<?php

namespace Intaro\JobQueueBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;

class IntaroJobQueueExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $definition = new Definition('Intaro\JobQueueBundle\JobQueue\JobExecuteService');
        $definition->addArgument($container->getParameter('kernel.root_dir'))
            ->addTag('old_sound_rabbit_mq.base_amqp')
            ->addTag('old_sound_rabbit_mq.consumer')
            ->addMethodCall('setTimeout', array($config['job_timeout']))
            ->addMethodCall('setEnvironment', array($config['environment']))
            ->addMethodCall('setDurable', array($config['durable']));
        $container->setDefinition('job_execute_service', $definition);


        $definition = new Definition($config['class']);
        $definition->addArgument(new Reference('service_container'))
            ->addTag('old_sound_rabbit_mq.base_amqp')
            ->addTag('old_sound_rabbit_mq.consumer');
        $container->setDefinition('job_queue_manager', $definition);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('config.yml');
    }

    public function prepend(ContainerBuilder $container)
    {
        $config = array(
            'producers' => array(
                'job_shedule' => array(
                    'connection' => 'default',
                    'exchange_options' => array('name' => 'job_queue', 'type' => 'direct'),
                    'queue_options' => array(
                        'name' => 'job_shedule',
                        'arguments' => array(
                            'x-dead-letter-exchange' => array('S', 'job_queue'),
                            'x-dead-letter-routing-key' => array('S', 'job_queue')
                            ),
                        'routing_keys' => array('job_shedule')
                        )
                    )
                ),
            'consumers' => array(
                'job_shedule' => array(
                    'connection' => 'default',
                    'exchange_options' => array('name' => 'job_queue', 'type' => 'direct'),
                    'queue_options' => array(
                        'name' => 'job_shedule',
                        'arguments' => array(
                            'x-dead-letter-exchange' => array('S', 'job_queue'),
                            'x-dead-letter-routing-key' => array('S', 'job_queue')
                            ),
                        'routing_keys' => array('job_shedule')
                        ),
                    'callback' => 'job_queue_manager'
                    ),
                'job_queue' => array(
                    'connection' => 'default',
                    'exchange_options' => array('name' => 'job_queue', 'type' => 'direct'),
                    'queue_options' => array(
                        'name' => 'job_queue',
                        'routing_keys' => array('job_queue')
                        ),
                    'callback' => 'job_queue_manager'
                    )
                )
            );

        $container->prependExtensionConfig('old_sound_rabbit_mq', $config);
    }
}
