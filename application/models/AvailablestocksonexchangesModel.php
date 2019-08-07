<?php

/**
 * AvailablestocksonexchangesModel
 *  
 * @author Rule1Tool GbR
 * @version 
 */

require_once 'Zend/Db/Table/Abstract.php';

class AvailablestocksonexchangesModel extends Zend_Db_Table_Abstract {
	/**
	 * The default table name 
	 */
	protected $_name = 'availablestocksonexchanges';
	protected $_primary = array("market_id", "company_id");


	public function getTableName()
	{
		return $this->_name;
	}
}
