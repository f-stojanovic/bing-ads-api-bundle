<?php

namespace Coddict\BingAdsApiBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('bing_ads_api');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
            ->scalarNode('developer_token')->isRequired()->end()
            ->scalarNode('api_environment')->isRequired()->end()
            ->scalarNode('oauth_scope')->isRequired()->end()
            ->scalarNode('oauth_refresh_token_path')->isRequired()->end()
            ->scalarNode('client_id')->isRequired()->end()
            ->scalarNode('client_secret')->isRequired()->end()
            ->integerNode('audience_id')->isRequired()->end()
            ->end();

        return $treeBuilder;
    }
}
