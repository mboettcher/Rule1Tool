<?php
class ACL_AuthUserAccess implements Zend_Acl_Assert_Interface
{
	const USERNAME = "username";
	const USERID = "UID";

	const WATCHLISTID = "WID"; 
	const PORTFOLIOID = "PID"; 
	
	protected $_request_params = self::USERNAME;
	
	public function __construct($requestparams)
	{
		$this->_request_params = $requestparams;
	}
	
    public function assert(Zend_Acl $acl,
                           Zend_Acl_Role_Interface $role = null,
                           Zend_Acl_Resource_Interface $resource = null,
                           $privilege = null)
    {
        return $this->_canEdit(Zend_Registry::get('Zend_Controller_Front'), Zend_Registry::get('UserObject'));
    }

    
    protected function _canEdit(Zend_Controller_Front $controller, User $user)
    {
    	if(!is_array($this->_request_params))
    	{
    		$this->_request_params = array($this->_request_params);
    	}
    	
    	$valid = true;
    	
    	foreach ($this->_request_params as $request_param)
    	{
    		$request_param_value = $controller->getRequest()->getParam($request_param);
    		
    		if($request_param == self::USERNAME)
	    	{
	    		$needle = $user->getNickname();
	    		if($request_param_value != $needle)
	    			$valid = false;
	    	}
	    	elseif($request_param == self::USERID)
	    	{
	    		$needle = $user->getId();
	    		if($request_param_value != $needle)
	    			$valid = false;
	    	}
	    	elseif ($request_param == self::WATCHLISTID)
	    	{
	    		$w = new Watchlist($request_param_value);
	    		if($w->getOwnerId() != $user->getId())
	    			$valid = false;
	    	}
	    	elseif ($request_param == self::PORTFOLIOID)
	    	{
	    		$p = new Portfolio($request_param_value);
	    		if($p->getUserId() != $user->getId())
	    			$valid = false;
	    	}
	    	else 
	    		$valid = false;
    	}
    	
    	if(count($this->_request_params) == 0)
    		$valid = false;

    	//echo $request_param." x ".$needle;exit;
    		
    	
   		return $valid;    
    }
}