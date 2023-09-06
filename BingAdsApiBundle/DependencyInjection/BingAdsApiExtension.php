<?php

namespace Coddict\BingAdsApiBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader;

class BingAdsApiExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('bing_ads_api.developer_token', $config['developer_token']);
        $container->setParameter('bing_ads_api.api_environment', $config['api_environment']);
        $container->setParameter('bing_ads_api.oauth_scope', $config['oauth_scope']);
        $container->setParameter('bing_ads_api.client_id', $config['client_id']);
        $container->setParameter('bing_ads_api.client_secret', $config['client_secret']);
        $container->setParameter('bing_ads_api.audience_id', $config['audience_id']);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');
    }
}
