<?php
class ACL_AnalysisShow implements Zend_Acl_Assert_Interface
{
    public function assert(Zend_Acl $acl,
                           Zend_Acl_Role_Interface $role = null,
                           Zend_Acl_Resource_Interface $resource = null,
                           $privilege = null)
    {
        return $this->_canShow(Zend_Registry::get('Zend_Controller_Front'), Zend_Registry::get('UserObject'));
    }

    
    protected function _canShow(Zend_Controller_Front $controller, User $user)
    {
    	$analysis_id = $controller->getRequest()->getParam("AID");
    	
    	$user_id = $user->getUserId();
    	
    	$model = new AnalysisModel();
    	$row = $model->find($analysis_id)->current();
    	if(!$row) //Wenn keine Analyse existiert, dann kann er auch nichts machen, und bekommt so eine ordentliche Fehlermeldung
    		return true;
    		
    	if($row->private == 0 || $row->user_id == $user_id)
    		return true;
    	else 
    		return false;    
    }
}