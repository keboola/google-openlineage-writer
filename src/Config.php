<?php

declare(strict_types=1);

namespace Keboola\GoogleOpenLineageWriter;

use Keboola\Component\Config\BaseConfig;

class Config extends BaseConfig
{
    public function getOpenLineageUrl(): string
    {
        return $this->getStringValue(['parameters', 'openlineage_api_url']);
    }

    public function getCreatedTimeFrom(): string
    {
        return $this->getStringValue(['parameters', 'created_time_from']);
    }

    public function getJobNameAsConfig(): bool
    {
        return (bool) $this->getValue(['parameters', 'job_name_as_config']);
    }

    public function getOpenLineageEndpoint(): string
    {
        return $this->getStringValue(['parameters', 'openlineage_api_endpoint'], '/api/v1/lineage');
    }
}
