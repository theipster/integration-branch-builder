<?php

declare(strict_types=1);

namespace TheIpster\IntegrationBranchBuilder\BitbucketServer;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Command extends SymfonyCommand
{
    protected static $defaultName = 'bitbucketserver';

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
            ->addArgument('projectKey', InputArgument::REQUIRED, 'Which Bitbucket project (e.g. "WS")?')
            ->addArgument('repositorySlug', InputArgument::REQUIRED, 'Which Bitbucket repository (e.g. "servicelayer")?')
            ->addArgument('targetBranch', InputArgument::REQUIRED, 'Which branch are the pull requests targeting?');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Parse inputs
        $projectKey = $input->getArgument('projectKey');
        $repositorySlug = $input->getArgument('repositorySlug');
        $targetBranch = $input->getArgument('targetBranch');

        // Log info message
        $infoMsg = sprintf(
            'Querying Bitbucket (%s/%s) for pull requests targeting %s.',
            $projectKey,
            $repositorySlug,
            $targetBranch
        );
        $this->logger->info($infoMsg);

        // Fetch pull requests
        $branches = $this->finder->getBranchesForPullRequestTarget(
            $projectKey,
            $repositorySlug,
            $targetBranch
        );

        //
    }
}
