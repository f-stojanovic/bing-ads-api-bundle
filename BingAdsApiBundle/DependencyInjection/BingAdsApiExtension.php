<?php

namespace Coddict\BingAdsApiBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
class BingAdsApiExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('bing_ads_api.developer_token', $config['developer_token']);
        $container->setParameter('bing_ads_api.api_environment', $config['api_environment']);
        $container->setParameter('bing_ads_api.oauth_scope', $config['oauth_scope']);
        $container->setParameter('bing_ads_api.oauth_refresh_token_path', $config['oauth_refresh_token_path']);
        $container->setParameter('bing_ads_api.client_id', $config['client_id']);
        $container->setParameter('bing_ads_api.client_secret', $config['client_secret']);
        $container->setParameter('bing_ads_api.audience_id', $config['audience_id']);
        $container->setParameter('bing_ads_api.upload_directory', $config['upload_directory']);
    }
}