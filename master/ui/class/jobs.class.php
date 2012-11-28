<?php

/**
 * Description of jobs
 *
 * @author madis
 */
class jobs {
    
    private $db;
    private $subpage;
    private $uploadMessage = "";
    
    public function __construct($db, $page) {
        $this->db = $db;
        $this->subpage = $page;
        
        if (isset($_POST['saveJob'])) {
            $this->saveJob();
        }
        if (isset($_POST['saveFile'])) {
            $this->uploadFile();
        }
        if (isset($_POST['saveSettings'])) {
            $this->saveSettings();
        }
        
        if (isset($_REQUEST['action'], $_REQUEST['id'])) {
            switch ($_REQUEST['action']) {
                case 'delete':
                    $this->removeJob($_REQUEST['id']);
                    break;
                
                case 'edit':
                    $_SESSION['Job']['jobId'] = intval($_REQUEST['id']);
                    $this->subpage = 'schedule';
                    break;
                
                case 'upload':
                    $_SESSION['Job']['jobId'] = intval($_REQUEST['id']);
                    $this->subpage = 'upload';
                    break;
            }
        
        }
    }
    
    public function getContent() {
        switch ($this->subpage) {
            case 'add':
                return $this->addJobContent();
                break;
            case 'list':
                return $this->listJobsContent();
                break;
            case 'upload':
                return $this->uploadClassContent();
                break;
            case 'schedule':
                return $this->jobScheduleContent();
                break;
            case 'view':
                return $this->viewJobContent();
                break;
        }
    }
    
    private function addJobContent() {
        $html_sceleton = file_get_contents('ui/template/job.add.html');
        
        $html = $html_sceleton;
        
        return $html;
    }
    
    private function uploadClassContent() {
        $html_sceleton = file_get_contents('ui/template/job.add.upload.html');
        
        $html_sceleton = str_replace("%MESSAGE%", $this->uploadMessage, $html_sceleton);
        $html = str_replace("%JOB_ID%", $_SESSION['Job']['jobId'], $html_sceleton);
        
        return $html;
    }
    
    private function jobScheduleContent() {
        $html_sceleton = file_get_contents('ui/template/job.schedule.html');
        
        $maxParallelDropdown = $this->generateMaxParallelDropdown();
        
        $html_sceleton = str_replace("%MAX_PARALLEL_DROPDOWN%", $maxParallelDropdown, $html_sceleton);
        $html = str_replace("%JOB_ID%", $_SESSION['Job']['jobId'], $html_sceleton);
        return $html;
    }
    
    private function viewJobContent() {
        $job = intval($_REQUEST['id']);
        
        $q = 'SELECT * FROM job AS j LEFT JOIN schedule AS s ON j.id=s.job_id LEFT JOIN job_parameters AS jp ON j.id=jp.job_id WHERE j.id='.$job;
        $data = $this->db->query($q);
        
        if ($data['rows']) {
           $job = $data['data'][0];
           
           if ($job['type']==1) {
               $html_sceleton = file_get_contents('ui/template/job.view.regular.html');
               
               $settings = '<tr>
                              <td>'.$job['max_parallel'].'</td>                   
                              <td>'.$job['server_cooldown'].' s</td>
                              <td>'.$job['global_cooldown'].' s</td>
                              <td><i class="'.($job['require_server_rotation']?'icon-plus':'icon-minus').'"></i></td>
                          </tr>';
               
               $html_sceleton = str_replace("%SETTINGS%", $settings, $html_sceleton);
               $html = str_replace("%JOB_NAME%", $job['name'], $html_sceleton);
               
               return $html;
           }
           else if ($job['type']==2) {
               $html_sceleton = file_get_contents('ui/template/job.view.cron.html');
               
               $timing = '<tr>
                              <td>'.($job['minute']?$job['minute']:'*').'</td>                   
                              <td>'.($job['hour']?$job['hour']:'*').'</td>
                              <td>'.($job['dom']?$job['dom']:'*').'</td>
                              <td>'.($job['month']?$job['month']:'*').'</td>
                              <td>'.($job['dow']?$job['dow']:'*').'</td>
                          </tr>';
               
               $html_sceleton = str_replace("%TIMING%", $timing, $html_sceleton);
               $html = str_replace("%JOB_NAME%", $job['name'], $html_sceleton);
               
               return $html;
           }
          
        }
        else {
            return "Invalid job id";
        }
        
    }
    
    private function listJobsContent() {
        $html_sceleton = file_get_contents('ui/template/job.list.html');
        
        $html = "";
        
        $jobs = $this->db->select('job');
        
        if($jobs['rows'] > 0) {
            foreach ($jobs['data'] as $k=>$job) {
                $stats = $this->db->select('stats', 'job_id='.$job['id']);
                
                $info = array('1'=>0, '2'=>0, '3'=>0, 'total'=> 0);
                
                foreach($stats['data'] as $stat) {
                    $info[$stat['status']]++;
                    if ($stat['status']==2) $info['total'] += $stat['duration'];
                }
                
                $html .= '<tr>
                            <td>'.($k+1).'</td>
                            <td><a href="?page=viewJob&id='.$job['id'].'">'.$job['name'].'</a></td>
                            <td>'.$job['class'].'</td>
                            <td>'.$stats['rows'].'</td>
                            <td>'.$info['2'].'</td>
                            <td>'.$info['3'].'</td>
                            <td>'.round($info['total']/$info['2'],4).'s</td>
                            <td>
                                <a href="?page=listJobs&action=edit&id='.$job['id'].'" title="Edit settings" ><i class="icon-pencil"></i></a>
                                <a href="?page=listJobs&action=upload&id='.$job['id'].'" title="Upload class file" ><i class="icon-arrow-up"></i></a>
                                <a href="?page=listJobs&action=delete&id='.$job['id'].'" title="Delete" onclick="return confirm(\'Remove job from list?\')"><i class="icon-trash"></i></a>
                            </td>
                          </tr>';
            }
        }
        
        return str_replace("%jobsList%", $html, $html_sceleton);        
    }
    
