<?php
class SearchModel
{
	protected $db;
	
	public function __construct()
	{
		$this->db = Zend_Registry::get('Zend_Db');
		//$this->db->setFetchMode(Zend_Db::FETCH_OBJ);
	}
	public function getRowsetByNameOrSymbol($needle, $returnSelect = false)
	{
		$select = $this->db->select()
								->from(array("c" => "companies"))
								->join(array("asex" => "availablestocksonexchanges"), "asex.company_id = c.company_id", array())
								->where('name like ?', "%".$needle."%")
								->orWhere('symbol = ?', $needle)
								->group("isin");
		
		if(!$returnSelect)
		{
			$stmt = $select->query();
			$result = $stmt->fetchAll();
			return $result;							
		}
		else
		{
			return $select;
		}
	}
	public function getRowsetByISIN($needle, $returnSelect = false)
	{
		$select = $this->db->select($needle)
								->from(array("c" => "companies"))
								->where('isin = ?', $needle)
								;
		if(!$returnSelect)
		{
			$stmt = $select->query();
			$result = $stmt->fetchAll();
			return $result;							
		}
		else
		{
			return $select;
		}
	}

}