<?php
class SitemapsController extends AppController{
 
	var $name = 'Sitemaps';
	var $uses = array('Users.User');
	var $helpers = array('Time');
	//var $helpers = array('Text');
	var $components = array('RequestHandler');
 
	function index (){	
		//prevent xml validation errors caused by sql log
	    Configure::write('debug', 0);
		$this->User->recursive = -1;
		$this->set('users', $this->User->find('all'));
	}
	
}
?>