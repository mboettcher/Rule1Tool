<?php

/**
 * MailController
 * 
 * @author
 * @version 
 */

require_once 'Zend/Controller/Action.php';

class MailController extends Zend_Controller_Action {

	public function registerAction() {
		$this->_helper->layout->setLayout('mail');
		$this->_helper->viewRenderer('registerNewUser');
		
		$this->view->data = array("nickname" => "meNick", "user_id" => 1, "activationkey" => 123);
	}
	public function inviteAction() {
		$this->_helper->layout->setLayout('mail');
		$this->_helper->viewRenderer('invitation');
		
		$this->view->data = array("empfaenger" => "test@rule1tool.com", "absender_name" => Zend_Registry::get("UserObject")->getDisplayname(), "invitation_key" => 1234);
	}
	public function resetpwAction() {
		$this->_helper->layout->setLayout('mail');
		$this->_helper->viewRenderer('reset-user-password');
		
		$this->view->data = array("password" => "DeInPasSwOrD", "username" => Zend_Registry::get("UserObject")->getNickname());
	}
	

}
?>

