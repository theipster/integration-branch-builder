<?php

declare(strict_types=1);

namespace TheIpster\IntegrationBranchBuilder\Git;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class WarmRerereCacheCommand extends Command
{
    use ShellCommandTrait;

    protected static $defaultName = 'git:warm-rerere-cache';

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setDescription('Warm the git rerere cache by replaying merge conflict resolutions between two existing branches.')
            ->addArgument('from', InputArgument::REQUIRED, 'Branch to begin replaying resolutions from (e.g. feature/ABC-123-target)')
            ->addArgument('to', InputArgument::REQUIRED, 'Branch to stop replaying resolutions up to (e.g. feature/ABC-123-integration)')
            ->addOption('clear-cache', null, InputOption::VALUE_NONE, 'Clear existing git rerere cache');
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

        // Clear existing Git rerere cache?
        if (file_exists('.git/rr-cache') && $input->getOption('clear-cache')) {
            $this->runShellCommand('rm -rf .git/rr-cache', [], 'Could not clear git rerere cache.');
            $output->writeln('Cleared git rerere cache.');
        }

        // Enable Git rerere.
        if (!file_exists('.git/rr-cache')) {
            $this->runShellCommand('mkdir -p .git/rr-cache', [], 'Could not enable git rerere.');
            $output->writeln('Initialized git rerere cache.');
        }

        // Fetch branches
        $fromBranch = $input->getArgument('from');
        $toBranch = $input->getArgument('to');

        // Get revision between them
        $revList = $this->runShellCommand(
            'git rev-list --parents %s..%s 2>/dev/null',
            [
                $fromBranch,
                $toBranch,
            ],
            sprintf(
                'Could not get rev-list for %s..%s.',
                $fromBranch,
                $toBranch
            )
        );
        $output->writeln(sprintf('Found %u commits.', count($revList)));

        // No merges? Nothing to do.
        if ($revList === null) {
            return 0;
        }

        // For each commit (reverse chronological order)
        $foundMerge = false;
        foreach ($revList as $hashes) {
            $hashes = explode(' ', $hashes, 3);

            // Only process merges
            if (count($hashes) > 2) {
                $foundMerge = true;
                $this->trainRerere($hashes[0], $hashes[1], $hashes[2]);
                $output->writeln(
                    sprintf(
                        'Trained rerere on %s..%s using %s.',
                        $hashes[1],
                        $hashes[2],
                        $hashes[0]
                    )
                );
            } else {

                // Stop once the first chain of merge commits stops
                if ($foundMerge) {
                    break;
                }
            }
        }
    }

    /**
     * @param string $mergeHash
     * @param string $fromHash
     * @param string $toHash
     *
     * @return void
     */
    private function trainRerere(string $mergeHash, string $fromHash, string $toHash): void
    {
        // Checkout the from commit.
        $this->runShellCommand('git checkout --quiet %s^0', [$fromHash], 'Could not checkout merge origin.');

        // (Re)merge the next commit.
        $mergeResult = $this->runShellCommand(
            'git -c merge.conflictStyle=diff3 merge -s recursive -X patience %s',
            [$toHash],
            'Could not merge next commit.'
        );
        if ($mergeResult === null) {

            // Train rerere.
            $this->runShellCommand('git rerere', [], 'Unable to store current rerere state');
            $this->runShellCommand('git checkout --quiet %s -- .', [$mergeHash], 'Unable to check out merge resolution');
            $this->runShellCommand('git rerere', [], 'Unable to store rerere resolution');
            $this->runShellCommand('git reset --quiet --hard', [], 'Unable to reset branch state');
        }
    }
}
