<?php
namespace Intaro\JobQueueBundle\JobQueue;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;

class JobQueueManager implements ConsumerInterface
{
    protected $container;

    private $defaultPeriod = 'P1D';


    public function __construct($container)
    {
        $this->container = $container;
    }


    /**
     * Возможные параметры:
     *  recurring - повторяющаяся задача
     *  startDate - время запуска задачи
     *  route - routing key для задачи
     *  periodCode - код по которому ищется задержка между выполнениями задачи
     *  period - задержка между выполнениями задачи
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
        if (isset($parameters['periodCode']))
            $message['periodCode'] = $parameters['periodCode'];
        if (isset($parameters['period']))
            $message['period'] = 'PT' . $parameters['period'] . 'S';

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

        $interval = new \DateInterval($this->defaultPeriod);
        if (isset($message['period']))
        {
            $interval = new \DateInterval($message['period']);
        }
        else
        {
            $periods = $this->getPeriods();
            $routePeriod = $message['producer'] . '_producer.' . $message['route'] . '_route';

            if (isset($periods[$message['periodCode']]))
                $interval = $periods[$message['periodCode']];
            elseif (isset($message['route']) && isset($periods[$routePeriod]))
                $interval = $periods[$message['producer'] . '_producer'];
            elseif (isset($periods[$message['producer'] . '_producer']))
                $interval = $periods[$message['producer'] . '_producer'];
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


    protected function getPeriods()
    {
        return array();
    }
}