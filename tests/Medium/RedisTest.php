<?php

namespace Wfs\MasterSlaveRedis\Tests\Medium;

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
}
