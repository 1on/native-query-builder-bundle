<?php
namespace Intaro\JobQueueBundle\JobQueue;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;

class JobQueueManager implements ConsumerInterface
{
    protected $container;

    private $defaultInterval = 'P1D';
    private $intervals = array();


    public function __construct($container)
    {
        $this->container = $container;
    }


    /**
     * Возможные параметры:
     *  recurring - повторяющаяся задача
     *  startDate - время запуска задачи
     *  route - routing key для задачи
     *  intervalCode - код по которому ищется задержка между выполнениями задачи
     *  interval - задержка между выполнениями задачи
     */
    public function addJob($command, $producerName, array $parameters = array())
    {
        $defaultParameters = array('recurring' => false, 'startDate' => new \DateTime());
        $parameters = array_merge($defaultParameters, $parameters);

        $message = array(
            'payload'   => $command,
            'producer'  => $producerName,
            'recurring'  => $parameters['recurring'],
            'startDate' => $parameters['startDate']->format('c')
            );

        if (isset($parameters['route']))
            $message['route'] = $parameters['route'];
        if (isset($parameters['intervalCode']))
            $message['intervalCode'] = $parameters['intervalCode'];
        if (isset($parameters['interval']))
            $message['interval'] = 'PT' . $parameters['interval'] . 'S';

        $this->sheduleJob($message);
    }


    public function execute(AMQPMessage $msg)
    {
        if (!$msg->has('application_headers') || !isset($msg->get('application_headers')['x-death']))
        {
            sleep(1);
            return false;
        }

        $message = json_decode($msg->body, true);

        /* Отправка задачи на исполнение */
        $producer = $this->container->get('old_sound_rabbit_mq.' . $message['producer'] . '_producer');
        if (isset($message['route']))
            $producer->publish($message['payload'], $message['route']);
        else
            $producer->publish($message['payload']);

        /* Установка задачи с задержкой */
        if ($message['recurring'])
            $this->sheduleJob($message);
        return true;
    }



    public function clearJobsShedule()
    {
        $consumer = $this->container->get('old_sound_rabbit_mq.jobs_consumer');
        $consumer->purge();
        $consumer = $this->container->get('old_sound_rabbit_mq.job_shedule_consumer');
        $consumer->purge();
        return true;
    }


    protected function sheduleJob(array $message)
    {
        $producer = $this->container->get('old_sound_rabbit_mq.job_shedule_producer');

        $interval = new \DateInterval($this->defaultInterval);
        if (isset($message['interval']))
        {
            $interval = new \DateInterval($message['interval']);
        }
        else
        {
            $intervals = $this->getIntervals();

            if (isset($message['route']))
                $routeInterval = $message['producer'] . '_producer.' . $message['route'] . '_route';
            else
                $routeInterval = $message['producer'] . '_producer';

            if (isset($message['intervalCode']) && isset($intervals[$message['intervalCode']]))
                $interval = $intervals[$message['intervalCode']];
            elseif (isset($intervals[$routeInterval]))
                $interval = $intervals[$routeInterval];
        }

        if (!$message['recurring'])
            $interval = null;
        $startDate = new \DateTime($message['startDate']);
        $expiration = $this->getExpiration($startDate, $interval);
        $message['startDate'] = $startDate->format('c');
        $producer->publish(json_encode($message), 'job_shedule', array('expiration' => $expiration));
    }


    protected function getExpiration(\DateTime &$startDate, \DateInterval $interval = null)
    {
        $now = new \DateTime();

        if (is_null($interval))
        {
            if ($startDate < $now)
                $startDate = $now;
        }
        else
        {
            while (true)
            {
                 $startDate = $startDate->add($interval);

                 if ($startDate > $now)
                    break;
            }
        }
        $expiration = $startDate->getTimestamp() - $now->getTimestamp();
        if ($expiration <= 0)
            $expiration = 10;
        $expiration *= 1000;
        return $expiration;
    }


    protected function getIntervals()
    {
        return $this->intervals;
    }

    protected function setIntervals(array $intervals)
    {
        $this->intervals = $intervals;

        return $this;
    }
}