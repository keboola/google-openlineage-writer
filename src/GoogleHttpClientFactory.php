<?php

declare(strict_types=1);

namespace Keboola\GoogleOpenLineageWriter;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use Keboola\Component\UserException;
use Keboola\Google\ClientBundle\Google\RestApi;

class GoogleHttpClientFactory
{
    protected const MAX_RETRIES = 7;

    public function __construct(private Config $config)
    {
    }

    public function getClient(): Client
    {
        /** @var string $OAuthApiData */
        $OAuthApiData = $this->config->getOAuthApiData();

        /** @var array<string, string> $tokenData */
        $tokenData = json_decode($OAuthApiData, true);
        if (!isset($tokenData['access_token'], $tokenData['refresh_token'])) {
            throw new UserException('The token data are broken. Please try to reauthorize.');
        }

        $restApi = new RestApi(
            $this->config->getOAuthApiAppKey(),
            $this->config->getOAuthApiAppSecret(),
            $tokenData['access_token'],
            $tokenData['refresh_token'],
        );
        $handlerStack = HandlerStack::create(new CurlHandler());

        $handlerStack->push(RestApi::createRetryMiddleware(
            $restApi->createRetryDecider(self::MAX_RETRIES),
            $restApi->createRetryCallback(),
        ));

        return new Client([
            'base_uri' => $this->config->getOpenLineageUrl(),
            'headers' => ['Authorization' => 'Bearer ' . $restApi->getAccessToken()],
            'handler' => $handlerStack,
        ]);
    }
}
