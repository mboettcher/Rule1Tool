<?php

/**
 * GruppenModel
 *  
 * @author mb
 * @version 
 */
	
require_once 'Zend/Db/Table/Abstract.php';

class GruppenModel extends Zend_Db_Table_Abstract
{
	/**
	 * The default table name 
	 */
    protected $_name = 'gruppen';
    protected $_primary = "id";
    
    protected $_dependentTables = array('GruppenThreadsModel');

	protected $_referenceMap    = array(
        'Founder' => array(
            'columns'           => 'founder_id',
            'refTableClass'     => 'UsersModel',
            'refColumns'        => 'user_id',
        )
    );
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
