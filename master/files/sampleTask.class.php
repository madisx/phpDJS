<?php

/**
 * Description of sampleTask
 *
 * @author madis
 */
class sampleTask {
        
    private $conn;
    
    public function checkRunnable() {
        
        if(!function_exists('mysql_connect')) {
            System_Daemon::log(System_Daemon::LOG_ERR,'php MySQL extension not available');
            return false;
            
        }
        
        $required_dir = '/tmp/';
        if(!is_readable($required_dir) || !is_writable($required_dir)) {
            System_Daemon::log(System_Daemon::LOG_ERR, $required_dir . ' is not a dir or not writable');
            return false;
        }
        
        $this->conn = @mysql_connect('localhost', 'test', 'test');
        
        if(!is_resource($this->conn)) {
            System_Daemon::log(System_Daemon::LOG_ERR, 'Mysql Server not accessible, connection1');
            return false;
        }
        
//        $link2 = @mysql_connect('localhost', 'tester', 'test');
//        
//        if(!is_resource($link2)) {
//            System_Daemon::log(System_Daemon::LOG_ERR, 'Mysql Server not accessible, connection2');
//            return false;
//        }
        
        $res = @shell_exec("ffmpeg -v 2>&1");
        if(strstr($res, "ffmpeg version")===false) {
            System_Daemon::log(System_Daemon::LOG_ERR, 'ffmpeg not installed');
            return false;
        }
        
        return true;
    }

    public function doJob($param) {
        mysql_select_db('test', $this->conn);
        
        $result = mysql_query('select * from test.table1', $this->conn);
        $data = false;
        
        if($result && mysql_num_rows($result)>0) {
            $data = mysql_fetch_assoc($result);
        }
        else return false;
        
        $target_file = '/tmp/'.$data['col2'].'.txt';
        file_put_contents($target_file, print_r($data, true));
        
        if(file_exists($target_file) && filesize($target_file)) return true;
        
        return false;
    }
}

?>
