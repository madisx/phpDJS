<?php

/**
 * phpDJS Master server main class
 *
 * @author Madis Kapsi
 */
class Master {

    private $database;
    private $config;
    private $node;

    const JOB_REGULAR = 1;
    const JOB_CRON = 2;

    public function __construct($database, $config) {
        $this->database = $database;
        $this->config = $config;
    }

    public function getTask() {
        $node = $_SERVER['REMOTE_ADDR'];

        if ($this->validateNode($node)) {
            $this->node = $this->getNodeId($node);

            $task_cron = $this->getCronTask();
            if ($task_cron != NULL) {
                return json_encode($task_cron);
            }

            $task_regular = $this->getRegularTask();
            if ($task_regular != NULL) {
                return json_encode($task_regular);
            }
        }

        return json_encode(array('task_id' => NULL, 'error' => 'No tasks'));
    }

    public function getClass($class) {
        $ip = $_SERVER['REMOTE_ADDR'];
        if ($this->validateNode($ip)) {
            return json_encode(array('file' => base64_encode(file_get_contents($this->config['class_files'] . $class . '.class.php'))));
        }
        return json_encode(array('file' => NULL));
    }

    private function getClassChecksum($class) {
        return md5_file($this->config['class_files'] . $class . '.class.php');
    }

    public function taskReport() {
        $request = json_decode($_POST['request']);

        $task = $request->task_id;
        $node = $this->getNodeId($_SERVER['REMOTE_ADDR']);
        $status = $request->result->status;

        if ($status > 200)
            $status = 3;
        else
            $status = 2;

        if ($status == 2) {
            $duration = $request->result->execTime;
            $query = 'UPDATE stats SET `status`=' . $status . ', `end_time`=NOW(), `duration`="' . $duration . '" WHERE `status`=1 AND `job_id`=' . $task . ' AND `server_id`=' . $node;
        }
        else
            $query = 'UPDATE stats SET `status`=' . $status . ' WHERE `status`=1 AND `job_id`=' . $task . ' AND `server_id`=' . $node;


        $this->database->query($query, false);
    }

    private function validateNode($ip) {
        $allow_run = false;

        if ($this->config['allow_unknown']) {
            $allow_run = true;

            if ($this->config['auto_add_unknown']) {
                $this->database->insert('server', array('ip' => $ip, 'name' => $_SERVER['REMOTE_HOST']));
            }
        } else {
            $server = $this->database->select('server', 'ip="' . $ip . '"');
            if ($server['rows'] > 0) {
                $allow_run = true;
            }
        }

        return $allow_run;
    }

    private function getCronTask() {
        $m = date('i');
        $h = date('H');
        $dm = date('j');
        $mo = date('n');
        $dw = date('N');
        //time to match
        $ttm = array(
            'minute' => $m,
            'hour' => $h,
            'dom' => $dm,
            'month' => $mo,
            'dow' => $dw,
        );

        $where = 'type=' . Master::JOB_CRON . ' 
                    AND (minute IS NULL OR minute="' . $m . '") 
                        AND (hour IS NULL OR hour="' . $h . '") 
                        AND (dom IS NULL OR dom="' . $dm . '") 
                        AND (month IS NULL OR month="' . $mo . '") 
                        AND (dow IS NULL OR dow="' . $dw . '")';

        $tasks = $this->database->select('schedule', $where);

        if ($tasks['rows'] > 0) {
            foreach ($tasks['data'] as $task) {
                if ($this->getTaskAvailable($task['job_id'], Master::JOB_CRON)) {
                    $class = str_replace(".class.php", "", $this->getTaskClassName($task['job_id']));

                    $this->database->insert('stats', array('job_id' => $task['job_id'], 'server_id' => $this->node, 'status' => '1'));

                    return array('task_id' => $task['job_id'],
                        'run_class' => $class,
                        'checksum' => $this->getClassChecksum($class));
                }
            }
        }

        $where = 'type=' . Master::JOB_CRON . '
            AND (minute LIKE "*/%" OR hour LIKE "*/%" OR dom LIKE "*/%" OR month LIKE "*/%" OR dow LIKE "*/%")';

        $tasks = $this->database->select('schedule', $where);

        if ($tasks['rows'] > 0) {
            foreach ($tasks['data'] as $task) {
                if ($this->getTaskAvailable($task['job_id'], Master::JOB_CRON)) {
                    if ($this->matchTaskTime($task, $ttm)) {
                        $class = str_replace(".class.php", "", $this->getTaskClassName($task['job_id']));

                        $this->database->insert('stats', array('job_id' => $task['job_id'], 'server_id' => $this->node, 'status' => '1'));

                        return array('task_id' => $task['job_id'],
                            'run_class' => $class,
                            'checksum' => $this->getClassChecksum($class));
                    }
                }
            }
        }

        return NULL;
    }

