<?php

declare(strict_types=1);

namespace Keboola\GoogleOpenLineageWriter\Functional;

use Keboola\DatadirTests\DatadirTestCase;

class DatadirTest extends DatadirTestCase
{
    protected function setUp(): void
    {
        $credentialsData = [
            'access_token' => getenv('ACCESS_TOKEN'),
            'refresh_token' => getenv('REFRESH_TOKEN'),
        ];

        putenv(sprintf(
            'CREDENTIALS_DATA=%s',
            json_encode($credentialsData),
        ));

        parent::setUp();
    }
}
