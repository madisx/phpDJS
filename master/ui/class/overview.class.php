<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of overview
 *
 * @author madis
 */
class overview {
    
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function getContent() {
        $html_sceleton = file_get_contents('ui/template/overview.html');
        
        $html_sceleton = str_replace("%nodeOverview%", $this->getServerStats(), $html_sceleton);
        $html = str_replace("%taskOverview%", $this->getTaskStats(), $html_sceleton);
        
        return $html;
    }
    
    private function getServerStats() {
        $nodes = $this->db->select('server');
        $stats = $this->db->select('stats');
        
        $total = 0.0;
        $cnt = 0;
        foreach ($stats['data'] as $row) {
            if($row['status']==2) {
                $cnt++;
                $total += $row['duration'];
            }
        }
        
        $html = '<tr>
                    <td><a href="?page=listNodes">'.$nodes['rows'].'</a></td>
                    <td>'.round($total/$cnt, 4).'s</td>
                 </tr>';
        
        return $html;
    }
    
    private function getTaskStats() {
        $jobs = $this->db->select('job');
        $stats = $this->db->select('stats');
        
        $counts = array('1'=>0, '2'=>0, '3'=>0);
        
        foreach ($stats['data'] as $row) {
            $counts[$row['status']]++;
        }
        
        $html = '<tr>
                    <td><a href="?page=listJobs">'.$jobs['rows'].'</a></td>
                    <td>'.$stats['rows'].'</td>
                    <td>'.$counts[1].'</td>
                    <td>'.$counts[3].'</td>
                    <td>'.round($counts[2]/$stats['rows']*100).'%</td>
                 </tr>
                ';                
        
        return $html;
    }
    
}

?>
