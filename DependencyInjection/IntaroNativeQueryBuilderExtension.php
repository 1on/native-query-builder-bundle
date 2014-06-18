<?php

namespace Intaro\NativeQueryBuilderBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Intaro\NativeQueryBuilderBundle\Builder\EntityManager;

class IntaroNativeQueryBuilderExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        EntityManager::setCacheTime($config['cache_time']);
    }

    public function prepend(ContainerBuilder $container)
    {
        $config = array(
            'orm' => array(
                'entity_managers' => array(
                    'default' => array(
                        'default_repository_class' => 'Intaro\NativeQueryBuilderBundle\Builder\EntityRepository',
                    )
                )
            )
        );

        $container->prependExtensionConfig('doctrine.orm', $config);
    }
}
