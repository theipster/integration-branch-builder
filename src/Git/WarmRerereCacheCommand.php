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
    protected static $defaultName = 'git:warm-rerere-cache';

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setDescription('Warm the git rerere cache by replaying merge conflict resolutions.')
            ->addArgument('baselineBranch', InputArgument::REQUIRED, 'Branch to begin replaying resolutions from (e.g. develop)')
            ->addArgument('resolvedBranch', InputArgument::REQUIRED, 'Branch to stop replaying resolutions up to (e.g. feature/ABC-123)')
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
        if ($input->getOption('clear-cache')) {
            $this->runShellCommand('rm -rf .git/rr-cache', [], 'Could not clear git rerere cache.');
        }

        // Enable Git rerere.
        $this->runShellCommand('mkdir -p .git/rr-cache', [], 'Could not enable git rerere.');

        // Fetch branches
        $baselineBranch = $input->getArgument('baselineBranch');
        $resolvedBranch = $input->getArgument('resolvedBranch');

        // Get revision between them
        $revList = $this->runShellCommand(
            'git rev-list --parents %s..%s 2>/dev/null',
            [
                $baselineBranch,
                $resolvedBranch,
            ],
            sprintf(
                'Could not get rev-list for %s..%s.',
                $baselineBranch,
                $resolvedBranch
            )
        );
        if ($revList === null) {

            // No merges, nothing to do.
            return 0;
        }

        // For each commit (reverse chronological order)
        $foundMerge = false;
        foreach ($revList as $hashes) {
            $hashes = explode(' ', $hashes, 3);

            // Only process merges
            if (count($hashes) > 2) {
                $foundMerge = true;

                // Checkout the from commit.
                $this->runShellCommand(
                    'git checkout --quiet %s^0',
                    [
                        $hashes[1],
                    ],
                    'Could not checkout merge origin.'
                );

                // (Re)merge the next commit.
                $mergeResult = $this->runShellCommand(
                    'git -c merge.conflictStyle=diff3 merge -s recursive -X patience %s',
                    [
                        $hashes[2],
                    ],
                    'Could not merge next commit.'
                );
                if ($mergeResult === null) {

                    // Train rerere.
                    $this->runShellCommand('git rerere', [], 'Unable to store current rerere state');
                    $this->runShellCommand('git checkout --quiet %s -- .', [$hashes[0]], 'Unable to check out merge resolution');
                    $this->runShellCommand('git rerere', [], 'Unable to store rerere resolution');
                    $this->runShellCommand('git reset --quiet --hard', [], 'Unable to reset branch state');
                }
            } else {

                // Stop once the first chain of merge commits stops
                if ($foundMerge) {
                    break;
                }
            }
        }
    }

    /**
     * @throws Exception
     *
     * @param string $command Command with placeholders
     * @param string[] $args Placeholders
     * @param string $errorMsg Error message if command fails
     *
     * @return string[] Output of command
     */
    private function runShellCommand(string $command, array $args, string $errorMsg): array
    {
        $args = array_map('escapeshellarg', $args);
        $command = sprintf($command, ...$args);
        exec($command, $output, $result);
        if ($result === 0) {
            return $output;
        } else {
            throw new Exception($errorMsg);
        }
    }
}
