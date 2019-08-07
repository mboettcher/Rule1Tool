<?php
class ACL_LocalIP implements Zend_Acl_Assert_Interface
{
    public function assert(Zend_Acl $acl,
                           Zend_Acl_Role_Interface $role = null,
                           Zend_Acl_Resource_Interface $resource = null,
                           $privilege = null)
    {
        return $this->_isLocalIP($_SERVER['REMOTE_ADDR']);
    }

    protected function _isLocalIP($ip)
    {
        if($ip == "127.0.0.1" || $ip == "localhost" || $ip == "::1" || $ip == "81.169.179.210")
        	return true;
        else
        	return false;
    }
}