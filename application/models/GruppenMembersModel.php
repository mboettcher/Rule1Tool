<?php

/**
 * GruppenMembersModel
 *  
 * @author martinboettcher
 * @version 
 */

require_once 'Zend/Db/Table/Abstract.php';

class GruppenMembersModel extends Zend_Db_Table_Abstract {
	/**
	 * The default table name 
	 */
	protected $_name = 'gruppen_members';
	protected $_primary = array("group_id", "user_id");
	
    public function insert(array $data)
    {
        // Einen Zeitstempel hinzufügen
        if (empty($data['date_join'])) {
            $data['date_join'] = time();
        }
        
        //ACHTUNG
        //Wenn bereits in DB, dann update (undelete)
        $rows = $this->find($data["group_id"], $data["user_id"]);
        if(count($rows) > 0)
        {
        	//update
        	$row = $rows->current();
        	if ($row->delete_by != null && $row->date_delete != null)
        	{
        		$row->date_delete = null;
	        	$row->delete_by = null;
	        	$row->date_join = time();
	        	$row->mtype_id = $data["mtype_id"];
	        	//Speichern in DB
	        	return $row->save();	
        	}
        	else 
        		return false;
	        	
        }
        else
        {
        	//insert
        	return parent::insert($data);
        }
    }

    public function delete($select)
    {
    	$data = array("delete_by" => Zend_Registry::get('Zend_Auth')->getIdentity()->user_id, "date_delete" => time());
		return parent::update($data, $select);
    }
    
}
