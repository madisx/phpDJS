<?php

class Node {

    private $config = array();

    public function __construct($config) {
        $this->config = $config;
    }

    public function getTask() {
        if (!isset($this->config['masterIP']))
            throw new Exception('Master server not set in config');
        if (!isset($this->config['masterURI']))
            $this->config['masterURI'] = '/';

        $request = array('request' => 'getTask');
        $requestJSON = json_encode($request);

        $result = $this->JSONPost($requestJSON);
        $task = json_decode($result);

        return $task;
    }

    public function verifyTaskClass($className, $checksum) {
        if (!isset($this->config['scriptPath']))
            throw new Exception('Local scripts path not set');
        if (!is_dir($this->config['scriptPath']))
            throw new Exception('Local scripts path does not exist');

        $filePath = $this->config['scriptPath'] . $className . '.class.php';

        if (!file_exists($filePath))
            return false;

        $localChecksum = md5_file($filePath);

        if ($checksum == $localChecksum) {
            include_once $filePath;
            return true;
        }

        return false;
    }

    public function getTaskClass($className) {
        $request = array('request' => 'getClass', 'className' => $className);
        $requestJSON = json_encode($request);

        $result = $this->JSONPost($requestJSON);

        $class = json_decode($result);
        $filePath = $this->config['scriptPath'] . $className . '.class.php';
        if (file_exists($filePath))
            unlink($filePath);

        file_put_contents($filePath, base64_decode($class->file));
    }

    public function sendJobStatus($status, $task) {
        $request = array('request' => 'taskReport', 'task_id'=>$task->task_id, 'result' => $status);
        $requestJSON = json_encode($request);

        $this->JSONPost($requestJSON);
    }
    
    private function JSONPost($json) {
        $requestUrl = $this->config['masterIP'] . $this->config['masterURI'];

        $ch = curl_init($requestUrl);
        $options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query(array("request" => $json)),
        );

        curl_setopt_array($ch, $options);
        return curl_exec($ch);
    }

}
