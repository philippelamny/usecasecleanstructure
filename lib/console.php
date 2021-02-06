<?php

require_once __DIR__ . '/vendor/autoload.php';

$application = new \Symfony\Component\Console\Application();

$application->add(new \Vietanywhere\UseCase\CreateCleanStructure\Commands\UseCaseCreateStructure());

$application->run();