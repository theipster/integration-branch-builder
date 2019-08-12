<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Console\Application;
use TheIpster\IntegrationBranchBuilder\Commands\BuildBitbucketCommand;

$application = new Application();
$application->add(new BuildBitbucketCommand());
$application->run();
