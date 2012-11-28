<?php
/**
 * Description of login
 *
 * @author madis
 */
class login {
    //put your code here
    
    public function getContent() {
        $html = file_get_contents('ui/template/login.html');        
        return $html;
    }
}

?>
