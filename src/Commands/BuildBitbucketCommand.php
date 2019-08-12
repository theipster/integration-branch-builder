<?php

declare(strict_types=1);

namespace TheIpster\IntegrationBranchBuilder\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BuildBitbucketCommand extends Command
{
    protected static $defaultName = 'build:bitbucket';

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setDescription('Builds a new integration branch.');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

    }
}
