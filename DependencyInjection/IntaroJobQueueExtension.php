<?php

namespace Intaro\JobQueueBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class IntaroJobQueueExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('config.yml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $definition = new Definition('Intaro\JobQueueBundle\JobQueue\JobExecuteService');
        $definition->addArgument($container->get('kernel')->getRootDir())
            ->addTag('old_sound_rabbit_mq.base_amqp')
            ->addTag('old_sound_rabbit_mq.consumer')
            ->addMethodCall('setTimeout', $config['job_timeout'])
            ->addMethodCall('setEnvironment', $config['environment'])
            ->addMethodCall('setDurable', $config['durable']);
        $this->container->setDefinition('job_execute_service', $definition);


        $definition = new Definition($config['class']);
        $definition->addArgument($container)
            ->addTag('old_sound_rabbit_mq.base_amqp')
            ->addTag('old_sound_rabbit_mq.consumer');
        $this->container->setDefinition('job_queue_manager', $definition);
    }
}
