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
            ->addArgument('projectKey', InputArgument::REQUIRED, 'Which Bitbucket project?')
            ->addArgument('repositorySlug', InputArgument::REQUIRED, 'Which Bitbucket repository?')
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

        // Fetch pull requests
        $branches = $this->service->getBranchesForPullRequestTarget(
            $projectKey,
            $repositorySlug,
            $targetBranch
        );

        // Output branch refs
        foreach ($branches as $branch) {
            $output->writeln($branch->getName());
        }
    }
}
