<?php

/**
 * StockexchangesModel
 *  
 * @author 
 * @version 
 */

require_once 'Zend/Db/Table/Abstract.php';

class StockexchangesModel extends Zend_Db_Table_Abstract {
	/**
	 * The default table name 
	 */
	protected $_name = 'stockexchanges';
	protected $_primary = "market_id";
	
	public function getClosedMarkets($time)
	{
		$select = $this->select()->where("time_end <= ?", $time["hour"].($time["minute"]+21)); // 21 Minuten Zeitverzögert
		$rows = $this->fetchAll($select);
		if(count($rows) > 0)
			return $rows;
		else
			return false;
	}
	public function getTableName()
	{
		return $this->_name;
	}
}
