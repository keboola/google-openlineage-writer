<?php

declare(strict_types=1);

namespace Keboola\GoogleOpenLineageWriter;

use Keboola\Component\Config\BaseConfigDefinition;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class ConfigDefinition extends BaseConfigDefinition
{
    protected function getParametersDefinition(): ArrayNodeDefinition
    {
        $parametersNode = parent::getParametersDefinition();
        // @formatter:off
        /** @noinspection NullPointerExceptionInspection */
        $parametersNode
            ->children()
                ->scalarNode('openlineage_api_url')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                    ->scalarNode('openlineage_api_endpoint')
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('created_time_from')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->booleanNode('job_name_as_config')
                    ->defaultFalse()
                ->end()
            ->end()
        ;
        // @formatter:on
        return $parametersNode;
    }
}
