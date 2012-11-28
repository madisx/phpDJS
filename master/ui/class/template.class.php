<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of template
 *
 * @author madis
 */
class template {
    
    private $menus = array('overview' => "Overview",
                           'listNodes' => "List of Nodes",
                           'addNode' => "Add a Node",
                           'listJobs' => "List of Jobs",
                           'addJob' => "Add a Job",
                           
        );
    
    public function drawPage($page, $content) {
        $main_html = file_get_contents('ui/template/main.html');
        
        $main_html = str_replace("%page%", ucfirst($page), $main_html);
        
        $main_html = str_replace("%menu%", $this->generateMenu($page), $main_html);
        $main_html = str_replace("%pageContent%", $content, $main_html);
        
        echo $main_html;
    }
    
    private function generateMenu($page) {
        $html = '<li class="nav-header">Operations</li>';
        
        if ($page!='login') {
            
            foreach ($this->menus as $pg => $text) {
                $html .= '<li class="'.($pg==$page?'active':'').'"><a href="?page='.$pg.'">'.$text.'</a></li>';
            }
            
            $html .= '<li class="divider"></li>';
            $html .= '<li><a href="?page=login&action=logout">Logout</a></li>';
        }
        else {
            $html .= '<li class="active"><a href="?page=login">Login</a></li>';
        }
        
        return $html;
    }
    
}

?>
