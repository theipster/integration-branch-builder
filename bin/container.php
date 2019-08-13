<?php

declare(strict_types=1);

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\DependencyInjection\AddConsoleCommandPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

// Create the container
$containerBuilder = new ContainerBuilder();

// Load config into container
$loader = new YamlFileLoader($containerBuilder, new FileLocator(__DIR__));
$loader->load('../config/services.yaml');

// Autowire commands
$containerBuilder->addCompilerPass(new AddConsoleCommandPass());

// Compile
$containerBuilder->compile();

// Return container
return $containerBuilder;
