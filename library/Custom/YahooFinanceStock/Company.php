<?php

class YahooFinanceStock_Company
{
	protected $_name;
	protected $_isin;
	
	public function __construct($data = null)
	{
		if($data != null)
			$this->_setObjectData($data);
	}
	protected function _setObjectData($data)
	{
		$needles = array(
							"name",
							"isin"
					);
		foreach ($needles as $key)
		{
			if(isset($data[$key]))
			{
				$var = "_".$key;
				$this->$var = $data[$key];
			}
		}
	}
	public function getName()
	{
		return $this->_name;
	}
	public function getIsin()
	{
		return $this->_isin;
	}
}


?>