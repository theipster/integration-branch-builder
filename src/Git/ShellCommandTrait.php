<?php

declare(strict_types=1);

namespace TheIpster\IntegrationBranchBuilder\Git;

trait ShellCommandTrait
{
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
