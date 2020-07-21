<?php

namespace yidas;

/**
 * Worker Dispatcher
 * 
 * PHP multi-processing task dispatcher with managing workers
 * 
 * @author      Nick Tsai <myintaer@gmail.com>
 * @version     1.0.0
 * @see         https://github.com/yidas/php-worker-dispatcher
 */
class WorkerDispatcher
{
    /**
     * @var integer
     */
    static private $cores = null;

    /**
     * @var integer
     */
    static private $processId = null;

    /**
     * @var array
     */
    static private $processList = [];
    
    /**
     * Run
     *
     * @param array $options
     * @return void
     */
    static public function run($options)
    {
        // Options
        $defaultOptions = [
            'debug' => false,
            'workers' => self::getCores(),
            'config' => null,
            'tasks' => [],
            'callbacks' => [
                'task' => null,
                'process' => null,
            ],
        ];
        $options = array_merge($defaultOptions, $options);

        // Config
        $debug = ($options['debug']) ? true : false; 
        $workers = (int) ($options['workers'] >= 1) ? $options['workers'] : 1;
        $config = $options['config'];
        $tasks = $options['tasks'];
        $callback['task'] = isset($options['callbacks']['task']) && is_callable($options['callbacks']['task']) 
            ? $options['callbacks']['task'] : null;
        $callback['process'] = isset($options['callbacks']['process']) && is_callable($options['callbacks']['process']) 
            ? $options['callbacks']['process'] : null;

        // Check config
        if (!$callback['task'] && !$callback['process']) {
            die("No callback setting.\n");
        }

        // Process task array
        if (!is_array($tasks)) {
            $counts = $tasks;
            $tasks = [];
            // Fill task array with task sequence number
            for ($i=0; $i < (int) $counts ; $i++) { 
                $tasks[] = $i + 1;
            }
        }
        $tasks = empty($tasks) ? null : self::arrayPartition($tasks, $workers);

        self::$processId = getmypid();

        // PCNTL process
        for ($seq=1; $seq <= $workers; $seq++) { 

            $pid = pcntl_fork();

            // Failed to fork process
            if ($pid == -1) {
                die("Could not fork");
            } 
            // Parent process
            else if ($pid) {

                if ($debug) {
                    self::_print("Child process (PID: {$pid}) has been forked.");
                }
                array_push(self::$processList, $pid);
            }
            // Each child process
            else {

                // Callback - Process
                if ($callback['process']) {
                    call_user_func_array($callback['process'], [$config, $seq, $tasks]);
                }

                // Callback - Task
                $taskArrayKey = $seq - 1;
                if ($callback['task'] && isset($tasks[$taskArrayKey])) {
                    
                    // Get task set from tasks array 
                    foreach ($tasks[$taskArrayKey] as $key => $task) {

                        call_user_func_array($callback['task'], [$config, $seq, $task]);
                    }
                }
                // $tasks is false/0 for infinite loop
                else if ($callback['task'] && empty($tasks)) {

                    $task = 1;
                    // Infinite loop
                    while (true) {
                        call_user_func_array($callback['task'], [$config, $seq, $task]);
                        $task++;
                    }
                }
                
                // Stop child process
                die();
                break;
            }
        }

        // Wait for all child processes
        foreach (self::$processList as $key => $pid) {

            pcntl_waitpid($pid, $status);
            if ($debug) {
                self::_print("Child process (PID: {$pid}) done.");
            }
        }
    }

    /**
     * Get the number of processor cores from current computer
     *
     * @return integer
     */
    static public function getCores()
    {
        // Check cache
        if (self::$cores) {
            return self::$cores;
        }
        // Get cores into cache
        $cores = (int) shell_exec("grep -c processor /proc/cpuinfo");
        self::$cores = ($cores) ? $cores : 1;
        return self::$cores;
    }

    /**
     * Print in console
     * 
     * @param string $string
     */
    static private function _print($string)
    {
        echo "{$string}\n";
    }

    /**
     * Partition array
     * 
     * @param Array $list
     * @param int $p
     * @return array
     * @link http://www.php.net/manual/en/function.array-chunk.php#75022
     */
    static public function arrayPartition(Array $list, $p)
    {
        $listlen = count($list);
        $partlen = floor($listlen / $p);
        $partrem = $listlen % $p;
        $partition = array();
        $mark = 0;
        for($px = 0; $px < $p; $px ++) {
            $incr = ($px < $partrem) ? $partlen + 1 : $partlen;
            $partition[$px] = array_slice($list, $mark, $incr);
            $mark += $incr;
        }
        return $partition;
    }
}
