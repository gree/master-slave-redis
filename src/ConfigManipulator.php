<?php declare(strict_types=1);

namespace Wfs\MasterSlaveRedis;

final class ConfigManipulator
{
    const DEFAULT_PORT = 6379;
    const DEFAULT_TIMEOUT = 3;

    /**
     * @param array $assocHostConfig = [
     *      "host" => "host-name",
     *      "port" => 6379,
     *      "timeout" => 3,
     *  ]
     * @return array = [
     *      "host" => "host-name",
     *      "port" => 6379,
     *      "timeout" => 3,
     *  ]
     */
    static private function fillHostDefaultValue(array $assocHostConfig): array
    {
        if (! isset($assocHostConfig['port'])) {
            $assocHostConfig['port'] = self::DEFAULT_PORT;
        }
        if (! isset($assocHostConfig['timeout'])) {
            $assocHostConfig['timeout'] = self::DEFAULT_TIMEOUT;
        }
        return $assocHostConfig;
    }

    /**
     * @param array $assocConfig = [
     *      "master" => [
     *          "host" => "host-name",
     *          "port" => 6379,
     *          "timeout" => 3,
     *      ],
     *      "slave" => [
     *          [
     *              "host" => "host-name",
     *              "port" => 6379,
     *              "timeout" => 3,
     *          ],
     *      ],
     *  ]
     * @return array = [
     *      "host" => "host-name",
     *      "port" => 6379,
     *      "timeout" => 3,
     *  ]
     */
    static public function pickupMasterConfig(array $assocConfig): array
    {
        self::raiseIfInvalidConfig($assocConfig);
        return self::fillHostDefaultValue($assocConfig['master']);
    }

    /**
     * @param array $assocConfig = [
     *      "master" => [
     *          "host" => "host-name",
     *          "port" => 6379,
     *          "timeout" => 3,
     *      ],
     *      "slave" => [
     *          [
     *              "host" => "host-name",
     *              "port" => 6379,
     *              "timeout" => 3,
     *          ],
     *      ],
     *  ]
     * @return array = [
     *      "host" => "host-name",
     *      "port" => 6379,
     *      "timeout" => 3,
     *  ]
     */
    static public function pickupSlaveConfig(array $assocConfig): array
    {
        self::raiseIfInvalidConfig($assocConfig);
        if (! isset($assocConfig['slave']) || count($assocConfig['slave']) === 0) {
            return self::pickupMasterConfig($assocConfig);
        }
        $index = array_rand($assocConfig['slave']);
        return self::fillHostDefaultValue($assocConfig['slave'][$index]);
    }

    static private function isValidHostConfig(array $assocHostConfig, bool $checkOptionalKeys = false): bool
    {
        if (! array_key_exists('host', $assocHostConfig)) {
            return false;
        }
        if ($checkOptionalKeys && ! array_key_exists('port', $assocHostConfig)) {
            return false;
        }
        if ($checkOptionalKeys && ! array_key_exists('timeout', $assocHostConfig)) {
            return false;
        }
        if (! is_string($assocHostConfig['host'])) {
            return false;
        }
        if (array_key_exists('port', $assocHostConfig) && ! is_int($assocHostConfig['port'])) {
            return false;
        }
        if (array_key_exists('timeout', $assocHostConfig) && ! is_int($assocHostConfig['timeout'])) {
            return false;
        }
        if (count(array_diff(array_keys($assocHostConfig), ['host', 'port', 'timeout'])) > 0) {
            return false;
        }
        return true;
    }

    static private function isValidConfig(array $assocConfig, bool $checkOptionalKeys = false): bool
    {
        if (! array_key_exists('master', $assocConfig)) {
            return false;
        }
        if (! self::isValidHostConfig($assocConfig['master'], $checkOptionalKeys)) {
            return false;
        }

        if (! array_key_exists('slave', $assocConfig)) {
            return false;
        }
        if (! is_iterable($assocConfig['slave'])) {
            return false;
        }

        foreach ($assocConfig['slave'] as $assocSlaveConfig) {
            if (! self::isValidHostConfig($assocSlaveConfig, $checkOptionalKeys)) {
                return false;
            }
        }
        return true;
    }

    static private function raiseIfInvalidConfig(array $assocConfig, bool $checkOptionalConfig = false)
    {
        if (! self::isValidConfig($assocConfig, $checkOptionalConfig)) {
            throw new \InvalidArgumentException("invalid config");
        }
    }
}
