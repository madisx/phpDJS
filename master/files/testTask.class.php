<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class testTask {

    public function checkRunnable() {
        $runnable = rand(1,10)>5;
        return $runnable;
    }

    public function doJob($param) {
        sleep(2);
        $success = rand(1,10)<8;
        return $success;
    }

}

?>
