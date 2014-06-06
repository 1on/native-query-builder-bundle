# IntaroJobQueueBundle #

## About ##

## Installation ##

Require the bundle in your composer.json file:

````
{
    "require": {
        "intaro/job-queue-bundle": "dev-master",
    }
}
```

Register the bundle:

```php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        new Intaro\JobQueueBundle\IntaroJobQueueBundle(),
    );
}
```

Install the bundle:

```
$ composer update intaro/job-queue-bundle
```

## Configuration ##

```yaml
intaro_job_queue:
    intervals:
        integration_service: { value: 3600 }
    job_timeout: 60     # timeout for executing job command
    environment: prod
    durable: false
```

## Usage ##

Add producers and consumers to the `old_sound_rabbit_mq` section in your configuration file:

```yaml
old_sound_rabbit_mq:
    producers:
        integration:
            connection:       default
            exchange_options: {name: 'integration', type: direct}
    consumers:
        integration:
            connection:       default
            exchange_options: {name: 'integration', type: direct}
            queue_options:
                name: 'integration_main'
                routing_keys:
                    - 'integration_main'
            callback: job_execute_service
        integration_service:
            connection:       default
            exchange_options: {name: 'integration', type: direct}
            queue_options:
                name: 'integration_service'
                routing_keys:
                    - 'integration_service'
            callback: job_execute_service
```

Initiate cyclic update:

```php
    $jobManager = $container->get('job_queue_manager');
    $jobManager->addJob('acme:integration:main', 'integration_main',
        array('recurring' => true, 'interval' => 'P1D', 'startDate' => new \DateTime('00:00:00'))
        );
    $jobManager->addJob('acme:integration:service', 'integration_service',
        array('recurring' => true, 'intervalCode' => 'integration_service')
        );
```

Every day at 00:00:00 "acme:integration:main" command will be executed and "acme:integration:service" will be execudet every hour.