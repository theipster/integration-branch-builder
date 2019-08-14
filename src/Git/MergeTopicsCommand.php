<?php

declare(strict_types=1);

namespace TheIpster\IntegrationBranchBuilder\Git;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MergeTopicsCommand extends Command
{
    use ShellCommandTrait;

    protected static $defaultName = 'git:merge-topics';

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setDescription('Merge multiple topic branches onto an integration branch.')
            ->addArgument('integration', InputArgument::REQUIRED, 'Integration branch to merge topic branches into (e.g. feature/ABC-123-integration)')
            ->addArgument('topic', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'Topic branches to merge (e.g. feature/ABC-123-task-1)');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
    }
}
