<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class testTask2 {

    public function checkRunnable() {
        return true;
    }

    public function doJob($param) {
        sleep(2);
        $success = rand(1,100)>10;
        
        return $success;
    }

}

?>
