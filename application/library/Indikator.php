<?php
class Indikator extends Abstraction
{
	protected $out_period = null;
	protected $out_data = null;
	
	protected $in_data = null;
	protected $in_data_count = 0;
	protected $in_data_close = null;
	
	protected $indicators = array();
	
	public function __construct($in_data, $prefs)
	{
		/*
		 * Bsp.:
		$prefs = array(
					"EMA" => array("period" => 10),
					"EMA" => array("period" => 120),
					"MACD" => array("fastEMA" => 12, "slowEMA" => 26, "signalEMA" => 9),
					"STO" => array("n_k" => 16, "n_d" => 5, "type" => "slow")
		);
		*/
	
		//$this->_setOutPeriod($out_period);
		$this->_setInData($in_data);
		$this->_setIndicators($prefs);
	}
	protected function _setOutPeriod($period)
	{
		if($period > 0)
			$this->out_period = $period;
		else
			throw new Zend_Exception("Ungültige Zeitperiode!");
	}
	protected function _setInData($data)
	{
		if(is_array($data))
		{
			$this->in_data = $data;
			$this->in_data_count =count($data);
		}
		else
			throw new Zend_Exception("Keine Ausgangsdaten gefunden!");
	}
	protected function _setIndicators($inds)
	{
		if(is_array($inds))
		{
			foreach($inds as $key => $ind)
			{
				if($key == "EMA")
				{
					foreach($ind as $ema)
					{
    					if(isset($ema["period"]))
    					{
    						//if($this->in_data_count >= $ema["period"])
    						$this->indicators["EMA"][] = new Indikator_EMA($ema["period"], $this->_getArrayCloses());
    					}
    					else
    						throw new Zend_Exception("Keine Periode für EMA angegeben.");						
					}
				}
				elseif($key == "SMA")
				{
					foreach($ind as $sma)
					{
    					if(isset($sma["period"]))
    					{
    						//if($this->in_data_count >= $sma["period"])
    							$this->indicators["SMA"][] = new Indikator_SMA($sma["period"], $this->_getArrayCloses());
    					}
    					else
    						throw new Zend_Exception("Keine Periode für SMA angegeben.");
					}
				}
				elseif($key == "MACD")
				{
					foreach($ind as $macd)
					{
    					if(isset($macd["fastEMA"]) && isset($macd["slowEMA"]) && isset($macd["signalEMA"]))
    					{
    						//if($this->in_data_count >= $macd["slowEMA"] && $this->in_data_count >= $macd["signalEMA"])
    						$this->indicators["MACD"][] = new Indikator_MACD($macd["fastEMA"], $macd["slowEMA"], $macd["signalEMA"], $this->_getArrayCloses());
    					}
    					else
    						throw new Zend_Exception("Unvollständige Angaben für den MACD");
					}
				}
				elseif($key == "STO")
				{
					foreach($ind as $sto)
					{
    					if(isset($sto["k"]) && isset($sto["d"]) && isset($sto["type"]))
    					{
    						//if($this->in_data_count >= $sto["k"])
    						$this->indicators["STO"][] = new Indikator_STO($sto["k"], $sto["d"], $sto["type"], $this->in_data);
    					}
    					else
    						throw new Zend_Exception("Unvollständige Angaben für den STO");	
					}
				}
				else
				{
					//throw new Zend_Exception("Unbekannter Indikator!");
				}
					
			}
		}
		else
			throw new Zend_Exception("Keine Indikatoren angegeben.");
	}
	public function getIndicators()
	{
		if(count($this->indicators) > 0)
		{
			$this->out_data = array();
			foreach($this->indicators as $kat => $indi_kat)
			{
				foreach($indi_kat as $n => $indikator)
				{
					if($indikator->getData())
					{
						$this->out_data[$kat][$n]["name"] = $indikator->getName();
						$this->out_data[$kat][$n]["data"] = $indikator->getData();
						$this->out_data[$kat][$n]["signals"] = $indikator->getSignals();						
					}
				}

			}
			return $this->out_data;
		}
		else
			return null;
	}
	public function getLastSignals()
	{
		if(count($this->indicators) > 0)
		{
			$this->out_data = array();
			foreach($this->indicators as $kat => $indi_kat)
			{
				foreach($indi_kat as $n => $indikator)
				{
					$this->out_data[$kat][$n]["name"] = $indikator->getName();
					$signal = $indikator->getLastSignal();
					if($signal)
					{
						$this->out_data[$kat][$n]["lastSignal"] = $signal["signal"];
						$this->out_data[$kat][$n]["lastSignalDate"] = $this->in_data[$signal["index"]]["date"];
						$zdate = new Zend_Date($this->in_data[$signal["index"]]["date"], Zend_Date::ISO_8601);
						$this->out_data[$kat][$n]["lastSignalDateDates"] = $zdate->get(Zend_Date::DATES);
					}
					else 
					{
						$this->out_data[$kat][$n]["lastSignal"] = null;
						$this->out_data[$kat][$n]["lastSignalDate"] = null;
						$this->out_data[$kat][$n]["lastSignalDateDates"] = null;
					}					
				}

			}
			return $this->out_data;
		}
		else
			return null;
	}
	protected function _getArrayCloses()
	{
		if($this->in_data_close !== null)
		{
			return $this->in_data_close;
		}
		else
		{
    		$this->in_data_close = array();
    		for($i = 0; $i < count($this->in_data); $i++)
    		{
    			$this->in_data_close[] = $this->in_data[$i]["close"];
    		}
    		return $this->in_data_close;		
		}
	}
	
