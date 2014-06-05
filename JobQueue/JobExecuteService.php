<?php
namespace Intaro\JobQueueBundle\JobQueue;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Process\Process;

class JobExecuteService implements ConsumerInterface
{
    protected $kernelRootDir;

    private $timeout = null;
    private $environment = 'prod';
    private $durable;

    public function __construct($kernelRootDir)
    {
        $this->kernelRootDir = $kernelRootDir;
    }

    /**
     * @access public
     * @return boolean
     */
    public function execute(AMQPMessage $msg)
    {
        $consolePath = $this->kernelRootDir . '/console';
        if (!file_exists($consolePath))
            return false;

        $cmd = $consolePath . ' ' . $msg->body . ' --env=' . $this->environment;
        $process = new Process($cmd);
        $process->setTimeout($this->timeout);
        $process->run();

        if (!$process->isSuccessful())
        {
            $this->logger->error($process->getErrorOutput());
            throw new \RuntimeException($process->getErrorOutput());

            if ($this->durable)
                return false;
        }

        return true;
    }

    protected function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

    protected function setEnvironment($environment)
    {
        $this->environment = $environment;
    }

    protected function setDurable($durable)
    {
        $this->durable = $durable;
    }
}