<?php

/**
 * GruppenThreadsModel
 *  
 * @author mb
 * @version 
 */
	
require_once 'Zend/Db/Table/Abstract.php';

class GruppenThreadsModel extends Zend_Db_Table_Abstract
{
	/**
	 * The default table name 
	 */
    protected $_name = 'gruppen_threads';
    protected $_primary = "thread_id";
    
   protected $_referenceMap  = array(
        'Gruppe' => array(
            'columns'           => 'group_id',
            'refTableClass'     => 'GruppenModel',
            'refColumns'        => 'id'
        ),
        'Type' => array(
            'columns'           => 'type',
            'refTableClass'     => 'GruppenThreadTypsModel',
            'refColumns'        => 'type_id'
        )
    );
    
    protected $_dependentTables = array('GruppenThreadRepliesModel', 'GruppenThreadsModel');
    
    public function insert(array $data)
    {
        // Einen Zeitstempel hinzufügen
        if (empty($data['date_add'])) {
            $data['date_add'] = time();
        }
        if (empty($data['date_edit'])) {
            $data['date_edit'] = time();
        }
        return parent::insert($data);
    }

    public function update(array $data, $where)
    {
        // Einen Zeitstempel hinzufügen
        if (empty($data['date_edit'])) {
            $data['date_edit'] = time();
        }
        return parent::update($data, $where);
    }    
    /**
     * Holte Threads zur Gruppe
     *
     * @param INT $group_id
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function getThreads($group_id, $returnSelect = false)
    {
    	$select = $this->select()->setIntegrityCheck(false)
    					->from(array("r" => "gruppen_threads"), array("*"))
						->join(array("u" => "users"), "u.user_id = r.founder_id", array("firstname", "lastname", "nickname", "image_id_m", "image_id_s")) 
    					->where("r.group_id = ?", $group_id)
    					->order("r.date_add DESC");
    	if($returnSelect)
    		return $select;
    	return $this->fetchAll($select);
    }
}
