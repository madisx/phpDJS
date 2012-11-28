<?php

/**
 * Database class for phpDJS
 *
 * @author Madis Kapsi 2012
 */
class db {
    
    private $config;
    private $db;
    public $error = false;
    
    public function __construct($config) {
        $this->config = $config;
        $this->connect();
    }
    
    private function connect() {
        $this->db = mysql_connect($this->config['host'], $this->config['user'], $this->config['pass']) or $this->error=true;
        mysql_select_db($this->config['base'], $this->db) or $this->error=true;
    }
    
    public function getSetting($key) {
        $query = 'SELECT value FROM settings WHERE name = "' . $key . '"';
        $result = mysql_query($query, $this->db) or die('SQL query failed');
        
        $value = null;
        
        if ($result && mysql_num_rows($result)) {
            $v = mysql_fetch_assoc($result);
            $value = $v['value'];
        }
        
        return $value;
    }
    
    public function query($query, $return = true) {
        $data = array('rows' => 0, 'data' => array());
        
        $result = mysql_query($query, $this->db) or die('Query failed' . mysql_error($this->db) . ' q: ' .$query);
        
        if ($return && $result && mysql_num_rows($result)) {
            $data['rows'] = mysql_num_rows($result);
            while ($row = mysql_fetch_assoc($result)) {
                $data['data'][] = $row;
            }
        }
        
        return $data;
    }
    
    public function select($table, $where = '', $fields = '*', $order = '', $limit = '', $join = '') {
                
        if (is_array($fields)) {
            $fields = implode(",", $fields);
        }
        
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }
        
        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }
        
        if ($limit != '') {
            $limit = ' LIMIT ' . $limit;
        }
        
        $query = 'SELECT ' . $fields . ' FROM ' . $table . $join . $where . $order . $limit;
        
        return $this->query($query);
    }
    
    public function insert($table, $data) {
        
        $values = array();
        
        foreach ($data as $k=>$v) {
            $values[] = '`'. $k . '`="' . $v .'"';
        }
        
        $query = 'INSERT INTO ' . $table . ' SET ' . implode(",", $values);
        $this->query($query, FALSE);
    }
    
    public function lastId() {
        return mysql_insert_id($this->db);
    }
    
}

?>
