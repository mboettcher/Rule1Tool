<?php

/**
 * CompaniesModel
 *  
 * @author mb
 * @version 
 */

class CompaniesModel extends Zend_Db_Table_Abstract {
	/**
	 * The default table name 
	 */
	protected $_name = 'companies';
	protected $_primary = "company_id";

    public function insert(array $data)
    {
        // Einen Zeitstempel hinzufügen
        if (empty($data['add_date'])) {
            $data['add_date'] = time();
        }
        return parent::insert($data);
    }
    /**
     * Gibt eine Zend_Db_Table_Row_Abstract passend zur ISIN zurück
     *
     * @param unknown_type $isin
     * @return Zend_Db_Table_Row_Abstract
     */
	public function getAllDataByISIN($isin)
	{
		return $this->fetchRow($this->select()->from($this, array("*"))
												->where('isin = ?', $isin));
	}
	public function getRowsetByNameOrSymbol($needle, $returnSelect = false, $varstart = false)
	{
		if($varstart)
			$nameNeedle = $needle."%";
		else
			$nameNeedle = "%".$needle."%";
		$select = $this->select()->setIntegrityCheck(false)
								->from(array("c" => "companies"))
								->join(array("asex" => "availablestocksonexchanges"), "asex.company_id = c.company_id", array())
								->where('name like ?', $nameNeedle)
								->orWhere('symbol = ?', $needle)
								->group("isin")
								->order("name ASC");
		
		if(!$returnSelect)
		{
			return $this->fetchAll($select);							
		}
		else
		{
			return $select;
		}
	}
	public function getRowsetByISIN($needle, $returnSelect = false)
	{
		$select = $this->select()
								->where('isin = ?', $needle)
								;
		if(!$returnSelect)
		{
			return $this->fetchAll($select);							
		}
		else
		{
			return $select;
		}
	}
	public function getTableName()
	{
		return $this->_name;
	}
	

}
