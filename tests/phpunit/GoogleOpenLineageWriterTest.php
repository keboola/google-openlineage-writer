<?php

declare(strict_types=1);

namespace Keboola\GoogleOpenLineageWriter\Tests;

use DateTimeImmutable;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Keboola\GoogleOpenLineageWriter\Config;
use Keboola\GoogleOpenLineageWriter\ConfigDefinition;
use Keboola\GoogleOpenLineageWriter\GoogleHttpClientFactory;
use Keboola\OpenLineageGenerator\OpenLineageWriter;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;

class GoogleOpenLineageWriterTest extends TestCase
{
    /**
     * @param array<string, mixed> $options
     */
    private function getQueueClient(array $options): Client
    {
        return new Client(
            array_merge(
                [
                    'base_uri' => 'http://example.com/',
                    'headers' => [
                        'X-StorageApi-Token' => 'token',
                    ],
                ],
                $options,
            ),
        );
    }

    private function getConfig(): Config
    {
        return new Config([
                'authorization' => [
                    'oauth_api' => [
                        'credentials' => [
                            'appKey' => (string) getenv('CLIENT_ID'),
                            '#appSecret' => (string) getenv('CLIENT_SECRET'),
                            '#data' =>  json_encode([
                                'access_token' => getenv('ACCESS_TOKEN'),
                                'refresh_token' => getenv('REFRESH_TOKEN'),
                            ]),
                        ],
                    ],
                ],
                'parameters' => [
                    'openlineage_api_url' => 'https://datalineage.googleapis.com',
                    'openlineage_api_endpoint' => '/v1/projects/devel-249608/locations/us-central1' .
                        ':processOpenLineageRunEvent',
                    'created_time_from' => '-1 day',
                ],
        ], new ConfigDefinition());
    }

    public function testWrite(): void
    {
        $queueClient = $this->mockQueueClient();
        $testLogger = new TestLogger();

        $config = $this->getConfig();
        $openLineageClientFactory = new GoogleHttpClientFactory($config);
        $openLineageClient = $openLineageClientFactory->getClient();

        /** @var Uri $baseUri */
        $baseUri = $openLineageClient->getConfig('base_uri');
        self::assertEquals('datalineage.googleapis.com', $baseUri->getHost());

        $openLineageWriter = new OpenLineageWriter(
            $queueClient,
            (new GoogleHttpClientFactory($config))->getClient(),
            $testLogger,
            new DateTimeImmutable($config->getCreatedTimeFrom()),
            $config->getOpenLineageEndpoint(),
            $config->getJobNameAsConfig(),
            true,
            'Y-m-d\TH:i:s.000\Z',
        );

        $openLineageWriter->write();

        $this->assertTrue($testLogger->hasInfoThatContains('Job 123 import to OpenLineage API - start'));
        $this->assertTrue($testLogger->hasInfoThatContains('Job 123 import to OpenLineage API - end'));
        $this->assertTrue($testLogger->hasInfoThatContains('Job 124 import to OpenLineage API - start'));
        $this->assertTrue($testLogger->hasInfoThatContains('Job 124 import to OpenLineage API - end'));
    }

    private const JOB_LIST_RESPONSE = '[
        {
            "id": "123",
            "runId": "123",
            "component": "keboola.component",
            "config": "456",
            "result": {
                "input": {
                }
            }
        },
        {
            "id": "124",
            "runId": "124",
            "component": "keboola.component",
            "config": "457",
            "result": {
                "input": {
                }
            }
        }
    ]';

    private const JOB_LINEAGE_RESPONSE = '[
        {
            "eventType": "START",
            "eventTime": "2022-03-04T12:07:00.406Z",
            "run": {
              "runId": "3fa85f64-5717-4562-b3fc-2c963f66afa6",
              "facets": {
                "parent": {
                  "_producer": "https://connection.north-europe.azure.keboola.com",
                  "_schemaURL": "https://openlineage.io/spec/facets/1-0-0/ParentRunFacet.json#/$defs/ParentRunFacet",
                  "run": {
                    "runId": "3fa85f64-5717-4562-b3fc-2c963f66afa6"
                  },
                  "job": {
                    "namespace": "connection.north-europe.azure.keboola.com/project/1234",
                    "name": "keboola.orchestrator-123"
                  }
                }
              }
            },
            "job": {
              "namespace": "connection.north-europe.azure.keboola.com/project/1234",
              "name": "keboola.snowflake-transformation-123456"
            },
            "producer": "https://connection.north-europe.azure.keboola.com",
            "inputs": [
              {
                "namespace": "connection.north-europe.azure.keboola.com/project/1234",
                "name": "in.c-kds-team-ex-shoptet-permalink-1234567.orders",
                "facets": {
                  "schema": {
                    "_producer": "https://connection.north-europe.azure.keboola.com",
                    "_schemaURL": "https://openlineage.io/spec/1-0-2/OpenLineage.json#/$defs/InputDatasetFacet",
                    "fields": [
                      {
                        "name": "code"
                      },
                      {
                        "name": "date"
                      },
                      {
                        "name": "totalPriceWithVat"
                      },
                      {
                        "name": "currency"
                      }
                    ]
                  }
                }
              }
            ]
            },
            {
            "eventType": "COMPLETE",
            "eventTime": "2022-03-04T12:07:00.406Z",
            "run": {
              "runId": "3fa85f64-5717-4562-b3fc-2c963f66afa6"
            },
            "job": {
              "namespace": "connection.north-europe.azure.keboola.com/project/1234",
              "name": "keboola.snowflake-transformation-123456"
            },
            "producer": "https://connection.north-europe.azure.keboola.com",
            "outputs": [
              {
                "namespace": "connection.north-europe.azure.keboola.com/project/1234",
                "name": "out.c-orders.dailyStats",
                "facets": {
                  "schema": {
                    "_producer": "https://connection.north-europe.azure.keboola.com",
                    "_schemaURL": "https://openlineage.io/spec/1-0-2/OpenLineage.json#/$defs/OutputDatasetFacet",
                    "fields": [
                      {
                        "name": "date"
                      },
                      {
                        "name": "ordersCount"
                      },
                      {
                        "name": "totalPriceEuroSum"
                      }
                    ]
                  }
                }
              }
            ]
            }
        ]';

    private function mockQueueClient(): Client
    {
        $mockHandler = new MockHandler([
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                self::JOB_LIST_RESPONSE,
            ),
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                self::JOB_LINEAGE_RESPONSE,
            ),
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                self::JOB_LINEAGE_RESPONSE,
            ),
        ]);
        // Add the history middleware to the handler stack.
        $requestHistory = [];
        $history = Middleware::history($requestHistory);
        $stack = HandlerStack::create($mockHandler);
        $stack->push($history);

        return $this->getQueueClient(['handler' => $stack]);
    }
}
