<?php

/**
 * NewCompaniesModel
 *  
 * @author martinbottcher
 * @version 
 */

require_once 'Zend/Db/Table/Abstract.php';

class NewCompaniesModel extends Zend_Db_Table_Abstract {
	/**
	 * The default table name 
	 */
	protected $_name = 'new_companies';
	protected $_primary = "company_id";

    public function insert(array $data)
    {
        // Einen Zeitstempel hinzufügen
        if (empty($data['date_add'])) {
            $data['date_add'] = time();
        }
        return parent::insert($data);
    }
}
