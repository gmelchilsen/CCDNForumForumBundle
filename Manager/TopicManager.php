<?php

/*
 * This file is part of the CCDNForum ForumBundle
 *
 * (c) CCDN (c) CodeConsortium <http://www.codeconsortium.com/>
 *
 * Available on github <http://www.github.com/codeconsortium/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CCDNForum\ForumBundle\Manager;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\QueryBuilder;

use CCDNForum\ForumBundle\Manager\BaseManagerInterface;
use CCDNForum\ForumBundle\Manager\BaseManager;

/**
 *
 * @author Reece Fowell <reece@codeconsortium.com>
 * @version 1.0
 */
class TopicManager extends BaseManager implements BaseManagerInterface
{
	/**
	 *
	 * @access protected
	 * @var string $roleToViewDeletedTopics
	 */	
	protected $roleToViewDeletedTopics = 'ROLE_MODERATOR';
	
	/**
	 *
	 * @access public
	 * @return bool
	 */	
	public function allowedToViewDeletedTopics()
	{
		return $this->securityContext->isGranted($this->roleToViewDeletedTopics);
	}
	
	/**
	 *
	 * @access public
	 * @param \CCDNForum\ForumBundle\Entity\Topic $topic
	 * @return bool
	 */
	public function isAuthorisedToReplyToTopic($topic)
	{
        if (! $topic->getBoard()->isAuthorisedToTopicReply($this->securityContext)) {
        	return false;
		}
        
        if ($topic->getIsClosed() || $topic->getIsDeleted()) {
			if (! $this->isGranted('ROLE_MODERATOR')) {
				return false;
			}
        }
				
		return true;
	}
	
	/**
	 *
	 * @access public
	 * @param \CCDNForum\ForumBundle\Entity\Topic $topic
	 * @return bool
	 */
	public function isAuthorisedToEditTopic($topic)
	{
		if ($topic->getIsDeleted() || $topic->getIsClosed()) {
			if (! $this->isGranted('ROLE_MODERATOR')) {
				return false;
			}
		}
		
		if ($this->isGranted('ROLE_USER')) {
			if ($topic->getFirstPost()) {
		        if ($topic->getFirstPost()->getCreatedBy()) {
					$post = $topic->getFirstPost();
				
		            // if user does not own post, or is not a mod
		            if ($post->getCreatedBy()->getId() != $this->getUser()->getId()) {
						if (! $this->isGranted('ROLE_MODERATOR')) {
							return false;
						}
		            }
				} else {
					if (! $this->isGranted('ROLE_MODERATOR')) {
						return false;
					}
				}
	        } else {
				return false;
	        }
		} else {
			return false;
		}
		
		return true;
	}
	
	/**
	 *
	 * @access public
	 * @param \CCDNForum\ForumBundle\Entity\Topic $topic
	 * @return bool
	 */
	public function isAuthorisedToDeleteTopic($topic)
	{
		if ($topic->getIsDeleted() || $topic->getIsClosed()) {
			if (! $this->isGranted('ROLE_MODERATOR')) {
				return false;
			}
		}
		
		if ($topic->getCachedReplyCount() > 0) {
			if (! $this->isGranted('ROLE_MODERATOR')) {
				return false;
			}
		}
		
		if ($this->isGranted('ROLE_USER')) {
			if ($topic->getFirstPost()) {
		        if ($topic->getFirstPost()->getCreatedBy()) {
					$post = $topic->getFirstPost();
				
		            // if user does not own post, or is not a mod
		            if ($post->getCreatedBy()->getId() != $this->getUser()->getId()) {
						if (! $this->isGranted('ROLE_MODERATOR')) {
							return false;
						}
		            }
				} else {
					if (! $this->isGranted('ROLE_MODERATOR')) {
						return false;
					}
				}
	        } else {
				return false;
	        }
		} else {
			return false;
		}
		
		return true;
	}
	
	/**
	 *
	 * @access public
	 * @param \CCDNForum\ForumBundle\Entity\Topic $topic
	 * @return bool
	 */
	public function isAuthorisedToRestoreTopic($topic)
	{
		if ($topic->getIsDeleted() || $topic->getIsClosed()) {
			if (! $this->isGranted('ROLE_MODERATOR')) {
				return false;
			}
		}
		
		if ($this->isGranted('ROLE_MODERATOR')) {
			if ($topic->getFirstPost()) {
		        if ($topic->getFirstPost()->getCreatedBy()) {
					$post = $topic->getFirstPost();
				
		            // if user does not own post, or is not a mod
		            if ($post->getCreatedBy()->getId() != $this->getUser()->getId()) {
						return false;
		            }
				} else {
					return false;
				}
	        } else {
				return false;
	        }
		} else {
			return false;
		}
		
		return true;
	}
	
