<?php
/*
 * Moving Average Convergence/Divergence
 */
class Indikator_MACD
{
	protected $data_raw = null;
	protected $data_macd = null;
	
	protected $fastEMA = null;
	protected $slowEMA = null;
	protected $signalEMA = null;
	
	protected $_data_signals = null;
	
	public function __construct($fastEMA, $slowEMA, $signalEMA, $data)
	{
		if($fastEMA > $slowEMA)
		{
			//TAUSCHEN
			$tmp = $fastEMA;
			$fastEMA = $slowEMA;
			$slowEMA = $tmp;
		}
		$this->_setFastEMA($fastEMA);
		$this->_setSlowEMA($slowEMA);
		$this->_setSignalEMA($signalEMA);
		
		$this->_setData($data);
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
	public static function expectedDataSets($num_out_datasets, $fastEMA, $slowEMA, $signalEMA)
	{
		$max = max(array($fastEMA, $slowEMA));
		$num_in_datasets = $num_out_datasets + $max + $signalEMA;
		return $num_in_datasets;
	}
	protected function _setFastEMA($ema)
	{
		if($ema > 0)
			$this->fastEMA = $ema;
		else
			throw new Zend_Exception("Ungültiger FastEMA");
	}
	protected function _setSlowEMA($ema)
	{
		if($ema > 0)
			$this->slowEMA = $ema;
		else
			throw new Zend_Exception("Ungültiger SlowEMA");
	}
	protected function _setSignalEMA($ema)
	{
		if($ema > 0)
			$this->signalEMA = $ema;
		else
			throw new Zend_Exception("Ungültiger signalEMA");
	}
	protected function _setData($data)
	{
		//$anzahl = count($data);
		//if($anzahl >= $this->slowEMA && $anzahl >= $this->signalEMA)
			$this->data_raw = $data;
		//else
			//throw new Zend_Exception("Ungenügende Anzahl an Datensätzen");
	}
	protected function _calcMACD()
	{
	/*
	 * Formel: MACD = EMA(12) - EMA(26)

        Die Signallinie ist ein 9-Perioden-EMA des MACD. Signallinie und MACD werden jeweils als Linie in einem Zwei-Linien-Modell dargestellt.
        
        Diese Standardeinstellungen können beliebig geändert werden, um den MACD für die eigene Strategie anzupassen.
        
        Nebenrechnung:
        EMA(12)t = EMAt-1 + ((2/(12 + 1)) * (Ct - EMAt-1))
        EMA(26)t = EMAt-1 + ((2/(26 + 1)) * (Ct - EMAt-1))
        Signallinie:
        EMA(9)t = EMAt-1 + ((2/(9 + 1)) * (MACDt - EMAt-1))
	 */
		
		$fastEMA = new Indikator_EMA($this->fastEMA, $this->data_raw);
		$fastEMA_data = $fastEMA->getData();
		$fastEMA_data = $fastEMA_data[0];
		
		$slowEMA = new Indikator_EMA($this->slowEMA, $this->data_raw);
		$slowEMA_data = $slowEMA->getData();
		$slowEMA_data = $slowEMA_data[0];

		$macd = array();
		for($i = 0; $i < count($this->data_raw); $i++)
		{
			if(isset($fastEMA_data[$i]) && isset($slowEMA_data[$i]))
			{
				$macd[] = round($fastEMA_data[$i],2)-round($slowEMA_data[$i],2);
			}
			else
				$macd[] = null;
			
		}
		
		$signalEMA = new Indikator_EMA($this->signalEMA, $macd);
		$signalEMA_data = $signalEMA->getData();
		$signalEMA_data = $signalEMA_data[0];
		
		$histogram_data = array();
		
		for($i = 0; $i < count($this->data_raw); $i++)
		{
				
			if($signalEMA_data[$i] !== null && $macd[$i] !== null)
				$tmp_histo = $macd[$i] - $signalEMA_data[$i];
			else
				$tmp_histo = null;
								
			$histogram_data[] = $tmp_histo;
		}
		$macd_data = array("signalEMA" => $signalEMA_data,
							"MACD" => $macd,
							"histogram" => $histogram_data);	
		$this->data_macd = $macd_data;
		
	}
	public function getData()
	{
		if($this->data_macd === null)
			$this->_calcMACD();
		return $this->data_macd;
	}	
	public function getName()
	{
		return "MACD(".$this->fastEMA.",".$this->slowEMA.",".$this->signalEMA.")";
	}
	
	public function getSignals()
	{
		if($this->_data_signals === null)
			$this->_calcAllSignals();
		return $this->_data_signals;
	}
	protected function _calcSignal($i)
	{
		if(isset($this->data_macd["histogram"][$i-1]) && isset($this->data_macd["histogram"][$i]))
		{
			if($this->data_macd["histogram"][$i-1] < 0 && $this->data_macd["histogram"][$i] >= 0)
			{
				//Von Unten nach Oben -> KAUF
				//Zur richtigen Darstellung in der Chart muss ein Verhältniswert bestimmt werden
				
				$verhaeltnisdiff2 = 1- $this->data_macd["histogram"][$i] / (-$this->data_macd["histogram"][$i-1]+$this->data_macd["histogram"][$i]);
				//echo "DEBUG BUY:$i ". $this->data_macd["histogram"][$i-1]." ". $this->data_macd["histogram"][$i]." ".$verhaeltnisdiff2."<br>";
				
				return array("signal" => "b","diff" => $verhaeltnisdiff2, "index" => $i);
			}
			elseif($this->data_macd["histogram"][$i-1] > 0 && $this->data_macd["histogram"][$i] <= 0)
			{
				//Verkauf
		
				$verhaeltnisdiff2 = 1- $this->data_macd["histogram"][$i] / (-$this->data_macd["histogram"][$i-1]+$this->data_macd["histogram"][$i]);
				//echo "DEBUG SELL:$i ". $this->data_macd["histogram"][$i-1]." ". $this->data_macd["histogram"][$i]." ".$verhaeltnisdiff2."<br>";
				
				return array("signal" => "s", "diff" => $verhaeltnisdiff2, "index" => $i);
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
		if($this->data_macd === null)
			$this->_calcMACD();
			
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