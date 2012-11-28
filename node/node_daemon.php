<?php

error_reporting(E_ALL);
require_once "System/Daemon.php";

System_Daemon::setOption("appName", "phpDistributedJobScheduler");
System_Daemon::setOption("authorEmail", "madis@koduleht.net");

System_Daemon::start();
System_Daemon::log(System_Daemon::LOG_INFO, "Daemon: " . System_Daemon::getOption("appName") . " started.");

$terminate = false;

require_once "Node.class.php";
include_once "configuration.php";

global $config;
$Node = new Node($config);
$i = 0;
while (!$terminate) {
    $task = $Node->getTask();
    
    if($task->task_id == NULL) {
        System_Daemon::info('Waiting for suitable task');
        sleep(1);
        continue;
    }
    $jobResult = array();

    try {
        $status = $Node->verifyTaskClass($task->run_class, $task->checksum);

        if (!$status) {
            $Node->getTaskClass($task->run_class);

            $status = $Node->verifyTaskClass($task->run_class, $task->checksum);
            if (!$status)
                throw new Exception('File md5 does not match');
            else {
                $jobResult = array();
                $jobResult['status'] = 301;
                $Node->sendJobStatus($jobResult, $task);
                System_Daemon::info("Class " . $task->run_class . " was updated - daemon needs to be restarted.");
                break;
            }
        }

        $job = new $task->run_class;

        $isRunnable = $job->checkRunnable();
        if ($isRunnable) {
            System_Daemon::info("Task " . ($task->run_class) . " is runnable");
            
            list($startMicro, $startFull) = explode(" ", microtime());
            $startTime = floatval($startFull) + floatval($startMicro);
            $status = $job->doJob($task->param);
            list($endMicro, $endFull) = explode(" ", microtime());
            $endTime = floatval($endFull) + floatval($endMicro);
             
            if ($status) {
                $jobResult['execTime'] = $endTime - $startTime;
                $jobResult['status'] = 200;
                System_Daemon::info("Task " . $task->run_class . " completed");
            }
            else {
                $jobResult['status'] = 400;
                System_Daemon::err("Task " . $task->run_class . " failed");
            }
        }
        else {
            $jobResult['status'] = 300;
            System_Daemon::info("Task not runnable");
        }
    } catch (Exception $e) {
        $jobResult['status'] = 400;
        System_Daemon::err("Exception: " . $e->getMessage());
    }
    
    $Node->sendJobStatus($jobResult, $task);
    
}

System_Daemon::stop();
?>
