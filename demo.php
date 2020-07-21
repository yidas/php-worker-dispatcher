<?php

// PHP-CLI only
if (strtolower(php_sapi_name()) != 'cli') {
    die("PHP-CLI environment only");
}

// Composer
$composerPath = __DIR__ . '/vendor/autoload.php';
if (!file_exists($composerPath)) {
    die("Composer is not installed, please install Composer.\n");
}
require $composerPath;

/**
 * Demo
 */

use yidas\WorkerDispatcher;

// CLI option
$tasks = isset($argv[1]) ? (int) $argv[1] : ["R4NEJ1", "F5KH83", "..."];

\yidas\WorkerDispatcher::run([
    'debug' => true,
    'workers' => 4,
    'config' => ['uri' => "/v1/resource"],
    'tasks' => $tasks,
    'callbacks' => [
        'process' => function ($config, $workerId, $tasks) {
            // var_dump($tasks);
            echo "The number of tasks in forked process - {$workerId}: " . count($tasks[$workerId - 1]) . "\n";
        },
        'task' => function ($config, $workerId, $task) {
            echo "Forked process - {$workerId}: Request to {$config['uri']} with token {$task}\n";
        },
    ],
]);
