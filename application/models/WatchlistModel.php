<?php

/**
 * WatchlistModel
 *  
 * @author martinbottcher
 * @version 
 */

require_once 'Zend/Db/Table/Abstract.php';

class WatchlistModel extends Zend_Db_Table_Abstract {
	/**
	 * The default table name 
	 */
	protected $_name = 'watchlist';
	protected $_primary = 'watchlist_id';
	
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
	public function getTableName()
	{
		return $this->_name;
	}
}
