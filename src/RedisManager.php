<?php declare(strict_types=1);

namespace Wfs\MasterSlaveRedis;

class RedisManager
{
    const DEFAULT_RETRY_NUM = 5;
    const DEFAULT_RETRY_INTERVAL = 1000;

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
     * @param int $retry_interval msec
     * @return \Redis
     * @throws RedisManagerException
     */
    private function connect(
        array $hostConfig,
        int $retry = self::DEFAULT_RETRY_NUM,
        int $retry_interval = self::DEFAULT_RETRY_INTERVAL
    ): \Redis {
        // pconnectはIPアドレスでする。
        $ip = gethostbyname($hostConfig['host']);
        $connection = new \Redis();

        for ($i = 0; $i < $retry; $i++) {
            try {
                $connection->pconnect($ip, $hostConfig['port'], $hostConfig['timeout']);
            } catch (\RedisException $e) {
                usleep($retry_interval * 1000);
                continue;
            }
            return $connection;
        }
        throw new RedisManagerException(sprintf(
            "Con't connect redis server: %s(%s):%d, t/o=%d",
            $hostConfig['host'],
            $ip,
            $hostConfig['port'],
            $hostConfig['timeout']
        ));
    }

    /**
     * @param int $retry
     * @param int $retry_interval
     * @return \Redis
     * @throws RedisManagerException
     */
    public function getMaster(int $retry = self::DEFAULT_RETRY_NUM,
                              int $retry_interval = self::DEFAULT_RETRY_INTERVAL): \Redis
    {
        $master = ConfigManipulator::pickupMasterConfig($this->config);
        return $this->connect($master, $retry, $retry_interval);
    }

    /**
     * @param int $retry
     * @param int $retry_interval
     * @return \Redis
     * @throws RedisManagerException
     */
    public function getSlave(int $retry = self::DEFAULT_RETRY_NUM,
                             int $retry_interval = self::DEFAULT_RETRY_INTERVAL): \Redis
    {
        $slave = ConfigManipulator::pickupSlaveConfig($this->config);
        return $this->connect($slave, $retry, $retry_interval);
    }
}
