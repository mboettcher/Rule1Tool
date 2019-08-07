<?php

/**
 * PortfolioModel
 *  
 * @author boettcher
 * @version 
 */

require_once 'Zend/Db/Table/Abstract.php';

class PortfolioModel extends Zend_Db_Table_Abstract {
	/**
	 * The default table name 
	 */
	protected $_name = 'portfolio';
	
    public function insert(array $data)
    {
        // Einen Zeitstempel hinzufügen
        if (empty($data['date_create'])) {
            $data['date_create'] = time();
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
