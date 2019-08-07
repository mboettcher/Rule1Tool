<?php

class Indikator_EMA extends Abstraction
{
	protected $alpha = null;
	protected $period = null;
	protected $data_raw = null;
	protected $data_ema = null;
	
	protected $_data_signals = null;
		
	public function __construct($period = null, $data)
	{
		if($period !== null)
		{
			$this->_setPeriod($period);	
			$this->_calcAlpha($this->period);
		}
		else
			throw new Zend_Exception("Bitte Periode setzen!");

		$this->_setData($data);
	}
	/**
	 * Gibt die Anzahl der nötigen Ausgangsdatensätze zurück, die angegebene Anzahl an Ausgabedatensätzen notwendig ist
	 * 
	 * @param INT Anzahl Ausgabedatensätze
	 * @param INT EMA-Periode
	 * 
	 * @return INT Anzahl der nötigen Ausgangsdatensätze
	 */
	public static function expectedDataSets($num_out_datasets, $ema_period)
	{
		$num_in_datasets = $num_out_datasets + $ema_period;
		return $num_in_datasets;
	}
	protected function _calcAlpha($period)
	{

		$this->alpha = 2/($period+1);

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
	protected function _calcPeriod()
	{
		$this->period = 2/$this->alpha-1;
	}
	protected function _setAlpha($alpha)
	{
		if($alpha >= 0 && $alpha <= 1)
			$this->alpha = $alpha;
		else
			throw new Zend_Exception("Ungültiges Alpha");
	}
	protected function _setData($data)
	{
		//if(count($data) >= $this->period)
		//{
			$this->data_raw = $data;
		//}
		//else
			//throw new Zend_Exception("Ungenügende Anzahl an Datensätzen");
	}
	protected function _calcEMA($i)
	{
		if($i+1 <= $this->period || $this->data_raw[$i-1] === null)
		{
			//restlichen Felder auf NULL setzen
			for($a = 0; $a < $i;$a++)
			{
				$this->data_ema[$a] = null;
			}
			$sma = new Indikator_SMA($this->period, $this->data_raw);
			$this->data_ema[$i] = $sma->getValue($i);
			$this->data_ema[$i] = $this->data_ema[$i];
			
		}
		else
			$this->data_ema[$i] = $this->alpha * $this->data_raw[$i] + (1 - $this->alpha) * $this->_calcEMA($i-1);
		
		return $this->data_ema[$i];
	}
	
	protected function _calcSignal($i)
	{
		if(isset($this->data_ema[$i-1]) && isset($this->data_raw[$i-1]) && isset($this->data_ema[$i]) && isset($this->data_raw[$i]))
		{
			if($this->data_ema[$i-1] < $this->data_raw[$i-1] && $this->data_ema[$i] >= $this->data_raw[$i])
			{
				//Zur richtigen Darstellung in der Chart muss ein Verhältniswert bestimmt werden
				$diff1 = $this->data_raw[$i-1] - $this->data_ema[$i-1];
				$diff2 = $this->data_ema[$i] - $this->data_raw[$i];
				$verhaeltnisdiff2 = $diff1 / ($diff1+$diff2);
				
				//Verkauf
				return array("signal" => "s","diff" => $verhaeltnisdiff2, "index" => $i);
			}
			elseif($this->data_ema[$i-1] > $this->data_raw[$i-1] && $this->data_ema[$i] <= $this->data_raw[$i])
			{
				$diff1 = $this->data_ema[$i-1] - $this->data_raw[$i-1];
				$diff2 = $this->data_raw[$i] - $this->data_ema[$i];
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
		if($this->data_ema === null)
			$this->_calcEMA(count($this->data_raw)-1);
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
	public function getData()
	{
		if($this->data_ema === null)
			$this->_calcEMA(count($this->data_raw)-1);
		return array($this->data_ema);
	}
	public function getName()
	{
		return "EMA(".$this->period.")";
	}
	public function getSignals()
	{
		if($this->_data_signals === null)
			$this->_calcAllSignals();
		return $this->_data_signals;
	}
}