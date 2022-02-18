<?php

use DI\Container;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Enjoys\Dotenv\Dotenv;
use EnjoysCMS\Core\Components\Helpers\HelpersBase;


$loader = require_once __DIR__ . "/../vendor/autoload.php";


try {
    $dotenv = new Dotenv(__DIR__);
    $dotenv->loadEnv();
} catch (Exception $e) {
    echo 'ENV Error: ' . $e->getMessage();
    exit;
}

//var_dump($_ENV['DB_DSN']);
/** @var Container $container */
$container = include __DIR__ . '/container.php';
HelpersBase::setContainer($container);