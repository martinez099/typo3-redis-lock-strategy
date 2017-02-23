<?php

namespace Tourstream\RedisLockStrategy\Tests;

use Tourstream\RedisLockStrategy\RedisLockStrategy;
use TYPO3\CMS\Core\Locking\LockFactory;
use TYPO3\CMS\Core\Tests\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @author Alexander Miehe <alexander.miehe@tourstream.eu>
 *
 * @covers \Tourstream\RedisLockStrategy\RedisLockStrategy
 */
class RedisLockStrategyTest extends FunctionalTestCase
{
    /**
     * @var LockFactory
     */
    private $lockFactory;

    /**
     * @test
     * @expectedException \TYPO3\CMS\Core\Locking\Exception
     * @expectedExceptionMessage no configuration for redis lock strategy found
     */
    public function should_throw_exception_because_config_is_missing()
    {
        $this->lockFactory->createLocker('test');
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Core\Locking\Exception
     * @expectedExceptionMessage no configuration for redis lock strategy found
     */
    public function should_throw_exception_because_config_is_not_an_array()
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['redis_lock'] = 'test';
        $this->lockFactory->createLocker('test');
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Core\Locking\Exception
     * @expectedExceptionMessage no host for redis lock strategy found
     */
    public function should_throw_exception_because_config_has_no_host()
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['redis_lock'] = [
        ];

        $this->lockFactory->createLocker('test');
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Core\Locking\Exception
     * @expectedExceptionMessage no database for redis lock strategy found
     */
    public function should_throw_exception_because_config_has_no_database()
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['redis_lock'] = [
             'host' => 'redis'
        ];

        $this->lockFactory->createLocker('test');
    }

    /**
     * @test
     */
    public function should_connect_and_acquire_a_lock()
    {
        $id = uniqid();

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['redis_lock'] = [
            'host' => '192.168.0.101',
            'port' => 6379,
            'database' => 7
        ];

        $locker =$this->lockFactory->createLocker($id);

        $redis = $this->getRedisClient();

        $redis->set($id, 'testvalue');

        self::assertTrue($locker->acquire());
    }

    /**
     * @test
     */
    public function should_connect_and_acquire_a_existing_lock()
    {
        $id = uniqid();

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['redis_lock'] = [
            'host' => '192.168.0.101',
            'port' => 6379,
            'database' => 7
        ];

        $locker =$this->lockFactory->createLocker($id);

        self::assertTrue($locker->acquire());

        $redis = $this->getRedisClient();

        self::assertTrue($redis->exists($id));
    }

    /**
     * @test
     */
    public function should_connect_and_check_if_lock_is_acquired()
    {
        $id = uniqid();

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['redis_lock'] = [
            'host' => '192.168.0.101',
            'port' => 6379,
            'database' => 7
        ];

        $locker =$this->lockFactory->createLocker($id);

        $redis = $this->getRedisClient();

        $redis->set($id, 'testvalue');

        self::assertTrue($locker->isAcquired());
    }

    /**
     * @test
     */
    public function should_connect_and_destroy_a_lock()
    {
        $id = uniqid();

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['redis_lock'] = [
            'host' => '192.168.0.101',
            'port' => 6379,
            'database' => 7
        ];

        $locker =$this->lockFactory->createLocker($id);

        $redis = $this->getRedisClient();

        $redis->set($id, 'testvalue');

        $locker->destroy();

        self::assertFalse($redis->exists($id));
    }

    /**
     * @test
     */
    public function should_connect_and_destroy_a_not_existing_lock()
    {
        $id = uniqid();

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['redis_lock'] = [
            'host' => '192.168.0.101',
            'port' => 6379,
            'database' => 7
        ];

        $locker =$this->lockFactory->createLocker($id);

        $redis = $this->getRedisClient();

        $locker->destroy();

        self::assertFalse($redis->exists($id));
    }

    /**
     * @return \Redis
     */
    private function getRedisClient()
    {
        $redis = new \Redis();
        $redis->connect('192.168.0.101');
        $redis->select(7);

        return $redis;
    }

    protected function setUp()
    {
        $this->testExtensionsToLoad[] = 'typo3conf/ext/redis_lock_strategy';

        parent::setUp();

        $this->lockFactory = GeneralUtility::makeInstance(LockFactory::class);
        $this->lockFactory->addLockingStrategy(RedisLockStrategy::class);
    }

    protected function tearDown()
    {
        parent::tearDown();

        $this->getRedisClient()->flushDB();
    }
}
