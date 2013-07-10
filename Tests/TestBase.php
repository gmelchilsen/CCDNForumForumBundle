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

namespace CCDNForum\ForumBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;

use CCDNUser\UserBundle\Entity\User;

use CCDNForum\ForumBundle\Entity\Forum;
use CCDNForum\ForumBundle\Entity\Category;
use CCDNForum\ForumBundle\Entity\Board;
use CCDNForum\ForumBundle\Entity\Topic;
use CCDNForum\ForumBundle\Entity\Post;

class TestBase extends WebTestCase
{
    /**
	 *
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;
	
	/**
	 *
	 * @var $container
	 */
	private $container;
	
	/**
	 *
	 * @access public
	 */
    public function setUp()
    {
        $kernel = static::createKernel();

        $kernel->boot();
		
		$this->container = $kernel->getContainer();

        $this->em = $this->container->get('doctrine.orm.entity_manager');
		
		$this->purge();
		
		$users      = $this->addFixturesForUsers();
		$forums     = $this->addFixturesForForums();
		$categories = $this->addFixturesForCategories($forums);
		$boards     = $this->addFixturesForBoards($categories);
		$topics     = $this->addFixturesForTopics($boards);
		$posts      = $this->addFixturesForPosts($topics, $users);
    }
	
    public function purge()
    {
        $purger = new ORMPurger($this->em);
        $executor = new ORMExecutor($this->em, $purger);
        $executor->purge();
	}
	
	public function addFixturesForUsers()
	{
		$userNames = array('admin', 'tom', 'dick', 'harry');
		$users = array();
		
		foreach ($userNames as $username) {
			$user = new User();
			
			$user->setUsername($username);
			$user->setEmail($username . '@foobar.com');
			$user->setPlainPassword('password');
			
			$this->em->persist($user);
			$this->em->flush();
			
			$this->em->refresh($user);
			
			$users[] = $user;
		}
	
		return $users;
	}

	public function addFixturesForForums()
	{
		$forumNames = array('test_forum_1', 'test_forum_2', 'test_forum_3');
		$forums = array();
		
		foreach ($forumNames as $forumName) {
			$forum = new Forum();
			
			$forum->setName($forumName);
			
			$this->em->persist($forum);
			$this->em->flush();

			$this->em->refresh($forum);
			
			$forums[] = $forum;
		}
		
		return $forums;
	}
	
	public function addFixturesForCategories($forums)
	{
		$categoryNames = array('test_category_1', 'test_category_2', 'test_category_3');
		$categories = array();
		
		foreach ($categoryNames as $index => $categoryName) {
			$category = new Category();
			
			$category->setName($categoryName);
			$category->setListOrderPriority($index);
			
			$this->em->persist($category);
			$this->em->flush();
			
			$this->em->refresh($category);
			
			$categories[] = $category;
		}
		
		return $categories;
	}
	
	public function addFixturesForBoards($categories)
	{
		$boardNames = array('test_board_1', 'test_board_2', 'test_board_3');
		$boards = array();
		
		foreach ($boardNames as $index => $boardName) {
			$board = new Board();
			
			$board->setName($boardName);
			$board->setDescription($boardName);
			$board->setListOrderPriority($index);
			
			$this->em->persist($board);
			$this->em->flush();
			
			$this->em->refresh($board);
			
			$boards[] = $board;
		}
		
		return $boards;
	}
	
	public function addFixturesForTopics($boards)
	{
		return array();
	}
	
	public function addFixturesForPosts($topics, $users)
	{
		
		return array();
	}

    /**
     *
     * @var \CCDNForum\ForumBundle\Model\Model\ForumModel $forumModel
     */
    private $forumModel;

    /**
     *
     * @var \CCDNForum\ForumBundle\Model\Model\CategoryModel $categoryModel
     */
    private $categoryModel;

    /**
     *
     * @var \CCDNForum\ForumBundle\Model\Model\BoardModel $boardModel
     */
    private $boardModel;

    /**
     *
     * @var \CCDNForum\ForumBundle\Model\Model\TopicModel $topicModel
     */
    private $topicModel;

    /**
     *
     * @var \CCDNForum\ForumBundle\Model\Model\PostModel $postModel
     */
    private $postModel;

    /**
     *
     * @var \CCDNForum\ForumBundle\Model\Model\DraftModel $draftModel
     */
    private $draftModel;

    /**
     *
     * @var \CCDNForum\ForumBundle\Model\Model\RegistryModel $registryModel
     */
    private $registryModel;

    /**
     *
     * @var \CCDNForum\ForumBundle\Model\Model\SubscriptionModel $subscriptionModel
     */
    private $subscriptionModel;

    /**
     *
     * @access protected
     * @return \CCDNForum\ForumBundle\Model\Model\ForumModel
     */
    protected function getForumModel()
    {
        if (null == $this->forumModel) {
            $this->forumModel = $this->container->get('ccdn_forum_forum.model.forum');
        }

        return $this->forumModel;
    }

    /**
     *
     * @access protected
     * @return \CCDNForum\ForumBundle\Model\Model\CategoryModel
     */
    protected function getCategoryModel()
    {
        if (null == $this->categoryModel) {
            $this->categoryModel = $this->container->get('ccdn_forum_forum.model.category');
        }

        return $this->categoryModel;
    }

    /**
     *
     * @access protected
     * @return \CCDNForum\ForumBundle\Model\Model\BoardModel
     */
    protected function getBoardModel()
    {
        if (null == $this->boardModel) {
            $this->boardModel = $this->container->get('ccdn_forum_forum.model.board');
        }

        return $this->boardModel;
    }

    /**
     *
     * @access protected
     * @return \CCDNForum\ForumBundle\Model\Model\TopicModel
     */
    protected function getTopicModel()
    {
        if (null == $this->topicModel) {
            $this->topicModel = $this->container->get('ccdn_forum_forum.model.topic');
        }

        return $this->topicModel;
    }

    /**
     *
     * @access protected
     * @return \CCDNForum\ForumBundle\Model\Model\PostModel
     */
    protected function getPostModel()
    {
        if (null == $this->postModel) {
            $this->postModel = $this->container->get('ccdn_forum_forum.model.post');
        }

        return $this->postModel;
    }

    /**
     *
     * @access protected
     * @return \CCDNForum\ForumBundle\Model\Model\DraftModel
     */
    protected function getDraftModel()
    {
        if (null == $this->draftModel) {
            $this->draftModel = $this->container->get('ccdn_forum_forum.model.draft');
        }

        return $this->draftModel;
    }

    /**
     *
     * @access protected
     * @return \CCDNForum\ForumBundle\Model\Model\RegistryModel
     */
    protected function getRegistryModel()
    {
        if (null == $this->registryModel) {
            $this->registryModel = $this->container->get('ccdn_forum_forum.model.registry');
        }

        return $this->registryModel;
    }

    /**
     *
     * @access protected
     * @return \CCDNForum\ForumBundle\Model\Model\SubscriptionModel
     */
    protected function getSubscriptionModel()
    {
        if (null == $this->subscriptionModel) {
            $this->subscriptionModel = $this->container->get('ccdn_forum_forum.model.subscription');
        }

        return $this->subscriptionModel;
    }
}