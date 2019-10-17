<?php declare(strict_types=1);

namespace Wfs\MasterSlaveRedis;

final class ConfigManipulator
{
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
    public static function pickupMasterConfig(array $assocConfig): array
    {
        self::throwIfInvalidConfig($assocConfig);
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
    public static function pickupSlaveConfig(array $assocConfig): array
    {
        self::throwIfInvalidConfig($assocConfig);

        // 空ならmasterを使う
        if (! isset($assocConfig['slave']) || count($assocConfig['slave']) === 0) {
            return self::pickupMasterConfig($assocConfig);
        }

        // 乱択
        $index = array_rand($assocConfig['slave']);
        return self::fillHostDefaultValue($assocConfig['slave'][$index]);
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
        // masterは必須。中身も正しく
        if (! array_key_exists('master', $assocConfig)) {
            return false;
        }
        if (! self::isValidHostConfig($assocConfig['master'], $checkOptionalKeys)) {
            return false;
        }

        // slaveも必須だが、空でもよい
        if (! array_key_exists('slave', $assocConfig)) {
            return false;
        }
        if (! is_iterable($assocConfig['slave'])) {
            return false;
        }

        // 存在しているslave設定は正しくなければならない
        foreach ($assocConfig['slave'] as $assocSlaveConfig) {
            if (! self::isValidHostConfig($assocSlaveConfig, $checkOptionalKeys)) {
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