	/**
	 *
	 * @access public
	 * @param int $boardId
	 * @return \CCDNForum\ForumBundle\Entity\Topic
	 */	
	public function findLastTopicForBoardByIdWithLastPost($boardId)
	{
		if (null == $boardId || ! is_numeric($boardId) || $boardId == 0) {
			throw new \Exception('Board id "' . $boardId . '" is invalid!');
		}
		
		$qb = $this->createSelectQuery(array('t', 'lp'));
		
		$qb
			->innerJoin('t.lastPost', 'lp')
			->where('t.board = :boardId')
			->andWhere('t.isDeleted = FALSE')
			->orderBy('lp.createdDate', 'DESC')
			->setMaxResults(1);
		
		return $this->gateway->findTopic($qb, array(':boardId' => $boardId));
	}
	
	/**
	 *
	 * @access public
	 * @param int $topicId
	 * @return \CCDNForum\ForumBundle\Entity\Topic
	 */	
	public function findOneByIdWithPostsByTopicId($topicId)
	{
		if (null == $topicId || ! is_numeric($topicId) || $topicId == 0) {
			throw new \Exception('Topic id "' . $topicId . '" is invalid!');
		}
		
		$qb = $this->createSelectQuery(array('t', 'p', 'fp', 'lp', 'b', 'c'));
		
		$qb
			->innerJoin('t.posts', 'p')
			->leftJoin('t.firstPost', 'fp')
			->leftJoin('t.lastPost', 'lp')
			->leftJoin('t.board', 'b')
			->leftJoin('b.category', 'c')
			->where(
				$this->limitQueryByDeletedStateAndByTopicId($qb)
			);
		
		return $this->gateway->findTopic($qb, array(':topicId' => $topicId));
	}
		
	/**
	 *
	 * @access public
	 * @param int $topicId
	 * @return \CCDNForum\ForumBundle\Entity\Topic
	 */	
	public function findOneByIdWithBoardAndCategory($topicId)
	{
		if (null == $topicId || ! is_numeric($topicId) || $topicId == 0) {
			throw new \Exception('Topic id "' . $topicId . '" is invalid!');
		}
		
		$qb = $this->createSelectQuery(array('t', 'b', 'c'));
		
		$qb
			->leftJoin('t.board', 'b')
			->leftJoin('b.category', 'c')
			->where(
				$this->limitQueryByDeletedStateAndByTopicId($qb)
			);
		
		return $this->gateway->findTopic($qb, array(':topicId' => $topicId));
	}
	
	/**
	 *
	 * @access public
	 * @param int $boardId
	 * @return \Doctrine\Common\Collections\ArrayCollection
	 */	
	public function findAllStickiedByBoardId($boardId)
	{
		if (null == $boardId || ! is_numeric($boardId) || $boardId == 0) {
			throw new \Exception('Board id "' . $boardId . '" is invalid!');
		}
		
		$params = array(':boardId' => $boardId, ':isSticky' => true);
		
		$qb = $this->createSelectQuery(array('t', 'b', 'c', 'fp', 'fp_author', 'lp', 'lp_author'));
		
		$qb
			->innerJoin('t.firstPost', 'fp')
			->leftJoin('t.lastPost', 'lp')
			->leftJoin('fp.createdBy', 'fp_author')
			->leftJoin('lp.createdBy', 'lp_author')
			->leftJoin('t.board', 'b')
			->leftJoin('b.category', 'c')
			->where(
				$this->limitQueryByStickiedAndDeletedStateByBoardId($qb)
			)
			->orderBy('lp.createdDate', 'DESC');
		
		return $this->gateway->findTopics($qb, $params);
	}
	
