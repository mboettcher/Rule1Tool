<?php

/*
 * Plugin_UserObject
 */

class Plugin_UserObject extends Zend_Controller_Plugin_Abstract
{

    public function __construct()
    {
    }
	public function preDispatch(Zend_Controller_Request_Abstract $request)
	{
	    if (Zend_Registry::get("Zend_Auth")->hasIdentity())
        {
        	$user = new User(Zend_Registry::get("Zend_Auth")->getIdentity()->user_id);
            Zend_Registry::set("UserObject", $user);
        }
        else 
        {
        	$guest = new User();
        	Zend_Registry::set("UserObject", $guest);
        	//niemand eingelogt, deshalb SessionId immer neu generieren
        	Zend_Session::regenerateId();
        }
	}
}