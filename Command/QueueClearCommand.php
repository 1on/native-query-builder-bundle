<?php
namespace Intaro\JobQueueBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\Query\ResultSetMapping;

class QueueClearCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('job-queue:clear')
            ->setDescription('Clears job queue.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $kernel     = $this->getApplication()->getKernel();
        $container  = $kernel->getContainer();
        $container->get('job_manager')->clearJobsShedule();

        $output->writeln('<info>Job queue cleared.</info>');
    }
}
