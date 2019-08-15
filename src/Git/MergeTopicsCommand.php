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
        $this->setDescription('Merge multiple topic branches onto the current branch.')
            ->addArgument('topic', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'Topic branches to merge (e.g. feature/ABC-123-task-1)');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Must be in a git repo.
        if (!file_exists('.git')) {
            throw new Exception('No .git directory found.');
        }

        // Fetch branches
        $topicBranches = $input->getArgument('topic');

        // Merge each topic branch
        foreach ($topicBranches as $topicBranch) {
            $this->mergeTopicBranch($topicBranch, $output);
        }
    }

    /**
     * @throws Exception
     *
     * @param string $topicBranch
     * @param OutputInterface $output
     *
     * @return void
     */
    private function mergeTopicBranch(string $topicBranch, OutputInterface $output): void
    {
        try {
            // Run the merge
            $this->runShellCommand(
                'git -c merge.conflictStyle=diff3 merge -s recursive -X patience --rerere-autoupdate --no-ff %s',
                [$topicBranch],
                sprintf('Could not merge topic "%s".', $topicBranch)
            );

            // Done!
            $output->writeln(sprintf('Merged topic branch "%s" cleanly.', $topicBranch));

        // Errors?
        } catch (Exception $exception) {

            // See if any leftover changes
            $unstagedFiles = $this->runShellCommand(
                'git diff --name-only',
                [],
                'Could not determine unstaged changes.'
            );

            // Only staged changes (from rerere)?
            if (count($unstagedFiles) == 0) {

                // Commit rerere's resolution.
                $this->runShellCommand('git commit --no-edit', [], 'Could not commit rerere resolution.');
                $output->writeln(sprintf('Merged topic branch "%s" with rerere resolution.', $topicBranch));

            // Unstaged (i.e. unresolvable) changes?
            } else {

                // Output debug detail.
                $outputVerbosity = $output->getVerbosity();
                if ($outputVerbosity >= OutputInterface::VERBOSITY_VERBOSE) {
                    $output->writeln('Unstaged changes filelist:');
                    foreach ($unstagedFiles as $unstagedFile) {
                        $output->writeln(sprintf(' - %s', $unstagedFile));
                    }

                    // Output diff?
                    if ($outputVerbosity >= OutputInterface::VERBOSITY_VERY_VERBOSE) {
                        $output->writeln('Unstaged changes diff:');
                        $diff = $this->runShellCommand('git diff -U5', [], 'Could not generate diff.');
                        foreach ($diff as $diffLine) {
                            $output->writeln($diffLine);
                        }
                    }
                }

                // Bail.
                throw $exception;
            }
        }
    }
}
