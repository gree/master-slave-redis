<?php declare(strict_types=1);

namespace Wfs\PrimaryReplicaRedis\Tests\Small;

use Closure;
use Wfs\PrimaryReplicaRedis\ConfigManipulator;
use PHPUnit\Framework\TestCase;

class ConfigManipulatorTest extends TestCase
{
    public function isValidHostConfigDataProvider()
    {
        return [
            [[], false, false],
            [[
                'host' => 'host-name',
            ], false, true],
            [[
                'host' => 123, // not string
            ], false, false],
            [[
                'host' => 'host-name',
                'port' => 6379,
            ], false, true],
            [[
                'host' => 'host-name',
                'timeout' => 3,
            ], false, true],
            [[
                'host' => 'host-name',
                'port' => 6379,
                'timeout' => 3,
            ], false, true],
            [[
                'host' => 'host-name',
                'port' => 'string', // non int
            ], false, false],
            [[
                'host' => 'host-name',
                'timeout' => 'string', // non int
            ], false, false],
            [[
                'host' => 'host-name',
                'port' => 'string', // non int
                'timeout' => 3,
            ], false, false],
            [[
                'host' => 'host-name',
                'port' => 6379,
                'timeout' => 'string', // non int
            ], false, false],
            [[
                'host' => 'host-name',
                'port' => 6379,
                'timeout' => 3,
                'unnecessary_key' => 'string',
            ], false, false],

            // checkOptionalKeys
            [[], true, false],
            [[
                'host' => 'host-name',
            ], true, false],
            [[
                'host' => 123, // not string
            ], true, false],
            [[
                'host' => 'host-name',
                'port' => 6379,
            ], true, false],
            [[
                'host' => 'host-name',
                'timeout' => 3,
            ], true, false],
            [[
                'host' => 'host-name',
                'port' => 6379,
                'timeout' => 3,
            ], true, true],
            [[
                'host' => 'host-name',
                'port' => 'string', // non int
            ], true, false],
            [[
                'host' => 'host-name',
                'timeout' => 'string', // non int
            ], true, false],
            [[
                'host' => 'host-name',
                'port' => 'string', // non int
                'timeout' => 3,
            ], true, false],
            [[
                'host' => 'host-name',
                'port' => 6379,
                'timeout' => 'string', // non int
            ], true, false],
            [[
                'host' => 'host-name',
                'port' => 6379,
                'timeout' => 3,
                'unnecessary_key' => 'string',
            ], true, false],
        ];
    }

    /**
     * @param $assocHostConfig
     * @param $checkOptionalKeys
     * @param $exceptedResult
     * @dataProvider isValidHostConfigDataProvider
     */
    public function testIsValidHostConfig($assocHostConfig, $checkOptionalKeys, $exceptedResult)
    {
        Closure::bind(function () use ($assocHostConfig, $checkOptionalKeys, $exceptedResult) {
            $actual = ConfigManipulator::isValidHostConfig($assocHostConfig, $checkOptionalKeys);
            $this->assertSame($exceptedResult, $actual);
        }, $this, ConfigManipulator::class)->__invoke();
    }

    public function testIsValidHostConfigOptionalArgs()
    {
        Closure::bind(function () {
            $actual = ConfigManipulator::isValidHostConfig(['host' => 'host-name'], false);
            $this->assertTrue($actual);
            $actual = ConfigManipulator::isValidHostConfig(['host' => 'host-name'], true);
            $this->assertFalse($actual);
            $actual = ConfigManipulator::isValidHostConfig(['host' => 'host-name']);
            $this->assertTrue($actual);
        }, $this, ConfigManipulator::class)->__invoke();
    }

    public function isValidConfigDataProvider()
    {
        $validHostConfig = ['host' => 'host-name', 'port' => 6379, 'timeout' => 3];
        $validHostConfigOnlyNecessaryKeys = ['host' => 'host-name'];
        $invalidHostConfig = ['invalid' => 'config!'];
        return [
            [[], false, false],
            [[
                'primary' => $validHostConfig,
            ], false, false],
            [[
                'primary' => $validHostConfig,
                'replica' => 'string',
            ], false, false],
            [[
                'primary' => $validHostConfig,
                'replica' => [],
            ], false, true],
            [[
                'primary' => $validHostConfigOnlyNecessaryKeys,
                'replica' => [],
            ], false, true],
            [[
                'primary' => $invalidHostConfig,
                'replica' => [],
            ], false, false],
            [[
                'primary' => $validHostConfig,
                'replica' => [],
            ], false, true],
            [[
                'primary' => $validHostConfig,
                'replica' => [
                    $validHostConfig,
                ],
            ], false, true],
            [[
                'primary' => $validHostConfig,
                'replica' => [
                    $validHostConfigOnlyNecessaryKeys,
                ],
            ], false, true],
            [[
                'primary' => $validHostConfig,
                'replica' => [
                    $invalidHostConfig,
                ],
            ], false, false],
            [[
                'primary' => $validHostConfig,
                'replica' => [
                    $validHostConfig,
                    $validHostConfigOnlyNecessaryKeys,
                ],
            ], false, true],
            [[
                'primary' => $validHostConfig,
                'replica' => [
                    $validHostConfig,
                    $invalidHostConfig,
                ],
            ], false, false],

            // require all optional keys

            [[], true, false],
            [[
                'primary' => $validHostConfig,
            ], true, false],
            [[
                'primary' => $validHostConfig,
                'replica' => [],
            ], true, true],
            [[
                'primary' => $validHostConfigOnlyNecessaryKeys,
                'replica' => [],
            ], true, false],
            [[
                'primary' => $invalidHostConfig,
                'replica' => [],
            ], true, false],
            [[
                'primary' => $validHostConfig,
                'replica' => [],
            ], true, true],
            [[
                'primary' => $validHostConfig,
                'replica' => [
                    $validHostConfig,
                ],
            ], true, true],
            [[
                'primary' => $validHostConfig,
                'replica' => [
                    $validHostConfigOnlyNecessaryKeys,
                ],
            ], true, false],
            [[
                'primary' => $validHostConfig,
                'replica' => [
                    $invalidHostConfig,
                ],
            ], true, false],
            [[
                'primary' => $validHostConfig,
                'replica' => [
                    $validHostConfig,
                    $validHostConfigOnlyNecessaryKeys,
                ],
            ], true, false],
            [[
                'primary' => $validHostConfig,
                'replica' => [
                    $validHostConfig,
                    $invalidHostConfig,
                ],
            ], true, false],
        ];
    }

