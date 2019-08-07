<?php
/*
 * Simple Moving Average
 */
class Indikator_SMA
{
	protected $data_raw = null;
	protected $data_sma = null;
	
	protected $_data_signals = null;
	
	protected $period = null;
	
	protected $_valid = null;
	
	public function __construct($period, $data)
	{
		$this->_setPeriod($period);
		$this->_setData($data);
	}
	/**
	 * Gibt die Anzahl der nötigen Ausgangsdatensätze zurück, die angegebene Anzahl an Ausgabedatensätzen notwendig ist
	 * 
	 * @param INT Anzahl Ausgabedatensätze
	 * @param INT SMA-Periode
	 * 
	 * @return INT Anzahl der nötigen Ausgangsdatensätze
	 */
	public static function expectedDataSets($num_out_datasets, $sma_period)
	{
		$num_in_datasets = $num_out_datasets + $sma_period;
		return $num_in_datasets;
	}
	protected function _setPeriod($period)
	{
		if($period > 0)
		{
			$this->period = $period;
		}
		else
			throw new Zend_Exception("Eingegebene Periode ist ungültig!");	
	}
	protected function _setData($data)
	{
		//if(count($data) >= $this->period)
			$this->data_raw = $data;
		//else
			//throw new Zend_Exception("Ungenügende Anzahl an Datensätzen");
	}
	public function plausiCheck()
	{
		if($this->_valid === null)
		{
			//PlausiCheck machen
			if(count($this->data_raw) < $this->period)
				$this->_valid = false;
			else
				$this->_valid = true;
		}

		return $this->_valid;
	}
	
	protected function _calcSMA($i)
	{
		if($i+1 >= $this->period)
		{
			if(isset($this->data_sma[$i-1]) && $this->data_sma[$i-1] !== null && $this->data_raw[$i-$this->period] !== null)
			{	
				$sma = (($this->data_sma[$i-1] * $this->period) - $this->data_raw[$i-$this->period] + $this->data_raw[$i]) / $this->period;
			}
			else
			{
				if($this->data_raw[$i] === null)
					$sma = null;
				else
				{
            		$sum = 0;
            		$sum_count = 0;
            		for($a = 0; $a < $this->period; $a++)
            		{
            			if($this->data_raw[$i-$a] !== null)
            			{
            				$sum += $this->data_raw[$i-$a];
            				$sum_count++;
            			}
            		}
            		$sma = $sum / $sum_count;
				}
				
			}
  			
    		return $sma;
		}
		else
			return null;
	
	}
	protected function _calcAll()
	{
		for($i = 0; $i < count($this->data_raw); $i++)
		{
			$this->data_sma[$i] = $this->_calcSMA($i);
		}
	}
	protected function _calcSignal($i)
	{
		if(isset($this->data_sma[$i-1]) && isset($this->data_raw[$i-1]) && isset($this->data_sma[$i]) && isset($this->data_raw[$i]))
		{
			if($this->data_sma[$i-1] < $this->data_raw[$i-1] && $this->data_sma[$i] >= $this->data_raw[$i])
			{
				//echo "DEBUG V $i: ". $this->data_sma[$i-1] ." < ". $this->data_raw[$i-1] . " && ". $this->data_sma[$i] ." >= ". $this->data_raw[$i]."<br>";
				
				//Zur richtigen Darstellung in der Chart muss ein Verhältniswert bestimmt werden
				$diff1 = $this->data_raw[$i-1] - $this->data_sma[$i-1];
				$diff2 = $this->data_sma[$i] - $this->data_raw[$i];
				$verhaeltnisdiff2 = $diff1 / ($diff1+$diff2);
				
				//Verkauf
				return array("signal" => "s","diff" => $verhaeltnisdiff2, "index" => $i);
			}
			elseif($this->data_sma[$i-1] > $this->data_raw[$i-1] && $this->data_sma[$i] <= $this->data_raw[$i])
			{
				//echo "DEBUG K $i: ". $this->data_sma[$i-1] ." > ". $this->data_raw[$i-1] . " && ". $this->data_sma[$i] ." <= ". $this->data_raw[$i]."<br>";
				
				$diff1 = $this->data_sma[$i-1] - $this->data_raw[$i-1];
				$diff2 = $this->data_raw[$i] - $this->data_sma[$i];
				$verhaeltnisdiff2 = $diff1 / ($diff1+$diff2);
				
				//Kauf
				return array("signal" => "b", "diff" => $verhaeltnisdiff2, "index" => $i);
				//return null;
			}
			else
			{
				//kein neues Signal
				return null;
			}
		}
		else 
			return null;
			
	}
	protected function _calcAllSignals()
	{
		if($this->data_sma === null)
			$this->_calcAll();
			
		$this->_data_signals = array();
		for($i = 1; $i < count($this->data_raw); $i++) //0 kann ausgelassen werden
		{
			$this->_data_signals[$i] = $this->_calcSignal($i);
		}
	}
	public function getLastSignal()
	{
		if($this->_data_signals === null)
		{
			$this->_calcAllSignals();
		}
		for($i = count($this->_data_signals); $i > 0; $i--) 
		{
			if($this->_data_signals[$i] != null) //letzten nicht NULL wert finden
				return $this->_data_signals[$i];
		}
	}
	public function getValue($i)
	{
		if($this->data_sma === null)
			$this->data_sma[$i] = $this->_calcSMA($i);

		return $this->data_sma[$i];
	}
	public function getData()
	{
		if(!$this->plausiCheck())
			return $this->plausiCheck();
		if($this->data_sma === null)
			$this->_calcAll();
			
		return array("SMA" => $this->data_sma);		
	}
	public function getName()
	{
		return "SMA(".$this->period.")";
	}
	public function getSignals()
	{
		if($this->_data_signals === null)
			$this->_calcAllSignals();
		return $this->_data_signals;
	}
}