	/**
	 *
	 * @access public
	 * @param int $boardId
	 * @param int $page
	 * @return \Pagerfanta\Pagerfanta
	 */
	public function findAllPaginatedByBoardId($boardId, $page)
	{
		if (null == $boardId || ! is_numeric($boardId) || $boardId == 0) {
			throw new \Exception('Board id "' . $boardId . '" is invalid!');
		}
		
		$params = array(':boardId' => $boardId, ':isSticky' => false);
		
		$qb = $this->createSelectQuery(array('t', 'b', 'c', 'fp', 'fp_author', 'lp', 'lp_author'));
		
		$qb
			->innerJoin('t.firstPost', 'fp')
			->leftJoin('t.lastPost', 'lp')
			->leftJoin('fp.createdBy', 'fp_author')
			->leftJoin('lp.createdBy', 'lp_author')
			->leftJoin('t.board', 'b')
			->leftJoin('b.category', 'c')
			->where(
				$this->limitQueryByStickiedAndDeletedStateByBoardId($qb)
			)
			->setParameters($params)
			->orderBy('lp.createdDate', 'DESC');

		return $this->gateway->paginateQuery($qb, $this->getTopicsPerPageOnBoards(), $page);
	}
	
	/**
	 *
	 * @access protected
	 * @param \Doctrine\ORM\QueryBuilder $qb
	 * @return \Doctrine\ORM\QueryBuilder
	 */
	protected function limitQueryByDeletedStateAndByTopicId(QueryBuilder $qb)
	{
		if ($this->allowedToViewDeletedTopics()) {
			$expr = $qb->expr()->eq('t.id', ':topicId');
		} else {
			$expr = $qb->expr()->andX(
				$qb->expr()->eq('t.id', ':topicId'),
				$qb->expr()->eq('t.isDeleted', false)
			);
		}
		
		return $expr;
	}
		
	/**
	 *
	 * @access protected
	 * @param \Doctrine\ORM\QueryBuilder $qb
	 * @return \Doctrine\ORM\QueryBuilder
	 */
	protected function limitQueryByStickiedAndDeletedStateByBoardId(QueryBuilder $qb)
	{
		if ($this->allowedToViewDeletedTopics()) {
			$expr = $qb->expr()->andX(
				$qb->expr()->eq('t.board', ':boardId'),
				$qb->expr()->eq('t.isSticky', ':isSticky')
			);
		} else {
			$expr = $qb->expr()->andX(
				$qb->expr()->eq('t.board', ':boardId'),
				$qb->expr()->andX(
					$qb->expr()->eq('t.isSticky', ':isSticky'),
					$qb->expr()->eq('t.isDeleted', false)
				)
			);
		}
		
		return $expr;
	}
	
    /**
     *
     * @access public
     * @param \CCDNForum\ForumBundle\Entity\Post $post
     * @return \CCDNForum\ForumBundle\Manager\BaseManagerInterface
     */
    public function create($post)
    {
        // insert a new row.
        $this->persist($post)->flush();

        // get the topic.
        $topic = $post->getTopic();

        // set topic last_post and first_post, board's last_post.
        $topic->setFirstPost($post);
        $topic->setLastPost($post);

        // persist and refresh after a flush to get topic id.
        $this->persist($topic)->flush();
        $this->refresh($topic);

        if ($topic->getBoard()) {
            // Update affected Board stats.
            $this->managerBag->getBoardManager()->updateStats($topic->getBoard())->flush();
        }

		// Subscribe the user to the topic.
		$this->managerBag->getSubscriptionManager()->subscribe($topic)->flush();
		
        return $this;
    }

    /**
     *
     * @access public
     * @param \CCDNForum\ForumBundle\Entity\Topic $topic
     * @return \CCDNForum\ForumBundle\Manager\BaseManagerInterface
     */
    public function update($topic)
    {
        // update the record
        $this->persist($topic);

        return $this;
    }

    /**
     *
     * @access public
     * @param \CCDNForum\ForumBundle\Entity\Topic $topic
     * @return \CCDNForum\ForumBundle\Manager\BaseManagerInterface
     */
    public function restore($topic)
    {
        $topic->setIsDeleted(false);
        $topic->setDeletedBy(null);
        $topic->setDeletedDate(null);

        $this->persist($topic)->flush();

        // Update affected Topic stats.
        $this->updateStats($topic);

        return $this;
    }
	
