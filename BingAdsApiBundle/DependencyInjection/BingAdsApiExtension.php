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

        $container->setParameter('developer_token', $config['developer_token']);
        $container->setParameter('api_environment', $config['api_environment']);
        $container->setParameter('oauth_scope', $config['oauth_scope']);
        $container->setParameter('oauth_refresh_token_path', $config['oauth_refresh_token_path']);
        $container->setParameter('client_id', $config['client_id']);
        $container->setParameter('client_secret', $config['client_secret']);
        $container->setParameter('audience_id', $config['audience_id']);
        $container->setParameter('upload_directory', $config['upload_directory']);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');
    }
}
