<?php

/**
 * ImagesModel
 *  
 * @author mb
 * @version 
 */
	
require_once 'Zend/Db/Table/Abstract.php';

class ImagesModel extends Zend_Db_Table_Abstract
{
	/**
	 * The default table name 
	 */
    protected $_name = 'images';
    protected $_primary = "id";
    
    protected $_dependentTables = array('UsersModel');

	
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
}