    /**
     *
     * @access public
     * @param \CCDNForum\ForumBundle\Entity\Topic $topic
	 * @param $user
     * @return \CCDNForum\ForumBundle\Manager\BaseManagerInterface
     */
    public function softDelete($topic, $user)
    {
        // Don't overwite previous users accountability.
        if ( ! $topic->getDeletedBy() && ! $topic->getDeletedDate()) {
            $topic->setIsDeleted(true);
            $topic->setDeletedBy($user);
            $topic->setDeletedDate(new \DateTime());

            // Close the topic as a precaution.
            $topic->setIsClosed(true);
            $topic->setClosedBy($user);
            $topic->setClosedDate(new \DateTime());

            // update the record before doing record counts
            $this->persist($topic)->flush();

            // Update affected Topic stats.
            $this->updateStats($topic);
        }

        return $this;
    }
	
    /**
     *
     * @access public
     * @param Topic $topic
	 * @param $user
     * @return \CCDNForum\ForumBundle\Manager\BaseManagerInterface
     */
    public function close($topic, $user)
    {
        // Don't overwite previous users accountability.
        if ( ! $topic->getClosedBy() && ! $topic->getClosedDate()) {
            $topic->setIsClosed(true);
            $topic->setClosedBy($user);
            $topic->setClosedDate(new \DateTime());

            $this->persist($topic);
        }

        return $this;
    }

    /**
     *
     * @access public
     * @param \CCDNForum\ForumBundle\Entity\Topic $topic
     * @return \CCDNForum\ForumBundle\Manager\BaseManagerInterface
     */
    public function reopen($topic)
    {
        $topic->setIsClosed(false);
        $topic->setClosedBy(null);
        $topic->setClosedDate(null);

		if ($topic->getIsDeleted()) {	
	        $topic->setIsDeleted(false);
	        $topic->setDeletedBy(null);
	        $topic->setDeletedDate(null);			
		}
		
        $this->persist($topic);

        return $this;
    }

    /**
     *
     * @access public
     * @param \CCDNForum\ForumBundle\Entity\Topic $topic
     * @return \CCDNForum\ForumBundle\Manager\BaseManagerInterface
     */
    public function sticky($topic, $user)
    {
        $topic->setIsSticky(true);
        $topic->setStickiedBy($user);
        $topic->setStickiedDate(new \DateTime());

        $this->persist($topic);

        return $this;
    }

    /**
     *
     * @access public
     * @param \CCDNForum\ForumBundle\Entity\Topic $topic
     * @return \CCDNForum\ForumBundle\Manager\BaseManagerInterface
     */
    public function unsticky($topic)
    {
        $topic->setIsSticky(false);
        $topic->setStickiedBy(null);
        $topic->setStickiedDate(null);

        $this->persist($topic);

        return $this;
    }
	
    /**
     *
     * @access public
     * @param \CCDNForum\ForumBundle\Entity\Topic $topic
     * @return \CCDNForum\ForumBundle\Manager\BaseManagerInterface
     */
    public function updateStats($topic)
    {
		$postManager = $this->managerBag->getPostManager();

		// Get stats.
        $topicPostCount = $postManager->getPostCountForTopicById($topic->getId());
        $topicFirstPost = $postManager->getFirstPostForTopicById($topic->getId());
        $topicLastPost = $postManager->getLastPostForTopicById($topic->getId());

        // Set the board / topic last post.
        $topic->setCachedReplyCount($topicPostCount['postCount'] > 0 ? --$topicPostCount['postCount'] : 0);
        $topic->setFirstPost($topicFirstPost ?: null);
        $topic->setLastPost($topicLastPost ?: null);

        $this->persist($topic)->flush();

        if ($topic->getBoard()) {
            // Update affected Board stats.
            $this->managerBag->getBoardManager()->updateStats($topic->getBoard())->flush();
        }

        return $this;
    }

    /**
     *
     * @access public
     * @param array $topics
     * @return \CCDNForum\ForumBundle\Manager\BaseManagerInterface
     */
    public function bulkUpdateStats($topics)
    {
        foreach ($topics as $topic) {
            $this->updateStats($topic);
        }
		
		return $this;
    }

    /**
     *
     * @access public
     * @param \CCDNForum\ForumBundle\Entity\Topic $topic
	 * @return \CCDNForum\ForumBundle\Manager\BaseManagerInterface
     */
    public function incrementViewCounter($topic)
    {
        // set the new counters
        $topic->setCachedViewCount($topic->getCachedViewCount() + 1);

        $this->persist($topic)->flush();
		
		return $this;
    }
}