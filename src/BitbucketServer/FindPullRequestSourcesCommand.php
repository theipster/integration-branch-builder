<?php

declare(strict_types=1);

namespace TheIpster\IntegrationBranchBuilder\BitbucketServer;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FindPullRequestSourcesCommand extends Command
{
    protected static $defaultName = 'bitbucket-server:find-pull-request-sources';

    /**
     * @var FindPullRequestSourcesService
     */
    private $service;

    /**
     * @param FindPullRequestSourcesService $service
     */
    public function __construct(FindPullRequestSourcesService $service)
    {
        $this->service = $service;
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setDescription('Given a pull request target branch, find all source branches.')
            ->addArgument('repository-url', InputArgument::REQUIRED, 'Bitbucket repository URL, e.g. "ssh://user@my-bitbucket-instance/project/repo.git"')
            ->addArgument('target-branch', InputArgument::REQUIRED, 'Branch that the pull requests must be targeting')
            ->addArgument('api-auth-header', InputArgument::REQUIRED, 'Bitbucket Server API HTTP Auth header value, e.g. "Bearer {token}"');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Parse inputs
        $repositoryUrl = $input->getArgument('repository-url');
        $targetBranch = $input->getArgument('target-branch');
        $apiAuthHeader = $input->getArgument('api-auth-header');

        // Fetch pull requests
        $branches = $this->service->getBranchesForPullRequestTarget(
            $repositoryUrl,
            $targetBranch,
            $apiAuthHeader
        );

        // Output branch refs
        foreach ($branches as $branch) {
            $output->writeln($branch->getName());
        }
    }
}
