<?php declare(strict_types=1);

namespace Wfs\MasterSlaveRedis;

class RedisManager
{
    const DEFAULT_RETRY_NUM = 5;

    /**
     * @var array
     */
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @param array $hostConfig
     * @param int $retry
     * @return \Redis
     * @throws RedisManagerException
     */
    private function connect(array $hostConfig, int $retry = self::DEFAULT_RETRY_NUM): \Redis
    {
        // pconnectはIPアドレスでする。
        $ip = gethostbyname($hostConfig['host']);
        $connection = new \Redis();

        for ($i = 0; $i < $retry; $i++) {
            try {
                $connection->pconnect($ip, $hostConfig['port'], $hostConfig['timeout']);
            } catch (\RedisException $e) {
                continue;
            }
            return $connection;
        }
        throw new RedisManagerException(sprintf("Con't connect redis server: %s(%s):%d, t/o=%d",
            $hostConfig['host'], $ip, $hostConfig['port'], $hostConfig['timeout']));
    }

    /**
     * @return \Redis
     * @throws RedisManagerException
     */
    public function getMaster(): \Redis
    {
        $master = ConfigManipulator::pickupMasterConfig($this->config);
        return $this->connect($master);
    }

    /**
     * @return \Redis
     * @throws RedisManagerException
     */
    public function getSlave(): \Redis
    {
        $slave = ConfigManipulator::pickupSlaveConfig($this->config);
        return $this->connect($slave);
    }
}
