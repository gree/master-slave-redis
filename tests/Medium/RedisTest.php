<?php

namespace Wfs\MasterSlaveRedis\Tests\Medium;

use Wfs\MasterSlaveRedis\RedisManager;
use PHPUnit\Framework\TestCase;

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
}
