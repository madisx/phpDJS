<?php

require_once 'configuration.php';
require_once 'db.class.php';

require_once 'Master.class.php';

global $config;

$database = new db($config['mysql']);
$master = new Master($database, $config['server']);

if (isset($_POST['request'])) {
    $request = json_decode($_POST['request']);

    switch ($request->request) {
        case 'getTask':
            echo $master->getTask();
            break;
        case 'getClass':
            echo $master->getClass($request->className);
            break;
        case 'taskReport':
            $master->taskReport();
            break;
    }
} else {
    include 'ui.php';

    $ui = new ui($database);
    $ui->checkConf();
    $ui->showUI();
}
