<?php

/*
 * Plugin_Auth
 */

class Plugin_Auth extends Zend_Controller_Plugin_Abstract
{
    private $_auth;
    private $_acl;
    
    private $_noauth = array('module' => null,
                             'controller' => 'auth',
                             'action' => 'login');
                            
    private $_noacl = array('module' => null,
                            'controller' => 'error',
                            'action' => 'privileges');
	
    public function __construct($auth, $acl)
    {
        $this->_auth = $auth;
        $this->_acl = $acl;
    }
	public function preDispatch(Zend_Controller_Request_Abstract $request)
	{
		$controller = strtolower($request->controller);
	    $action = strtolower($request->action);
	    $language = strtolower($request->language);
	    $module = $request->module;
	    
	    //manueller CLEAR
	   // Zend_Auth::getInstance()->clearIdentity(); 
	    
	    if (Zend_Registry::get("UserObject") != null)
        {
            $role = Zend_Registry::get("UserObject")->getRole();
        }
        else
        {
            $role = 'guest';
        }

        if (!$this->_acl->has($controller)) //Falsche Resourcen abfangen
        {
            $controller = null;
        }

	    //Prüfe ob NICHT berechtigt die Seite zu betreten
	    if(!$this->_acl->isAllowed($role, $controller, $action))
	    {

	    	if (!$this->_auth->hasIdentity() || Zend_Registry::get('systemstatus') != "ONLINE")
	    	{
	    		//Wenn noch nicht eingeloggt, dann SESSION_ID immer neu generieren
	    		Zend_Session::regenerateId();
	    		//Login
	    		$this->_request->setModuleName($this->_noauth['module']);
            	$this->_request->setControllerName($this->_noauth['controller']);
           		$this->_request->setActionName($this->_noauth['action']);
           		$this->_request->setParam("fromAuth", true);
	    	}
	    	else{
	    		$this->_request->setModuleName($this->_noacl['module']);
            	$this->_request->setControllerName($this->_noacl['controller']);
           		$this->_request->setActionName($this->_noacl['action']);
	    	}
	    	
	    }
	}
}