<?php

declare(strict_types=1);

namespace Keboola\GoogleOpenLineageWriter;

use DateTimeImmutable;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use Keboola\Component\BaseComponent;
use Keboola\Component\UserException;
use Keboola\Google\ClientBundle\Google\RestApi;
use Keboola\OpenLineageGenerator\GeneratorException;
use Keboola\OpenLineageGenerator\OpenLineageWriter;
use Keboola\StorageApi\Client as StorageClient;
use Throwable;

class Component extends BaseComponent
{
    /**
     * @throws \Keboola\Component\UserException
     * @throws \Keboola\StorageApi\ClientException
     */
    protected function run(): void
    {
        if (empty($this->getRawConfig())) {
            throw new UserException('The configuration is missing.');
        }

        $storageUrl = (string) getenv('KBC_URL');
        $storageToken = (string) getenv('KBC_TOKEN');

        /** @var Config $config */
        $config = $this->getConfig();

        $storageClient = new StorageClient([
            'token' => $storageToken,
            'url' => $storageUrl,
        ]);

        $queueClient = new Client([
            'base_uri' => $storageClient->getServiceUrl('queue'),
            'headers' => [
                'X-StorageApi-Token' => $storageToken,
            ],
        ]);

        try {
            $createdTimeFrom = new DateTimeImmutable($config->getCreatedTimeFrom());
        } catch (Throwable $e) {
            throw new UserException(sprintf(
                'Unable to parse "created_time_from": %s',
                $e->getMessage(),
            ));
        }

        $openLineageWriter = new OpenLineageWriter(
            $queueClient,
            (new GoogleHttpClientFactory($config))->getClient(),
            $this->getLogger(),
            $createdTimeFrom,
            $config->getOpenLineageEndpoint(),
            $config->getJobNameAsConfig(),
            true,
        );

        try {
            $openLineageWriter->write();
        } catch (GeneratorException $e) {
            throw new UserException($e->getMessage());
        }
    }

    protected function getConfigClass(): string
    {
        return Config::class;
    }

    protected function getConfigDefinitionClass(): string
    {
        return ConfigDefinition::class;
    }
}
