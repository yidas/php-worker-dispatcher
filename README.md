<p align="center">
    <a href="https://www.php.net/" target="_blank">
        <img src="https://www.php.net/images/logos/php-logo-bigger.png" height="60px">
    </a>
    <h1 align="center">PHP Worker Dispatcher</h1>
    <br>
</p>

PHP multi-processing task dispatcher with managing workers

[![Latest Stable Version](https://poser.pugx.org/yidas/worker-dispatcher/v/stable?format=flat-square)](https://packagist.org/packages/yidas/worker-dispatcher)
[![License](https://poser.pugx.org/yidas/worker-dispatcher/license?format=flat-square)](https://packagist.org/packages/yidas/worker-dispatcher)


Features
--------

- ***Multi-Processing** implementation on native PHP-CLI*

- ***Tasks Dispatching** to each worker process*

- ***Elegant Interface** for setup and use*

---

OUTLINE
-------

- [Demonstration](#demonstration)
- [Introduction](#introduction)
- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)
    - [Option](#option)
        - [callbacks.process](#callbacksprocess)
        - [callbacks.task](#callbackstask)

---

DEMONSTRATION
-------------

Use multi-processing to dispatch tasks with generating workers based on CPU cores:

```php
\yidas\WorkerDispatcher::run([
    'tasks' => ["R4NEJ1", "F5KH83", "..."],
    'callbacks' => [
        // The callback is for each forked process with decentralized tasks
        'task' => function ($config, $workderId, $task) {
            // $task is one of the `tasks` assigned to each worker, ex. "F5KH83" for $workderId is 2
            $token = $task;
            $result = file_get_contents("https://example/v1/register-by-token/{$token}");
        },
    ],
]);
```

Use multi-processing to digest jobs from queue:

```php
\yidas\WorkerDispatcher::run([
    'tasks' => false,
    'callbacks' => [
        // The callback is for each forked process
        'process' => function ($config, $workderId, $task) {
            // Get and handle each job from queue in inifite loop (You need to define your own function)
            while (true) {
                $result = handleOneJobFromQueue();
                if ($result === null) {
                    break;
                }
            }
        },
    ],
]);
```

---

INTRODUCTION
------------

This library is implemented by PHP PCNTL control, which provides a main PHP-CLI to fork multiple child processes to share tasks, and even can use for high concurrency application with infinite loop setting.

<img src="https://raw.githubusercontent.com/yidas/php-worker-dispatcher/master/img/introduction.png" />

> Since PHP has no shared variables or queue mechanism natively, if you don’t have an external job queue, this library provides a task average dispatcher to simply solve the core distributed processing problem.

---

REQUIREMENTS
------------

This library requires the following:

- PHP [PCNTL](https://www.php.net/manual/en/pcntl.installation.php)
- PHP CLI 5.4.0+

---

INSTALLATION
------------

Run Composer in your project:

    composer require yidas/worker-dispatcher ~1.0.0
    
Then you could use the class after Composer is loaded on your PHP project:

```php
require __DIR__ . '/vendor/autoload.php';

use yidas\WorkerDispatcher;
```

---

USAGE
-----

Calling the `run()` method statically with options as argument, WorkerDispatcher will start to dispatch tasks (if any), and then fork the number of workers according to the environment or settings, and wait for all forked processes to complete or terminate the main process.

The setting example with all options is as following:

```php
\yidas\WorkerDispatcher::run([
    'debug' => true,
    'workers' => 4,
    'config' => ['uri' => "/v1/resource"],
    'tasks' => ["R4NEJ1", "F5KH83", "..."],
    'callbacks' => [
        'process' => function ($config, $workerId, $tasks) {
            echo "The number of tasks in forked process - {$workerId}: " . count($tasks[$workerId - 1]) . "\n";
        },
        'task' => function ($config, $workerId, $task) {
            echo "Forked process - {$workerId}: Request to {$config['uri']} with token {$task}\n";
        },
    ],
]);
```

### Options

|Option            |Type     |Deafult      |Description|
|:--               |:--      |:--          |:--        |
|debug             |boolean  |false        |Debug mode |
|workers           |integer  |(auto)       |The number of workers(processes) to fork. <br>(The default is the same as the number of CPU cores)|
|config            |multitype|null         |The custom variable used to bring in the callback function|
|tasks             |multitype|array        |For dispatching to each forked process. *<br>- Array: Each value of array will be dispatched to all forked processes. <br>- Integer: The number of loops dispatched to all forked processes. <br>- false: Perform finite loop.*|
|[callbacks.process](#callbacksprocess) |callable |nul          |Callback function called after each forked process is created|
|[callbacks.task](#callbackstask)       |callable |nul          |Callback function called in each task loop of each forked process|


#### callbacks.process

Callback function called after each forked process is created

```php
function (multitype $config, integer $workerId, array $tasks)
```

|Argument          |Type     |Deafult      |Description|
|:--               |:--      |:--          |:--        |
|$config            |multitype|null         |The custom variable used to bring in the callback function|
|$workerId          |integer  |(auto)       |The sequence number of the worker(processes) in current function (Start from 1)|
|$tasks             |multitype|array        |Tasks array list for the worker(processes) in current function|

#### callbacks.task

Callback function called in each task loop of each forked process

```php
function (multitype $config, integer $workerId, multitype $task)
```

|Argument          |Type     |Deafult      |Description|
|:--               |:--      |:--          |:--        |
|$config            |multitype|null         |The custom variable used to bring in the callback function|
|$workerId          |integer  |(auto)       |The sequence number of the worker(processes) in current function (Start from 1)|
|$tasks             |multitype|array        |The value of each tasks array list for each worker(processes) in current function|



