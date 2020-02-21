<?php

namespace Wfs\MasterSlaveRedis\Tests\Medium;

use Redis;
use Wfs\MasterSlaveRedis\RedisManager;
use PHPUnit\Framework\TestCase;
use Wfs\MasterSlaveRedis\RedisManagerException;

class RedisTest extends TestCase
{
    public function testWriteMasterReadSlave()
    {
        $manager = new RedisManager([
            'master' => [
                'host' => 'redis-master',
            ],
            'slave' => [
                [
                    'host' => 'redis-slave1',
                ]
            ]
        ]);
        $expected = sprintf("%d", (new \DateTime())->getTimestamp());
        $manager->getMaster()->del("key1");
        $actual = $manager->getSlave()->get("key1");
        $this->assertFalse($actual);
        $manager->getMaster()->set("key1", $expected);
        $actual = $manager->getSlave()->get("key1");
        $this->assertSame($expected, $actual);
    }

    public function testWriteSlave()
    {
        $manager = new RedisManager([
            'master' => [
                'host' => 'redis-master',
            ],
            'slave' => [
                [
                    'host' => 'redis-slave1',
                ]
            ]
        ]);
        $this->expectException(\RedisException::class);
        $this->expectDeprecationMessage("READONLY You can't write against a read only replica.");
        $manager->getSlave()->del("key1");
    }

    public function testConnectionError()
    {
        $manager = new RedisManager([
            'master' => [
                'host' => 'redis-master',
                'port' => 1234,
            ],
            'slave' => [
                [
                    'host' => 'redis-slave1',
                ]
            ]
        ]);
        $this->expectException(RedisManagerException::class);
        $manager->getMaster()->info();
    }

    public function testPersistent()
    {
        $manager = new RedisManager([
            'master' => [
                'host' => 'redis-master',
            ],
            'slave' => [
                [
                    'host' => 'redis-slave1',
                ]
            ]
        ]);
        // total_connections_received: 累積接続コネクション数

        $ret = $manager->getMaster()->info();
        $startConnections = $ret['total_connections_received'];
        $ret = $manager->getMaster()->info();
        $ret = $manager->getMaster()->info();
        $ret = $manager->getMaster()->info();
        $ret = $manager->getMaster()->info();
        $ret = $manager->getMaster()->info();
        // getMaster(pconnect)を繰り返してもコネクション接続回数が増えない
        $this->assertSame($startConnections, $ret['total_connections_received']);

        $manager->getMaster()->close();
        $ret = $manager->getMaster()->info();
        // closeの後は１回増える
        $this->assertSame($startConnections + 1, $ret['total_connections_received']);
    }

    public function testDnsRoundRobin()
    {
        $manager = new RedisManager([
            'master' => [
                'host' => 'redis-master',
            ],
            'slave' => [
                [
                    'host' => 'redis-slave1',
                ]
            ]
        ]);
        // total_connections_received: 累積接続コネクション数
        $addressList = gethostbynamel('redis-slave1');
        $this->assertCount(4, $addressList,
            <<< EOT
You must run this test with the following command:
docker-compose up -d --scale redis-slave1=4 redis-slave1
docker-compose run --rm phpunit-full
EOT
        );

        $runIds = [];
        for ($i = 0; $i < 100; $i++) {
            $ret = $manager->getSlave()->info();
            $runIds[] = $ret['run_id'];
        }
        $uniqueRunIds = array_unique($runIds);

        $this->assertCount(4, $uniqueRunIds);
    }

    public function testDnsRoundRobinWithPureRedisObject()
    {
        $redis = new Redis();
        // total_connections_received: 累積接続コネクション数
        $addressList = gethostbynamel('redis-slave1');
        $this->assertCount(4, $addressList,
            <<< EOT
You must run this test with the following command:
docker-compose up -d --scale redis-slave1=4 redis-slave1
docker-compose run --rm phpunit-full
EOT
        );

        $runIds = [];
        for ($i = 0; $i < 100; $i++) {
            $ret = $redis->pconnect('redis-slave1');
            $runIds[] = $ret['run_id'];
        }
        $uniqueRunIds = array_unique($runIds);

        // php-redis manage connection pool with "host-name"
        $this->assertCount(1, $uniqueRunIds);
    }
}