    /**
     * @param $assocConfig
     * @param $checkOptionalKeys
     * @param $exceptedResult
     * @dataProvider isValidConfigDataProvider
     */
    public function testIsValidConfig($assocConfig, $checkOptionalKeys, $exceptedResult)
    {
        Closure::bind(function () use ($assocConfig, $checkOptionalKeys, $exceptedResult) {
            $actual = ConfigManipulator::isValidConfig($assocConfig, $checkOptionalKeys);
            $this->assertSame($exceptedResult, $actual);
        }, $this, ConfigManipulator::class)->__invoke();
    }

    public function testIsValidConfigOptionalArgs()
    {
        Closure::bind(function () {
            $actual = ConfigManipulator::isValidConfig(['primary' => ['host' => 'host-name'], 'replica' => []], false);
            $this->assertTrue($actual);
            $actual = ConfigManipulator::isValidConfig(['primary' => ['host' => 'host-name'], 'replica' => []], true);
            $this->assertFalse($actual);
            $actual = ConfigManipulator::isValidConfig(['primary' => ['host' => 'host-name'], 'replica' => []]);
            $this->assertTrue($actual);
        }, $this, ConfigManipulator::class)->__invoke();
    }

    public function fillHostDefaultValueDataProvider()
    {
        return [
            [
                [
                    'host' => 'host-name',
                ],
                [
                    'host' => 'host-name',
                    'port' => ConfigManipulator::DEFAULT_PORT,
                    'timeout' => ConfigManipulator::DEFAULT_TIMEOUT,
                ],
            ],
            [
                [
                    'host' => 'host-name',
                    'port' => 1234,
                ],
                [
                    'host' => 'host-name',
                    'port' => 1234,
                    'timeout' => ConfigManipulator::DEFAULT_TIMEOUT,
                ],
            ],
            [
                [
                    'host' => 'host-name',
                    'timeout' => 5678,
                ],
                [
                    'host' => 'host-name',
                    'port' => ConfigManipulator::DEFAULT_PORT,
                    'timeout' => 5678,
                ],
            ],
            [
                [
                    'host' => 'host-name',
                    'port' => 1234,
                    'timeout' => 5678,
                ],
                [
                    'host' => 'host-name',
                    'port' => 1234,
                    'timeout' => 5678,
                ],
            ],
        ];
    }

    /**
     * @param $inputAssocConfig
     * @param $expectedAssocConfig
     * @dataProvider fillHostDefaultValueDataProvider
     */
    public function testFillHostDefaultValue($inputAssocConfig, $expectedAssocConfig)
    {
        ksort($expectedAssocConfig);
        Closure::bind(function () use ($inputAssocConfig, $expectedAssocConfig) {
            $this->assertTrue(ConfigManipulator::isValidHostConfig($inputAssocConfig));
            $actual = ConfigManipulator::fillHostDefaultValue($inputAssocConfig);
            ksort($actual);
            $this->assertSame($expectedAssocConfig, $actual);
        }, $this, ConfigManipulator::class)->__invoke();
    }

    public function testPickupPrimaryConfigWithoutInvalidConfig()
    {
        $this->expectException(\InvalidArgumentException::class);
        ConfigManipulator::pickupPrimaryConfig([]);
    }
    public function testPickupPrimaryConfig()
    {
        $actual = ConfigManipulator::pickupPrimaryConfig([
            'primary' => [
                'host' => 'primary-host',
            ],
            'replica' => [
                [
                    'host' => 'replica-host',
                ]
            ]
        ]);
        $expected = [
            'host' => 'primary-host',
            'port' => ConfigManipulator::DEFAULT_PORT,
            'timeout' => ConfigManipulator::DEFAULT_TIMEOUT,
        ];
        ksort($expected);
        ksort($actual);
        $this->assertSame($expected, $actual);
    }

    public function testPickupReplicaConfigWithReplica()
    {
        $actual = ConfigManipulator::pickupReplicaConfig([
            'primary' => [
                'host' => 'primary-host',
            ],
            'replica' => [
                [
                    'host' => 'replica-host',
                ]
            ]
        ]);
        $expected = [
            'host' => 'replica-host',
            'port' => ConfigManipulator::DEFAULT_PORT,
            'timeout' => ConfigManipulator::DEFAULT_TIMEOUT,
        ];
        ksort($expected);
        ksort($actual);
        $this->assertSame($expected, $actual);

    }

    public function testPickupReplicaConfigWithoutReplica()
    {
        $actual = ConfigManipulator::pickupReplicaConfig([
            'primary' => [
                'host' => 'primary-host',
            ],
            'replica' => [
            ]
        ]);
        $expected = [
            'host' => 'primary-host',
            'port' => ConfigManipulator::DEFAULT_PORT,
            'timeout' => ConfigManipulator::DEFAULT_TIMEOUT,
        ];
        ksort($expected);
        ksort($actual);
        $this->assertSame($expected, $actual);
    }
}
