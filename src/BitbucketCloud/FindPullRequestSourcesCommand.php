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
            ->addArgument('targetBranch', InputArgument::REQUIRED, 'Which branch are the pull requests targeting?');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Fetch branches
        $targetBranch = $input->getArgument('targetBranch');
        $branches = $this->service->getBranchesForPullRequestTarget($targetBranch);

        // Output branch refs
        foreach ($branches as $branch) {
            $output->writeln($branch->getName());
        }
    }
}
