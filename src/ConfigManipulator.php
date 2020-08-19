<?php

declare(strict_types=1);

namespace Wfs\PrimaryReplicaRedis;

final class ConfigManipulator
{
    const PRIMARY_KEY = 'primary';
    const REPLICA_KEY = 'replica';
    const DEFAULT_PORT = 6379;
    const DEFAULT_TIMEOUT = 0;

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
    private static function fillHostDefaultValue(array $assocHostConfig): array
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
     *      "primary" => [
     *          "host" => "host-name",
     *          "port" => 6379,
     *          "timeout" => 3,
     *      ],
     *      "replica" => [
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
    public static function pickupPrimaryConfig(array $assocConfig): array
    {
        self::throwIfInvalidConfig($assocConfig);
        return self::fillHostDefaultValue($assocConfig[self::PRIMARY_KEY]);
    }

    /**
     * @param array $assocConfig = [
     *      "primary" => [
     *          "host" => "host-name",
     *          "port" => 6379,
     *          "timeout" => 3,
     *      ],
     *      "replica" => [
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
    public static function pickupReplicaConfig(array $assocConfig): array
    {
        self::throwIfInvalidConfig($assocConfig);

        // 空ならprimaryを使う
        if (! isset($assocConfig[self::REPLICA_KEY]) || count($assocConfig[self::REPLICA_KEY]) === 0) {
            return self::pickupPrimaryConfig($assocConfig);
        }

        // 乱択
        $index = array_rand($assocConfig[self::REPLICA_KEY]);
        return self::fillHostDefaultValue($assocConfig[self::REPLICA_KEY][$index]);
    }

    private static function isValidHostConfig(array $assocHostConfig, bool $checkOptionalKeys = false): bool
    {
        // hostは必須
        if (! array_key_exists('host', $assocHostConfig)) {
            return false;
        }

        // port, timeoutは$checkOptionalKeys=trueの時だけチェック
        if ($checkOptionalKeys && ! array_key_exists('port', $assocHostConfig)) {
            return false;
        }
        if ($checkOptionalKeys && ! array_key_exists('timeout', $assocHostConfig)) {
            return false;
        }

        // 型チェック
        if (! is_string($assocHostConfig['host'])) {
            return false;
        }
        if (array_key_exists('port', $assocHostConfig) && ! is_int($assocHostConfig['port'])) {
            return false;
        }
        if (array_key_exists('timeout', $assocHostConfig) && ! is_int($assocHostConfig['timeout'])) {
            return false;
        }

        // 不要キーチェック
        if (count(array_diff(array_keys($assocHostConfig), ['host', 'port', 'timeout'])) > 0) {
            return false;
        }
        return true;
    }

    private static function isValidConfig(array $assocConfig, bool $checkOptionalKeys = false): bool
    {
        // primaryは必須。中身も正しく
        if (! array_key_exists(self::PRIMARY_KEY, $assocConfig)) {
            return false;
        }
        if (! self::isValidHostConfig($assocConfig[self::PRIMARY_KEY], $checkOptionalKeys)) {
            return false;
        }

        // replicaも必須だが、空でもよい
        if (! array_key_exists(self::REPLICA_KEY, $assocConfig)) {
            return false;
        }
        if (! is_iterable($assocConfig[self::REPLICA_KEY])) {
            return false;
        }

        // 存在しているreplica設定は正しくなければならない
        foreach ($assocConfig[self::REPLICA_KEY] as $assocReplicaConfig) {
            if (! self::isValidHostConfig($assocReplicaConfig, $checkOptionalKeys)) {
                return false;
            }
        }
        return true;
    }

    private static function throwIfInvalidConfig(array $assocConfig, bool $checkOptionalConfig = false)
    {
        if (! self::isValidConfig($assocConfig, $checkOptionalConfig)) {
            throw new \InvalidArgumentException("invalid config");
        }
    }
}
