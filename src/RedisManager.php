<?php declare(strict_types=1);

namespace Wfs\MasterSlaveRedis;

class RedisManager
{
    /**
     * @var array
     */
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    private function connect(array $hostConfig): \Redis
    {
        // pconnectはIPアドレスでする。
        $ip = gethostbyname($hostConfig['host']);
        $connection = new \Redis();
        $connection->pconnect($ip, $hostConfig['port'], $hostConfig['timeout']);
        return $connection;
    }

    public function getMaster(): \Redis
    {
        $master = ConfigManipulator::pickupMasterConfig($this->config);
        return $this->connect($master);
    }

    public function getSlave(): \Redis
    {
        $slave = ConfigManipulator::pickupSlaveConfig($this->config);
        return $this->connect($slave);
    }
}
