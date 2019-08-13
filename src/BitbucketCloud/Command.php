<?php

declare(strict_types=1);

namespace TheIpster\IntegrationBranchBuilder\BitbucketCloud;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Command extends SymfonyCommand
{
    protected static $defaultName = 'bitbucketcloud';

    /**
     * @var Finder
     */
    private $finder;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     * @param Finder $finder
     */
    public function __construct(LoggerInterface $logger, Finder $finder)
    {
        $this->logger = $logger;
        $this->finder = $finder;
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setDescription('Builds a new integration branch.')
            ->addArgument('target', InputArgument::REQUIRED, 'Which branch are the pull requests targeting?');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Fetch branches
        $target = $input->getArgument('target');
        $this->logger->info(sprintf('Querying Bitbucket for pull requests targeting %s.', $target));
        $branches = $this->finder->getBranchesForPullRequestTarget($target);

        //
    }
}