	/**
	 * Errechnet wieviele Zeilen an Ausgangsdaten benötigt werden
	 *
	 * @param INT $num_out_datasets
	 * @param ARRAY $inds
	 * @return INT
	 */
	public static function expectedDataSets($num_out_datasets, $inds)
	{
		if(is_array($inds) && count($inds) > 0)
		{
			$count_sets = array();
			
			foreach($inds as $key => $ind)
			{
				if($key == "EMA")
				{
					foreach($ind as $ema)
					{
    					if(isset($ema["period"]))
    						$count_sets[] = Indikator_EMA::expectedDataSets($num_out_datasets, $ema["period"]);
    					else
    						throw new Zend_Exception("Keine Periode für EMA angegeben.");							
					}
				}
				elseif($key == "SMA")
				{
					foreach($ind as $sma)
					{
    					if(isset($sma["period"]))
    						$count_sets[] = Indikator_SMA::expectedDataSets($num_out_datasets, $sma["period"]);
    					else
    						throw new Zend_Exception("Keine Periode für SMA angegeben.");
					}
				}
				elseif($key == "MACD")
				{
					foreach($ind as $macd)
					{
    					if(isset($macd["fastEMA"]) && isset($macd["slowEMA"]) && isset($macd["signalEMA"]))
    						$count_sets[] = Indikator_MACD::expectedDataSets($num_out_datasets, $macd["fastEMA"], $macd["slowEMA"], $macd["signalEMA"]);
    					else
    						throw new Zend_Exception("Unvollständige Angaben für den MACD");
					}
				}
				elseif($key == "STO")
				{
					foreach($ind as $sto)
					{
    					if(isset($sto["k"]) && isset($sto["d"]) && isset($sto["type"]))
    						$count_sets[] = Indikator_STO::expectedDataSets($num_out_datasets, $sto["k"], $sto["d"], $sto["type"]);
    					else
    						throw new Zend_Exception("Unvollständige Angaben für den STO");	
					}
				}
				else
				{
					//throw new Zend_Exception("Unbekannter Indikator!");
				}
			}
			
			$max = max($count_sets);
			return $max;
		}
		else
			throw new Zend_Exception("Keine Indikatoren angegeben.");
	}
}