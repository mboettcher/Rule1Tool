<?php

class FinanceDataVendor {

	protected $_data = array(
							"cashflow" => array(),
							"eps" => array(), 
							"equity" => array(),
							"revenue" => array(), 
							"depts" => array(),
							"income_after_tax" => array(),
							"kgv" => array(),
							"roic" => array(),
							"current_eps" => null,
							"historical_growth" => null,
							"historical_kgv" => null,
							"analysts_estimated_growth" => null
							); 
	protected $_dataFetched = false;
	public function getData()
	{
		if(!$this->_dataFetched)
			$this->_fetchData();
		return $this->_data;
	}
	protected function _fetchData()
	{
		$this->_dataFetched = true;
	}
}

?>