    private function generateMaxParallelDropdown() {
        $html = '<select name="jobMaxParallel" class="span2">%OPTIONS%</select>';
        
        $data = $this->db->query('SELECT count(*) AS rowcnt FROM server');
        
        $options = "";
        if($data['rows']!=0) {
            $i = 1;
            $options .= '<option value="'.$i.'">'.$i++.'</option>';
            
            $rows = $data['data'][0]['rowcnt'];
            
            while(--$rows > 0) {
                $options .= '<option value="'.$i.'">'.$i++.'</option>';
            }
            
        }
        
        $html = str_replace("%OPTIONS%", $options, $html);
        
        return $html;
    }
    
    private function saveJob() {
        $name = strval($_POST['jobName']);
        $class = strval($_POST['jobClass']);
        
        $classExists = $this->db->select('job','class="'.$class.'"');
        
        if ($classExists['rows']==0) {
            $this->db->insert('job', array('name' => $name, 'class' => $class));
            $newJob = $this->db->lastId();

            $_SESSION['Job']['jobId'] = $newJob;
        }
        else {
            $_SESSION['Job']['jobId'] = $classExists['data'][0]['id'];
        }
        
        if(isset($_POST['jobClassUpload']) || !file_exists('files/'.$class)) {
            $this->subpage = 'upload';
        }
        else {
            $this->subpage = 'schedule';
        }
    }
    
    private function uploadFile() {
        
        if($_POST['jobId']==$_SESSION['Job']['jobId']) {
            $job = $this->db->select('job', 'id='.$_SESSION['Job']['jobId']);
            $job = $job['data'][0];
            
            if (file_exists('files/'.$job['class'])) {
                unlink('files/'.$job['class']);
            }
            $status = move_uploaded_file($_FILES["jobClassFile"]["tmp_name"], 'files/'.$job['class']);
        }
        
        if($status)
            $this->subpage = 'schedule';
        else {
            $this->uploadMessage = 'Uploading failed - no premissions';
            $this->subpage = 'upload';
        }
    }
    
    private function saveSettings() {
                
        if ($_POST['jobType'] == 'regular') {
            $q = 'INSERT INTO schedule SET job_id='.intval($_POST['jobId']).', type=1, frequency=1 ON DUPLICATE KEY UPDATE type=1, frequency=1';
            $this->db->query($q, false);
            
            $q = 'INSERT INTO job_parameters SET job_id='.intval($_POST['jobId']).', max_parallel='.$_POST['jobMaxParallel'].', server_cooldown='.$_POST['jobCooldownServer'].',global_cooldown='.$_POST['jobCooldown'].',server_rotation_required='.(isset($_POST['jobServerRotate'])?1:0).' 
                    ON DUPLICATE KEY UPDATE max_parallel='.$_POST['jobMaxParallel'].', server_cooldown='.$_POST['jobCooldownServer'].',global_cooldown='.$_POST['jobCooldown'].',server_rotation_required='.(isset($_POST['jobServerRotate'])?1:0);
            
            $this->db->query($q, false);
        }
        else if ($_POST['jobType'] == 'cron') {
            $q = 'DELETE FROM job_parameters WHERE job_id='.intval($_POST['jobId']);
            $this->db->query($q, false);
            
            $m = !isset($_POST['jobMinute']) || $_POST['jobMinute']=='*' ? 'NULL' : '"'.strval($_POST['jobMinute']).'"';
            $h = !isset($_POST['jobHour']) || $_POST['jobHour']=='*' ? 'NULL' : '"'.strval($_POST['jobHour']).'"';
            $dom = !isset($_POST['jobDOM']) || $_POST['jobDOM']=='*' ? 'NULL' : '"'.strval($_POST['jobDOM']).'"';
            $mon = !isset($_POST['jobMon']) || $_POST['jobMon']=='*' ? 'NULL' : '"'.strval($_POST['jobMon']).'"';
            $dow = !isset($_POST['jobDOW']) || $_POST['jobDOW']=='*' ? 'NULL' : '"'.strval($_POST['jobDOW']).'"';
            
            $q = 'INSERT INTO schedule SET job_id='.intval($_POST['jobId']).', type=2, frequency=NULL, minute='.$m.', hour='.$h.', dom='.$dom.', month='.$mon.', dow='.$dow.' 
                ON DUPLICATE KEY UPDATE type=2, frequency=NULL, minute='.$m.', hour='.$h.', dom='.$dom.', month='.$mon.', dow='.$dow.'';
            $this->db->query($q, false);
        }
        
        $this->subpage = 'list';
        unset($_SESSION['Job']);
    }
    
    private function removeJob($id) {
        $q = 'DELETE FROM job WHERE id='.intval($id);
        $this->db->query($q, false);
    }
}

?>
