<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of nodes
 *
 * @author madis
 */
class nodes {

    private $db;
    private $subpage;

    public function __construct($db, $page) {
        $this->db = $db;
        $this->subpage = $page;
        
        if (isset($_POST['saveNode'])) {
            $this->saveNode();
            $this->subpage = 'list';
        }
        if (isset($_REQUEST['action'], $_REQUEST['id'])) {
            switch ($_REQUEST['action']) {
                case 'delete':
                    $this->removeNode($_REQUEST['id']);
                    break;
            }
        
        }
    }

    public function getContent() {
        return $this->subpage == 'add' ? $this->addNodeContent() : $this->listNodesContent();
    }

    private function addNodeContent() {
        $html_sceleton = file_get_contents('ui/template/node.add.html');

        $html = $html_sceleton;
        
        return $html;
    }

    private function listNodesContent() {
        $html_sceleton = file_get_contents('ui/template/node.list.html');
        
        $html = "";
        $nodes = $this->db->select('server');

        if ($nodes['rows'] > 0) {
            foreach ($nodes['data'] as $k => $node) {
                $stats = $this->db->select('stats', 'server_id='.$node['id']);
                
                $html .= '<tr>
                            <td>'.($k+1).'</td>
                            <td>'.$node['ip'].'</td>
                            <td>'.$node['name'].'</td>
                            <td>'.$stats['data'][sizeof($stats['data'])-1]['start_time'].'</td>
                            <td>'.$stats['rows'].'</td>
                            <td><a href="?page=listNodes&action=delete&id='.$node['id'].'" onclick="return confirm(\'Remove node from list?\')"><i class="icon-trash"></i></a></td>
                          </tr>';
                
            }
        }
        
        return str_replace("%nodesList%", $html, $html_sceleton);
    }
    
    private function saveNode() {
        $ip = strval($_POST['nodeIp']);
        $hostname = strval($_POST['nodeHostname']);
        
        $this->db->insert('server', array('ip'=>$ip, 'name'=>$hostname));
    }
    
    private function removeNode($id) {
        $q = 'DELETE FROM server WHERE id='.intval($id);
        $this->db->query($q, false);
    }

}

?>
