<?php
class RssController extends AppController{
 
	var $name = 'Rss';
	var $uses = array('Users.User');
	var $helpers = array('Text');
	var $components = array('RequestHandler');
 
	public function index(){
	    if( $this->RequestHandler->isRss() ){
	        $users = $this->User->find('all', array('limit' => 20, 'order' => 'User.created DESC'));
	        return $this->set(compact('users'));
	    }
	}
	
}
?>