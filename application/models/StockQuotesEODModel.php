<?php

/**
 * StockQuotesEODModel
 *  
 * @author martinbottcher
 * @version 
 */

require_once 'Zend/Db/Table/Abstract.php';

class StockQuotesEODModel extends Zend_Db_Table_Abstract {
	/**
	 * The default table name 
	 */
	protected $_name = 'stockquotes_eod';
	protected $_primary = array('company_id', 'market_id', 'date');
	
	
	public function isAllreadyInside($company_id, $market_id, $date)
	{
		$rows = $this->find($company_id, $market_id, $date);
		if(count($rows) > 0)
			return true;
		else
			return false;
									
	}
	public function getTableName()
	{
		return $this->_name;
	}
}
