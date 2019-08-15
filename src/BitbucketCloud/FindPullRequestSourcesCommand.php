<?php

declare(strict_types=1);

namespace TheIpster\IntegrationBranchBuilder\BitbucketCloud;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FindPullRequestSourcesCommand extends Command
{
    protected static $defaultName = 'bitbucket-cloud:find-pull-request-sources';

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
            ->addArgument('workspace', InputArgument::REQUIRED, 'Bitbucket workspace')
            ->addArgument('repository-slug', InputArgument::REQUIRED, 'Bitbucket repository slug')
            ->addArgument('target-branch', InputArgument::REQUIRED, 'Branch that the pull requests must be targeting')
            ->addArgument('api-auth-header', InputArgument::REQUIRED, 'Bitbucket Cloud API HTTP Auth header value, e.g. "Basic {token}"');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Get inputs
        $workspace = $input->getArgument('workspace');
        $repositorySlug = $input->getArgument('repository-slug');
        $targetBranch = $input->getArgument('target-branch');
        $authHeaderValue = $input->getArgument('api-auth-header');

        // Fetch branches
        $branches = $this->service->getBranchesForPullRequestTarget(
            $workspace,
            $repositorySlug,
            $targetBranch,
            $authHeaderValue
        );

        // Output branch refs
        foreach ($branches as $branch) {
            $output->writeln($branch->getName());
        }
    }
}
