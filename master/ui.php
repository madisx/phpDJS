<?php

require 'ui/class/template.class.php';

/**
 * Description of ui
 *
 * @author madis
 */
class ui {
    
    private $db;
    private $template;
    
    public function __construct($db) {
        $this->db = $db;
        $this->template = new template();
    }
    
    public function showUI() {
        session_start();
        $page = @$_GET['page'];
        
        if (isset($_GET['action']) && $_GET['action'] == 'logout') {
            unset($_SESSION['login']);
        }
        
        if (!$this->validateUser()) {
            $page = 'login';
        }
        else if ($page == 'login') $page = 'overview';
                
        $content = 'Page not found';
        
        switch ($page) {
            case 'login':
                require_once 'ui/class/login.class.php';
                $contentPage = new login();
                break;
            case 'listNodes':
                require_once 'ui/class/nodes.class.php';
                $contentPage = new nodes($this->db, 'list');
                break;
            case 'addNode':
                require_once 'ui/class/nodes.class.php';
                $contentPage = new nodes($this->db, 'add');
                break;
            case 'listJobs':
                require_once 'ui/class/jobs.class.php';
                $contentPage = new jobs($this->db, 'list');
                break;
            case 'addJob':
                require_once 'ui/class/jobs.class.php';
                $contentPage = new jobs($this->db, 'add');
                break;
            case 'viewJob':
                require_once 'ui/class/jobs.class.php';
                $contentPage = new jobs($this->db, 'view');
                break;
            default:
                require_once 'ui/class/overview.class.php';
                $contentPage = new overview($this->db);                
                break;
        }
        
        $content = $contentPage->getContent();
        
        $this->template->drawPage($page, $content);
    }
    
    private function validateUser() {
        
        if (isset($_SESSION['login']) || isset($_POST['login'])) {
            $user = isset($_SESSION['login']) ? $_SESSION['login']['user'] : mysql_real_escape_string($_POST['user']);
            $pass = isset($_SESSION['login']) ? $_SESSION['login']['pass'] : mysql_real_escape_string($_POST['pass']);
            
            $login = $this->db->select('user', 'username="'.$user.'" AND password="'.md5($pass).'"');
            
            if ($login['rows'] > 0) {
                if (!isset($_SESSION['login'])) {
                    $_SESSION['login'] = array('user' => $user, 'pass'=>$pass);
                }
                return true;
            }
        }
        return false;
    }
    
    public function checkConf() {
        if ($this->db->error) {
            echo "Sql configuration error";
            exit(1);
        }
        
        if(!is_writable('files/')) {
            echo "Class files folder not writable (./files/)";
            exit(1);
        }
    }
    
}

?>
