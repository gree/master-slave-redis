<?php declare(strict_types=1);

namespace Wfs\MasterSlaveRedis\Tests\Small;

use Closure;
use Wfs\MasterSlaveRedis\ConfigManipulator;
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
                'master' => $validHostConfig,
            ], false, false],
            [[
                'master' => $validHostConfig,
                'slave' => 'string',
            ], false, false],
            [[
                'master' => $validHostConfig,
                'slave' => [],
            ], false, true],
            [[
                'master' => $validHostConfigOnlyNecessaryKeys,
                'slave' => [],
            ], false, true],
            [[
                'master' => $invalidHostConfig,
                'slave' => [],
            ], false, false],
            [[
                'master' => $validHostConfig,
                'slave' => [],
            ], false, true],
            [[
                'master' => $validHostConfig,
                'slave' => [
                    $validHostConfig,
                ],
            ], false, true],
            [[
                'master' => $validHostConfig,
                'slave' => [
                    $validHostConfigOnlyNecessaryKeys,
                ],
            ], false, true],
            [[
                'master' => $validHostConfig,
                'slave' => [
                    $invalidHostConfig,
                ],
            ], false, false],
            [[
                'master' => $validHostConfig,
                'slave' => [
                    $validHostConfig,
                    $validHostConfigOnlyNecessaryKeys,
                ],
            ], false, true],
            [[
                'master' => $validHostConfig,
                'slave' => [
                    $validHostConfig,
                    $invalidHostConfig,
                ],
            ], false, false],

            // require all optional keys

            [[], true, false],
            [[
                'master' => $validHostConfig,
            ], true, false],
            [[
                'master' => $validHostConfig,
                'slave' => [],
            ], true, true],
            [[
                'master' => $validHostConfigOnlyNecessaryKeys,
                'slave' => [],
            ], true, false],
            [[
                'master' => $invalidHostConfig,
                'slave' => [],
            ], true, false],
            [[
                'master' => $validHostConfig,
                'slave' => [],
            ], true, true],
            [[
                'master' => $validHostConfig,
                'slave' => [
                    $validHostConfig,
                ],
            ], true, true],
            [[
                'master' => $validHostConfig,
                'slave' => [
                    $validHostConfigOnlyNecessaryKeys,
                ],
            ], true, false],
            [[
                'master' => $validHostConfig,
                'slave' => [
                    $invalidHostConfig,
                ],
            ], true, false],
            [[
                'master' => $validHostConfig,
                'slave' => [
                    $validHostConfig,
                    $validHostConfigOnlyNecessaryKeys,
                ],
            ], true, false],
            [[
                'master' => $validHostConfig,
                'slave' => [
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
            $actual = ConfigManipulator::isValidConfig(['master' => ['host' => 'host-name'], 'slave' => []], false);
            $this->assertTrue($actual);
            $actual = ConfigManipulator::isValidConfig(['master' => ['host' => 'host-name'], 'slave' => []], true);
            $this->assertFalse($actual);
            $actual = ConfigManipulator::isValidConfig(['master' => ['host' => 'host-name'], 'slave' => []]);
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

    public function testPickupMasterConfigWithoutInvalidConfig()
    {
        $this->expectException(\InvalidArgumentException::class);
        ConfigManipulator::pickupMasterConfig([]);
    }
    public function testPickupMasterConfig()
    {
        $actual = ConfigManipulator::pickupMasterConfig([
            'master' => [
                'host' => 'master-host',
            ],
            'slave' => [
                [
                    'host' => 'slave-host',
                ]
            ]
        ]);
        $expected = [
            'host' => 'master-host',
            'port' => ConfigManipulator::DEFAULT_PORT,
            'timeout' => ConfigManipulator::DEFAULT_TIMEOUT,
        ];
        ksort($expected);
        ksort($actual);
        $this->assertSame($expected, $actual);
    }

    public function testPickupSlaveConfigWithSlave()
    {
        $actual = ConfigManipulator::pickupSlaveConfig([
            'master' => [
                'host' => 'master-host',
            ],
            'slave' => [
                [
                    'host' => 'slave-host',
                ]
            ]
        ]);
        $expected = [
            'host' => 'slave-host',
            'port' => ConfigManipulator::DEFAULT_PORT,
            'timeout' => ConfigManipulator::DEFAULT_TIMEOUT,
        ];
        ksort($expected);
        ksort($actual);
        $this->assertSame($expected, $actual);

    }

    public function testPickupSlaveConfigWithoutSlave()
    {
        $actual = ConfigManipulator::pickupSlaveConfig([
            'master' => [
                'host' => 'master-host',
            ],
            'slave' => [
            ]
        ]);
        $expected = [
            'host' => 'master-host',
            'port' => ConfigManipulator::DEFAULT_PORT,
            'timeout' => ConfigManipulator::DEFAULT_TIMEOUT,
        ];
        ksort($expected);
        ksort($actual);
        $this->assertSame($expected, $actual);
    }
}
