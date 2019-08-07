<?php
class ACL_GroupThreadReplyEdit implements Zend_Acl_Assert_Interface
{
    public function assert(Zend_Acl $acl,
                           Zend_Acl_Role_Interface $role = null,
                           Zend_Acl_Resource_Interface $resource = null,
                           $privilege = null)
    {
        return $this->_canEdit(Zend_Registry::get('Zend_Controller_Front'), Zend_Registry::get('UserObject'));
    }

    
    protected function _canEdit(Zend_Controller_Front $controller, User $user)
    {
    	$reply_id = $controller->getRequest()->getParam("REPLYID");
    	
    	$user_id = $user->getUserId();
    	
    	$replyModel = new GruppenThreadRepliesModel();
    	$row = $replyModel->find($reply_id)->current();
    	if($row->writer_id == $user_id)
    		return true;
    	else 
    		return false;    
    }
}