<?php

declare(strict_types=1);

namespace Keboola\GoogleOpenLineageWriter\Tests;

use Generator;
use Keboola\GoogleOpenLineageWriter\ConfigDefinition;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class ConfigDefinitionTest extends TestCase
{
    public function validConfigurationData(): Generator
    {
        yield 'minimal configuration' => [
            [
                'parameters' => [
                    'openlineage_api_url' => 'https://localhost',
                    'created_time_from' => '-1 day',
                ],
            ],
            [
                'parameters' => [
                    'openlineage_api_url' => 'https://localhost',
                    'created_time_from' => '-1 day',
                    'job_name_as_config' => false,
                ],
            ],
        ];

        yield 'configuration with job names' => [
            [
                'parameters' => [
                    'openlineage_api_url' => 'localhost:3000',
                    'created_time_from' => '-2 day',
                    'job_name_as_config' => true,
                ],
            ],
            [
                'parameters' => [
                    'openlineage_api_url' => 'localhost:3000',
                    'created_time_from' => '-2 day',
                    'job_name_as_config' => true,
                ],
            ],
        ];
    }

    public function invalidConfigurationData(): Generator
    {
        yield 'empty parameters' => [
            [
                'parameters' => [],
            ],
            'The child config "openlineage_api_url" under "root.parameters" must be configured.',
        ];

        yield 'empty openlineage_api_url' => [
            [
                'parameters' => [
                    'openlineage_api_url' => null,
                ],
            ],
            'The path "root.parameters.openlineage_api_url" cannot contain an empty value, but got null.',
        ];

        yield 'missing created_time_from' => [
            [
                'parameters' => [
                    'openlineage_api_url' => 'localhost',
                ],
            ],
            'The child config "created_time_from" under "root.parameters" must be configured.',
        ];

        yield 'empty created_time_from' => [
            [
                'parameters' => [
                    'openlineage_api_url' => 'localhost',
                    'created_time_from' => null,
                ],
            ],
            'The path "root.parameters.created_time_from" cannot contain an empty value, but got null.',
        ];

        yield 'invalid job_name_as_config' => [
            [
                'parameters' => [
                    'openlineage_api_url' => 'localhost',
                    'created_time_from' => '-1 day',
                    'job_name_as_config' => '123',
                ],
            ],
            'Invalid type for path "root.parameters.job_name_as_config". Expected "bool", but got "string".',
        ];
    }

    /**
     * @dataProvider validConfigurationData
     * @param array<string, mixed> $inputConfig
     * @param array<string, mixed> $expectedConfig
     */
    public function testValidConfiguration(array $inputConfig, array $expectedConfig): void
    {
        $processor = new Processor();
        $processedConfig = $processor->processConfiguration(new ConfigDefinition(), [$inputConfig]);
        self::assertSame($expectedConfig, $processedConfig);
    }

    /**
     * @dataProvider invalidConfigurationData
     * @param array<string, mixed> $inputConfig
     */
    public function testInvalidConfiguration(array $inputConfig, string $expectedExceptionMessage): void
    {
        $processor = new Processor();

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $processor->processConfiguration(new ConfigDefinition(), [$inputConfig]);
    }
}