    private function getRegularTask() {
        $tasks = $this->database->select('schedule', 'type=' . Master::JOB_REGULAR);

        if ($tasks['rows'] > 0) {
            foreach ($tasks['data'] as $task) {
                if ($this->getTaskAvailable($task['job_id'])) {
                    $class = str_replace(".class.php", "", $this->getTaskClassName($task['job_id']));

                    $this->database->insert('stats', array('job_id' => $task['job_id'], 'server_id' => $this->node, 'status' => '1'));

                    return array('task_id' => $task['job_id'],
                        'run_class' => $class,
                        'checksum' => $this->getClassChecksum($class));
                }
            }
        }
        error_log('no available task');
        return NULL;
    }

    private function getTaskAvailable($task_id, $type = Master::JOB_REGULAR) {
        error_log('checking: '.$task_id);
        $where = 'job_id="' . $task_id . '"';
        $param = $this->database->select('job_parameters', $where);

        $max_parallel = 1;
        $overall_cooldown = 0;
        $same_node_cooldown = 0;
        $require_rotation = false;
error_log('1');
        if ($param['rows'] > 0) {
            $max_parallel = $param['data'][0]['max_parallel'];
            $overall_cooldown = $param['data'][0]['global_cooldown'];
            $same_node_cooldown = $param['data'][0]['server_cooldown'];
            $require_rotation = $param['data'][0]['server_rotation_required'] ? true : false;
        }
error_log('2');
        $where = 'job_id="' . $task_id . '" AND `status`=1';

        if ($type != Master::JOB_REGULAR) {
            $where = 'job_id=' . $task_id . ' AND (`status`=1 OR (`status`=2 AND `end_time` LIKE "' . date('Y-m-d H:i') . ':%") )';
        }

        $tasks = $this->database->select('stats', $where);
error_log('3');
        if ($tasks['rows'] >= $max_parallel)
            return false;

        $last_run = $this->getLastRunDetails($task_id);
error_log('4');
        //if there is no history of task being run, then no point in checking cooldowns and rotation
        if ($last_run['rows'] == 0)
            return true;
        $last_run = $last_run['data'][0];
error_log('5');
        if ($require_rotation) {
            if($last_run['server_id'] == $this->node) return false;
        }
error_log('6');
        if ($overall_cooldown != 0) {
            $ttc = strtotime($last_run['start_time']);
            if ($last_run['end_time'] != NULL) {
                $ttc = strtotime($last_run['end_time']);
            }

            $now = strtotime("now");

            $diff = $now - $ttc;

            if ($diff < $overall_cooldown)
                return false;
        }
error_log('7');
        if ($same_node_cooldown != 0) {
            $same_server_run = $this->getLastRunDetails($task_id, $this->node);
            if ($same_server_run['rows'] > 0) {
                $same_server_run = $same_server_run['data'][0];

                $ttc = strtotime($same_server_run['start_time']);
                if ($same_server_run['end_time'] != NULL) {
                    $ttc = strtotime($same_server_run['end_time']);
                }

                $now = strtotime("now");

                $diff = $now - $ttc;

                if ($diff < $same_node_cooldown)
                    return false;
            }
        }
error_log('8');
        return true;
    }

    private function getLastRunDetails($task_id, $server = 0) {
        $where = "job_id=" . $task_id;
        $order = 'end_time DESC';
        $limit = "1";

        if ($server != 0) {
            $where .= " AND server_id=".$server;
        }

        $stats = $this->database->select('stats', $where, '*', $order, $limit);

        return $stats;
    }

    private function matchTaskTime($task, $time) {

        foreach ($time as $k => $v) {
            if ($task[$k] != NULL) {
                if ($task[$k] !== intval($task[$k])) {

                    $repl_dow = str_replace("*/", "", $task[$k]);
                    if ($time[$k] % $repl_dow != 0) {
                        return false;
                    }
                } else {
                    if ($task[$k] != $time[$k]) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    private function getNodeId($ip) {
        $id = 0;

        $server = $this->database->select('server', 'ip="' . $ip . '"');
        if ($server['rows'] > 0) {
            $id = $server['data'][0]['id'];
        }

        return $id;
    }

    private function getTaskClassName($id) {
        $name = '';

        $task = $this->database->select('job', 'id=' . $id);

        if ($task['rows'] > 0) {
            $name = $task['data'][0]['class'];
        }

        return $name;
    }

}

?>
