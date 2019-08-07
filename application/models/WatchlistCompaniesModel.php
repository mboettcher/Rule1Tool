<?php

/**
 * WatchlistCompaniesModel
 *  
 * @author martinbottcher
 * @version 
 */

require_once 'Zend/Db/Table/Abstract.php';

class WatchlistCompaniesModel extends Zend_Db_Table_Abstract {
	/**
	 * The default table name 
	 */
	protected $_name = 'watchlist_companies';
	protected $_primary = array('watchlist_id', 'company_id');
	
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
     * Gibt RowSet einer Watchlist zurück
     *
     * @param integer $watchlist_id
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function getWatchlist($watchlist_id)
    {
    	$select = $this->select()->setIntegrityCheck(false);
    	$select			->from(array("wc" => "watchlist_companies"))
						->join(array("c" => "companies"), "wc.company_id = c.company_id", array("company_name" => "name", "isin", "company_id", "type", "main_market", "picture_id"))   
    					->where("wc.watchlist_id = ?", $watchlist_id)
    					->order("c.name ASC");
    					
    	$rows = $this->fetchAll($select);

    	return $rows;

    }
	public function getTableName()
	{
		return $this->_name;
	}
}
