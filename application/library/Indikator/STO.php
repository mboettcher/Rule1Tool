<?php
/*
 * Stochastic oscillator
 * 
 * FAST & SLOW
 * 
 */

class Indikator_STO
{
	protected $data_raw = null;
	protected $data_k = null;
	protected $data_d = null;
	
	protected $data_sto = null;
	
	protected $type = null;

	protected $period = null;
	protected $sma = null;
	
	protected $_data_signals = null;
	
	public function __construct($period_k, $sma_d, $type, $data)
	{
		$this->_setPeriod($period_k);
		$this->_setSMA($sma_d);
		$this->_setData($data);
		$this->_setType($type);
	}
	/**
	 * Gibt die Anzahl der nötigen Ausgangsdatensätze zurück, die angegebene Anzahl an Ausgabedatensätzen notwendig ist
	 * 
	 * @param INT Anzahl Ausgabedatensätze
	 * @param INT fastEMA-Periode
	 * @param INT slowEMA-Periode
	 * @param INT signalEMA-Periode
	 * 
	 * @return INT Anzahl der nötigen Ausgangsdatensätze
	 */
	public static function expectedDataSets($num_out_datasets, $period_k, $sma, $type)
	{	
		
		$num_in_datasets = $num_out_datasets + max(array($sma,$period_k));
		if($type == "slow")
			$num_in_datasets += 3;
		return $num_in_datasets;
	}
	protected function _setSMA($sma)
	{
		if($sma > 0)
			$this->sma = $sma;
		else
			throw new Zend_Exception("Ungültiger SMA");
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
	protected function _setType($type)
	{
		if($type == "fast" || $type == "slow")
			$this->type = $type;
		else
			throw new Zend_Exception("Ungültiger Type!");
	} 
	
	protected function _calcK($i)
	{
		if($i+1 >= $this->period)
		{
    		$lowestlow = $this->_getLowest($i, $this->period);
    		$highesthigh = $this->_getHighest($i, $this->period);
    		if(($highesthigh - $lowestlow) != 0) //devision durch null ist nur was für haxor ;)
    			$k = 100 * (($this->data_raw[$i]["close"] - $lowestlow)/($highesthigh - $lowestlow));
    		else 
    			$k = null;
    		//echo $k."<br> ";
 		}
		else
			$k = null;
		return $k;
	}
	protected function _getLowest($from, $period)
	{
		$data = $this->_makeNewArray($from, $period, "low");
		$min = min($data);
		return $min;
	}
	protected function _getHighest($from, $period)
	{
		$data = $this->_makeNewArray($from, $period, "high");
		$max = max($data);
		return $max;
	}
	protected function _makeNewArray($from, $period, $part)
	{
		$arr = null;
		for($i=$from; $i > $from-$period;$i--)
		{
			$arr[] = $this->data_raw[$i][$part];
		}
		return $arr;
	}
	protected function _calcAll()
	{
		for($i=0; $i < count($this->data_raw);$i++)
		{
			$this->data_k[$i] = $this->_calcK($i);
		}
		
		//SLOW
		if($this->type == "slow")
		{
    		$sma = new Indikator_SMA(3, $this->data_k);
	   		$this->data_k = $sma->getData();
	   		$this->data_k = $this->data_k["SMA"];
		}
		
		//D - Trigger
		$sma = new Indikator_SMA($this->sma, $this->data_k);
		$this->data_d = $sma->getData();
		$this->data_d = $this->data_d["SMA"];
		
		//Alles zusammen packen
		/*for($i=0; $i < count($this->data_raw);$i++)
		{
			$this->data_sto[$i] = array("k" => $this->data_k[$i], "d" => $this->data_d[$i]);
		}*/
		
	}
	public function getData()
	{
		if($this->data_k === null)
			$this->_calcAll();
		return array("k" => $this->data_k, "d" => $this->data_d);
	}
	public function getName()
	{
		return "Stochastic Oscillator K(".$this->period.") D(".$this->sma.") (".$this->type.")";
	}
	
	public function getSignals()
	{
		if($this->_data_signals === null)
			$this->_calcAllSignals();
		return $this->_data_signals;
	}
	protected function _calcSignal($i)
	{
		if(isset($this->data_d[$i-1]) && isset($this->data_k[$i-1]) && isset($this->data_d[$i]) && isset($this->data_k[$i]))
		{
			if($this->data_d[$i-1] < $this->data_k[$i-1] && $this->data_d[$i] >= $this->data_k[$i])
			{
				
				//Zur richtigen Darstellung in der Chart muss ein Verhältniswert bestimmt werden
				$diff1 = $this->data_k[$i-1] - $this->data_d[$i-1];
				$diff2 = $this->data_d[$i] - $this->data_k[$i];
				$verhaeltnisdiff2 = $diff1 / ($diff1+$diff2);
				
				//Verkauf
				return array("signal" => "s","diff" => $verhaeltnisdiff2, "index" => $i);
			}
			elseif($this->data_d[$i-1] > $this->data_k[$i-1] && $this->data_d[$i] <= $this->data_k[$i])
			{
				
				$diff1 = $this->data_d[$i-1] - $this->data_k[$i-1];
				$diff2 = $this->data_k[$i] - $this->data_d[$i];
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
		if($this->data_k === null)
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
}