<?php

namespace Wfs\PrimaryReplicaRedis\Tests\Medium;

use Redis;
use Wfs\PrimaryReplicaRedis\RedisManager;
use PHPUnit\Framework\TestCase;
use Wfs\PrimaryReplicaRedis\RedisManagerException;

class RedisTest extends TestCase
{
    public function testWritePrimaryReadReplica()
    {
        $manager = new RedisManager([
            'primary' => [
                'host' => 'redis-primary',
            ],
            'replica' => [
                [
                    'host' => 'redis-replica1',
                ]
            ]
        ]);
        $expected = sprintf("%d", (new \DateTime())->getTimestamp());
        $manager->getPrimary()->del("key1");
        $actual = $manager->getReplica()->get("key1");
        $this->assertFalse($actual);
        $manager->getPrimary()->set("key1", $expected);
        $actual = $manager->getReplica()->get("key1");
        $this->assertSame($expected, $actual);
    }

    public function testWriteReplica()
    {
        $manager = new RedisManager([
            'primary' => [
                'host' => 'redis-primary',
            ],
            'replica' => [
                [
                    'host' => 'redis-replica1',
                ]
            ]
        ]);
        $this->expectException(\RedisException::class);
        $this->expectDeprecationMessage("READONLY You can't write against a read only replica.");
        $manager->getReplica()->del("key1");
    }

    public function testConnectionError()
    {
        $manager = new RedisManager([
            'primary' => [
                'host' => 'redis-primary',
                'port' => 1234,
            ],
            'replica' => [
                [
                    'host' => 'redis-replica1',
                ]
            ]
        ]);
        $this->expectException(RedisManagerException::class);
        $manager->getPrimary()->info();
    }

    public function testPersistent()
    {
        $manager = new RedisManager([
            'primary' => [
                'host' => 'redis-primary',
            ],
            'replica' => [
                [
                    'host' => 'redis-replica1',
                ]
            ]
        ]);
        // total_connections_received: 累積接続コネクション数

        $ret = $manager->getPrimary()->info();
        $startConnections = $ret['total_connections_received'];
        $ret = $manager->getPrimary()->info();
        $ret = $manager->getPrimary()->info();
        $ret = $manager->getPrimary()->info();
        $ret = $manager->getPrimary()->info();
        $ret = $manager->getPrimary()->info();
        // getPrimary(pconnect)を繰り返してもコネクション接続回数が増えない
        $this->assertSame($startConnections, $ret['total_connections_received']);

        $manager->getPrimary()->close();
        $ret = $manager->getPrimary()->info();
        // closeの後は１回増える
        $this->assertSame($startConnections + 1, $ret['total_connections_received']);
    }

    public function testDnsRoundRobin()
    {
        $manager = new RedisManager([
            'primary' => [
                'host' => 'redis-primary',
            ],
            'replica' => [
                [
                    'host' => 'redis-replica1',
                ]
            ]
        ]);
        // total_connections_received: 累積接続コネクション数
        $addressList = gethostbynamel('redis-replica1');
        $this->assertCount(4, $addressList,
            <<< EOT
You must run this test with the following command:
docker-compose up -d --scale redis-replica1=4 redis-replica1
docker-compose run --rm phpunit-full
EOT
        );

        $runIds = [];
        for ($i = 0; $i < 100; $i++) {
            $ret = $manager->getReplica()->info();
            $runIds[] = $ret['run_id'];
        }
        $uniqueRunIds = array_unique($runIds);

        $this->assertCount(4, $uniqueRunIds);
    }

    public function testDnsRoundRobinWithPureRedisObject()
    {
        $redis = new Redis();
        // total_connections_received: 累積接続コネクション数
        $addressList = gethostbynamel('redis-replica1');
        $this->assertCount(4, $addressList,
            <<< EOT
You must run this test with the following command:
docker-compose up -d --scale redis-replica1=4 redis-replica1
docker-compose run --rm phpunit-full
EOT
        );

        $runIds = [];
        for ($i = 0; $i < 100; $i++) {
            $redis->pconnect('redis-replica1');
            $ret = $redis->info();
            $runIds[] = $ret['run_id'];
        }
        $uniqueRunIds = array_unique($runIds);

        // php-redis manage connection pool with "host-name"
        $this->assertCount(1, $uniqueRunIds);
    }
}
