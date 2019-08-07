<?php

/**
 * LoginsModel
 *  
 * @author martinbottcher
 * @version 
 */

require_once 'Zend/Db/Table/Abstract.php';

class LoginsModel extends Zend_Db_Table_Abstract {
	/**
	 * The default table name 
	 */
	protected $_name = 'logins';
	protected $_primary = array('id');
	
	public function insert(array $data)
    {
        // Einen Zeitstempel hinzufügen
        if (empty($data['date_login'])) {
            $data['date_login'] = time();
        }

        return parent::insert($data);
    }

	

}
