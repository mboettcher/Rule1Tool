<?php
class Quotes_Quote extends Abstraction
{
	protected $company;
	protected $market;
	
	public $close = null;
	public $date = null;
	public $change = null;
	
	public $high = null;
	public $low = null;
	
	protected $_dataFetched = false;
		
	public function __construct(Company $company, Market $market)
	{
		$this->company = $company;
		$this->market = $market;
	}
	/**
	 * holt den letzten Kurs
	 *
	 * @return Quotes_Quote
	 */
	public function getLastQuote()
	{
		if(!$this->_dataFetched)
		{
                    
                        //Prüfen ob Market-Object auch initialisiert, da bei Portfolios teilweise kein Market vorhanden ist
                        if($this->market->isInit())
                        {
                            $table = new StockQuotesEODModel();
    /*
                            $select = $table->select()
                                                            ->where("company_id = ?", $this->company->getId())
                                                            ->where("market_id = ?", $this->market->getId())
                                                            ->order("date DESC")
                                                            ->limit(2);
    */
                            //WIR BRAUCHEN FORCE INDEX, verkürzt abfrage Zeit um 100000%
                            /*
                             * SELECT * FROM stockquotes_eod USE INDEX(PRIMARY) 
                             * WHERE (company_id = '1') AND (market_id = '3') 
                             * ORDER BY `date` DESC 
                             * LIMIT 2
                             */
                            $sql = "SELECT * FROM ".$table->getTableName()." USE INDEX(PRIMARY) "
                                            .$table->getAdapter()->quoteInto("WHERE (company_id=?)",$this->company->getId())." "
                                            .$table->getAdapter()->quoteInto("AND (market_id=?)",$this->market->getId())." "
                                            ."ORDER BY `date` DESC "
                                            ."LIMIT 2";

                            //echo $sql;
                            //echo $select->__toString();
                            //echo "<br/><br/>";		

                            $rows = $table->getAdapter()->fetchAll($sql);	
                            //$rows = $table->fetchAll($select);
                        }
                         else {
                             $rows = null;
                        }
                    
                            
			if(count($rows) >= 2)
			{
				//$rows = $rows->toArray(); 
				$this->change = round(($rows[0]["close"]-$rows[1]["close"]) / $rows[1]["close"] * 100,2); //Änderung zum Vortag in Prozent
				$this->close = $rows[0]["close"];
				$this->date = $rows[0]["date"];
				$this->high = $rows[0]["high"];	
				$this->low = $rows[0]["low"];
			}
			else
			{
				//Keine Daten da
				$this->change = null;
				$this->close = null;
				$this->date = null;	
				$this->high = null;	
				$this->low = null;						
			}
			
			$this->_dataFetched = true;
		}
		
		return $this;
	}
	
	public function getChange($toNumber = false)
	{
		if($toNumber)
		{
			$value =  $this->_toNumber($this->change);
			if($this->change >= 0)
				$value = "+".$value;
			return $value;
		}
		else
			return $this->change;
	}
	public function getClose($toNumber = false, $cutnumber = false)
	{
		$precision = 2;
		if($cutnumber)
		{
			if($this->close > $cutnumber)
				$precision = 0;
		}
		if($toNumber)
			return $this->_toNumber($this->close, $precision);
		else
			return $this->close;
	}
	public function getHigh($toNumber = false, $cutnumber = false)
	{
		$precision = 2;
		if($cutnumber)
		{
			if($this->high > $cutnumber)
				$precision = 0;
		}
		if($toNumber)
			return $this->_toNumber($this->high, $precision);
		else
			return $this->high;
	}
	public function getLow($toNumber = false, $cutnumber = false)
	{
		$precision = 2;
		if($cutnumber)
		{
			if($this->low > $cutnumber)
				$precision = 0;
		}
		if($toNumber)
			return $this->_toNumber($this->low, $precision);
		else
			return $this->low;
	}
	/**
	 * Gibt Date zurück
	 *
	 * @param BOOLEAN True um timestamp zu erhalten
	 * @return STRING|INT
	 */
	public function getDate($timestamp = false, $dates = false)
	{
		if($timestamp)
		{
			$date = new Zend_Date($this->date, Zend_Date::ISO_8601);
			return $date->getTimestamp();
		}
		elseif($dates)
		{
			$date = new Zend_Date($this->date, Zend_Date::ISO_8601);
			return $date->get(Zend_Date::DATES);
		}
		else 
			return $this->date;
	}
	protected function _toNumber($value, $precision = 2)
	{
		$_toNumberOptions = array('locale' => Zend_Registry::get("Zend_Locale"), 'precision' => $precision);
		return Zend_Locale_Format::toFloat($value, $_toNumberOptions);
	}
	public function toArray()
	{
		$arr = array(
			"close" => $this->close,
			"date" => $this->date,
			"change" => $this->change
		);
		return $arr;
	}
	
	public function getCurrency()
	{
		return $this->market->getCurrency();
	}
	public function getCompany()
	{
		return $this->company;
	}
	public function getMarket()
	{
		return $this->market;
	}



}