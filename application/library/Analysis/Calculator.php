<?php
class Analysis_Calculator extends Analysis 
{

	protected function _getAnalysis()
	{
		$return = parent::_getAnalysis();
		//Berechhnungen durchführen
		$this->calculateAll();
		
		return $return;
	}
	public function calculateAll()
	{
		$this->calculateRevenueAverages();
		$this->calculateEpsAverages();
		$this->calculateEquityAverages();
		$this->calculateCashflowAverages();
		$this->calculateRoicAverages();
		$this->calculateMOS();
		$this->calculatePaybackTimeMOS();
	}

	public function getDataAverage($part, $years, $localit = true, $precision = 2)
	{
	    if(isset($this->data[$part."_av_".$years]))
	        if($localit)
	            return $this->toNumber($this->data[$part."_av_".$years], $precision);
	        else 
	            return $this->data[$part."_av_".$years];
	    else
	        return null;
	}
	public function getDataRate($part, $year, $localit = true)
	{
	    if(isset($this->data[$part."_rate"][$year]))
	        if($localit)
	            return $this->toNumber($this->data[$part."_rate"][$year]);
	        else 
	            return $this->data[$part."_rate"][$year];
	    else
	        return null;
	}	
	/**
	 * Durchschnitt berechnen
	 *
	 * @param ARRAY $data
	 * @param BOOLEAN true damit Null-Element zu Null-Ergebnis führen
	 * @return DOUBLE|NULL
	 */
	protected function calculateAverage($data, $strict = false) //egal wieviele datensätze... berechne einen durchschnitt draus
	{
		if(!is_array($data) || count($data) < 1)
			return false;
		
		//lets fets!
		$count = count($data);
		$sum = 0;
		$i = 0;
		foreach($data as $value)
		{
			if($value !== null) // null-werte ignorieren
			{
				$sum += $value;
				$i++;
			}
		}
		
		if($i > 0)
		{
			if($strict) //auf nulls aufpassen
			{
				if($count == $i) //keine nulls dabei?
					return $sum / $count;
				else
					return null;
			}
			else 
				return $sum / $i;
		}
		else 
			return null;
	}
	protected function calculateChangerate($from, $to)
	{
		if($from != 0 && $from !== null && $to !== null)
		{
			$change = $to - $from;
			$change_percental = $change / $from * 100;
			
			if($from < 0 && $to > 0) // bei übergang von MINUS zu PLUS muss das Vorzeichen umgekehrt werden, da sonst ein Negatives Wachstum heraus kommt
				$change_percental = - $change_percental;
			
			return $change_percental;		
		}
		else
			return null;
	}

	protected function calculateAllChangerates($roh_data)
	{
		$data = array();
		for($i=0; $i < (count($roh_data)-1); $i++)
		{	
			$rate = $this->calculateChangerate($roh_data[$i+1], $roh_data[$i]);
			//if($rate)
				$data[] = $rate;
		}
		return $data;
	}
	protected function calculateROIC($income_after_tax, $equity, $depts)
	{
		if(($equity + $depts) != 0 && $equity !== null && $depts !== null && $income_after_tax !== null)
			return $income_after_tax / ($equity + $depts) * 100;
		else
			return null;
	}
	protected function calculateFiveYearAverage($data)
	{
		if(count($data) >= 5)
		{
			//berechne
			return $this->calculateAverage(array($data[0],$data[1],$data[2],$data[3],$data[4]), true);
		}
		else
			return false;
	}
	protected function calculateOneYearAverage($data)
	{
		if(count($data) >= 1)
		{
			//berechne
			return $data[0];
		}
		else
			return false;
	}
	protected function calculateTenYearAverage($data)
	{
		if(count($data) >= 10)
		{
			//berechne
			return $this->calculateAverage(array($data[0],$data[1],$data[2],$data[3],$data[4],$data[5],$data[6],$data[7],$data[8],$data[9]), true);
		}
		else
			return false;
	}
	protected function calculateNineYearAverage($data)
	{
		if(count($data) >= 9)
		{
			//berechne
			return $this->calculateAverage(array($data[0],$data[1],$data[2],$data[3],$data[4],$data[5],$data[6],$data[7],$data[8]), true);
		}
		else
			return false;
	}	
	protected function calculateAllROIC()
	{
	    	$c_iat = count($this->data["income_after_tax"]);
			$c_equity = count($this->data["equity"]);
			$c_depts = count($this->data["depts"]);
			$count = 0;
		    if($c_equity < $c_depts)
		    {
		    	if($c_iat < $c_equity)
		    		$count = $c_iat;
		    	else
		    		$count = $c_equity;
		    }
		    else
		    	$count = $c_depts;

    		for($i=0; $i < $count; $i++)
    		{
    			$this->data["roic"][] = $this->calculateROIC($this->data["income_after_tax"][$i], $this->data["equity"][$i], $this->data["depts"][$i]);
    		}
	}
	protected function calculateRoicAverages()
	{
		if(count($this->data["roic"]) == 0)
			$this->calculateAllROIC();
			
		$this->data["roic_av_1"] = $this->calculateOneYearAverage($this->data["roic"]);
		$this->data["roic_av_5"] = $this->calculateFiveYearAverage($this->data["roic"]);
		$this->data["roic_av_10"] = $this->calculateTenYearAverage($this->data["roic"]);
	}
	protected function calculateRevenueAverages()
	{
		if(count($this->data["revenue_rate"]) == 0)
			$this->calculateRevenueGrowthRate();
			
		$this->data["revenue_av_1"] = $this->calculateOneYearAverage($this->data["revenue_rate"]);
		$this->data["revenue_av_5"] = $this->calculateFiveYearAverage($this->data["revenue_rate"]);
		$this->data["revenue_av_9"] = $this->calculateNineYearAverage($this->data["revenue_rate"]);
	}
	protected function calculateRevenueGrowthRate()
	{
		$this->data["revenue_rate"] = $this->calculateAllChangerates($this->data["revenue"]);
	}
	protected function calculateEquityAverages()
	{
		if(count($this->data["equity_rate"]) == 0)
			$this->calculateEquityGrowthRate();
			
		$this->data["equity_av_1"] = $this->calculateOneYearAverage($this->data["equity_rate"]);
		$this->data["equity_av_5"] = $this->calculateFiveYearAverage($this->data["equity_rate"]);
		$this->data["equity_av_9"] = $this->calculateNineYearAverage($this->data["equity_rate"]);
	}
	protected function calculateEquityGrowthRate()
	{
		$this->data["equity_rate"] = $this->calculateAllChangerates($this->data["equity"]);
	}
	protected function calculateEpsAverages()
	{
		if(count($this->data["eps_rate"]) == 0)
			$this->calculateEpsGrowthRate();
			
		$this->data["eps_av_1"] = $this->calculateOneYearAverage($this->data["eps_rate"]);
		$this->data["eps_av_5"] = $this->calculateFiveYearAverage($this->data["eps_rate"]);
		$this->data["eps_av_9"] = $this->calculateNineYearAverage($this->data["eps_rate"]);
	}
	protected function calculateEpsGrowthRate()
	{
		$this->data["eps_rate"] = $this->calculateAllChangerates($this->data["eps"]);
	}
	protected function calculateCashflowAverages()
	{
		if(count($this->data["cashflow_rate"]) == 0)
			$this->calculateCashflowGrowthRate();
			
		$this->data["cashflow_av_1"] = $this->calculateOneYearAverage($this->data["cashflow_rate"]);
		$this->data["cashflow_av_5"] = $this->calculateFiveYearAverage($this->data["cashflow_rate"]);
		$this->data["cashflow_av_9"] = $this->calculateNineYearAverage($this->data["cashflow_rate"]);
	}
	protected function calculateCashflowGrowthRate()
	{
		$this->data["cashflow_rate"] = $this->calculateAllChangerates($this->data["cashflow"]);
	}
	protected function calculateKgvAverages()
	{
		$this->data["kgv_av_1"] = $this->calculateOneYearAverage($this->data["kgv"]);
		$this->data["kgv_av_5"] = $this->calculateFiveYearAverage($this->data["kgv"]);
		$this->data["kgv_av_10"] = $this->calculateTenYearAverage($this->data["kgv"]);
	}
	protected function calculateHistoricalKGV()
	{
		if(!$this->data["kgv_av_10"])
			$this->calculateKgvAverages();
			
		if(!$this->data["kgv_av_10"]) // wenn immernoch nicht, dann hole halt eins über weniger jahre (maximal)
			$this->data["historical_kgv"] = $this->calculateAverage($this->data["kgv"]);
		else
			$this->data["historical_kgv"] = $this->data["kgv_av_10"];
	}
	protected function calculateFutureKGV()
	{
		if(!$this->data["historical_kgv"])
			$this->calculateHistoricalKGV();
			
		if($this->my_future_kgv_testvalue != null)		
			$this->data["future_kgv"] = $this->my_future_kgv_testvalue;	
		elseif($this->data["my_future_kgv"] != null)		
			$this->data["future_kgv"] = $this->data["my_future_kgv"];	
		elseif($this->data["historical_kgv"] != null && $this->data["historical_kgv"] < $this->data["rule1_growth"]*2)
			$this->data["future_kgv"] = $this->data["historical_kgv"];
		else
			$this->data["future_kgv"] = $this->data["rule1_growth"]*2;
	}
	public function getFutureKgv($localit = true, $precision = 2)
	{
	    if(!$this->data["future_kgv"])
			$this->calculateFutureKGV();
		if($localit)
            return $this->toNumber($this->data["future_kgv"], $precision);
        else 
            return $this->data["future_kgv"];			
	}
	
	protected function calculateHistoricalGrowth()
	{
		if(!$this->data["equity_av_9"])
			$this->calculateEquityAverages();
			
        //@TODO Auf vorhandene Anzahl an Werten anpassen
		if(!$this->data["historical_growth"]) //wenn immernoch nicht, dann das nehmen was da
			$this->data["historical_growth"] = $this->calculateAverage($this->data["equity_rate"]);
		else 	
			$this->data["historical_growth"] = $this->data["equity_av_9"];
	}
    public function getHistoricalGrowth($localit = true, $precision = 2, $printZero = false)
	{
	    if(!$this->data["historical_growth"])
			$this->calculateHistoricalGrowth();
				
	    if($localit)
            return $this->toNumber($this->data["historical_growth"], $precision, $printZero);
        else 
            return $this->data["historical_growth"];
	}
	protected function calculateFutureEPS()
	{
		if(!$this->data["rule1_growth"])
			$this->calculateRule1Growth();
		$multiplikator = 1 + $this->data["rule1_growth"]/100;
		
		if($this->my_eps_testvalue != null)		
			$eps = $this->my_eps_testvalue;	
		else 
			$eps = $this->data["current_eps"];
		
		if($eps >= 0)		
			$this->data["future_eps"] = $eps * pow($multiplikator, 10);
		else
		{
			//Negatives EPS
			
			//Betrag bilden
			$betrag = -$eps;
			//theoretisches EPS vom Betrag errechnen, um exponenzielle Steigerung zu bekommen
			$f_eps = $betrag * pow($multiplikator, 10);
			//Änderung zwischen Ursprung und Hochrechnung feststellen
			$change = $f_eps - $betrag;
				
			//Errechnete Änderung zum ursprünglichen EPS addieren
			$this->data["future_eps"] = $eps+$change;			
		}

	}
    public function getFutureEPS($localit = true, $precision = 2)
	{
	    if(!$this->data["future_eps"])
			$this->calculateFutureEPS();
				
	    if($localit)
            return $this->toNumber($this->data["future_eps"], $precision, true);
        else 
            return $this->data["future_eps"];
	}
	protected function calculateRule1Growth()
	{
		if(!$this->data["historical_growth"])
			$this->calculateHistoricalGrowth();
			
		if($this->my_estimated_growth_testvalue != null)		
			$this->data["rule1_growth"] = $this->my_estimated_growth_testvalue;	
		elseif($this->data["my_estimated_growth"] != null)
			$this->data["rule1_growth"] = $this->data["my_estimated_growth"];				
		elseif($this->data["analysts_estimated_growth"] < $this->data["historical_growth"] && $this->data["analysts_estimated_growth"] !== null)
			$this->data["rule1_growth"] = $this->data["analysts_estimated_growth"];
		else
		{
			if($this->data["historical_growth"] !== null)
				$this->data["rule1_growth"] = $this->data["historical_growth"];
			else
			{
				//jetzt das nächst bessere wählen
				if($this->data["analysts_estimated_growth"] !== null)
					$this->data["rule1_growth"] = $this->data["analysts_estimated_growth"];	
				else
					$this->data["rule1_growth"] = null;
					
			}
		}
			

	}
    public function getRule1Growth($localit = true, $precision = 2)
	{
	    if(!$this->data["rule1_growth"])
			$this->calculateRule1Growth();
				
	    if($localit)
            return $this->toNumber($this->data["rule1_growth"], $precision, true);
        else 
            return $this->data["rule1_growth"];
	}
	
	protected function calculateFuturePrice()
	{
		if(!$this->data["future_eps"])
			$this->calculateFutureEPS();
		if(!$this->data["future_kgv"])
			$this->calculateFutureKGV();
				
		$this->data["future_price"] = $this->data["future_eps"] * $this->data["future_kgv"];
	}
    public function getFuturePrice($localit = true, $precision = 2)
	{
	    if(!$this->data["future_price"])
			$this->calculateFuturePrice();

		if($localit)
            return $this->toNumber($this->data["future_price"], $precision, true);
        else 
            return $this->data["future_price"];			
	}
	protected function calculateStickerPrice()
	{
		if(!$this->data["future_price"])
			$this->calculateFuturePrice();
			
		//$multiplikator = ($this->rate_of_return/100) + 1;
		//$stickerprice = $this->data["future_price"];
		
		//funzt ned:
		//$multiplikator = 1 - $this->rate_of_return/100;		
		//$this->data["stickerprice"] = $this->data["future_price"] * pow($multiplikator, 10);
		
		$stickerprice = $this->data["future_price"];
		$multiplikator = $this->rate_of_return/100 +1;	
		for($i=0;$i<10;$i++)
			$stickerprice = $stickerprice / $multiplikator;
		$this->data["stickerprice"] = $stickerprice;
	}
    public function getStickerPrice($localit = true, $precision = 2)
	{
	    if(!$this->data["stickerprice"])
			$this->calculateStickerPrice();
			
		if($localit)
            return $this->toNumber($this->data["stickerprice"], $precision, true);
        else 
            return $this->data["stickerprice"];				
	}
	protected function calculateMOS()
	{
		if(!$this->data["future_stickerprice"])
			$this->calculateStickerPrice();
		$this->data["mos_price"] = $this->data["stickerprice"] * 0.5;
	}
    public function getMOS($localit = true, $precision = 2)
	{
	    if(!$this->data["mos_price"])
			$this->calculateMOS();
				
		if($localit)
            return $this->toNumber($this->data["mos_price"], $precision, true);
        else 
            return $this->data["mos_price"];
	}
	public function getMyEstimatedGrowth($localit = true, $precision = 2, $printZero = false)
	{
		if($localit)
            return $this->toNumber($this->data["my_estimated_growth"], $precision, $printZero);
        else 
            return $this->data["my_estimated_growth"];
	}
	public function getAnalystsEstimatedGrowth($localit = true, $precision = 2, $printZero = false)
	{
		if($localit)
            return $this->toNumber($this->data["analysts_estimated_growth"], $precision, $printZero);
        else 
            return $this->data["analysts_estimated_growth"];
	}
	public function getCurrentEPS($localit = true, $precision = 2)
	{
		if($localit)
            return $this->toNumber($this->data["current_eps"], $precision, true);
        else 
            return $this->data["current_eps"];
	}
	public function getMyFutureKgv($localit = true, $precision = 2, $printZero = false)
	{
		if($localit)
            return $this->toNumber($this->data["my_future_kgv"], $precision, $printZero);
        else 
            return $this->data["my_future_kgv"];
	}
	public function getHistoricalKgv($localit = true, $precision = 2)
	{
		if($localit)
            return $this->toNumber($this->data["historical_kgv"], $precision, true);
        else 
            return $this->data["historical_kgv"];
	}
	public function getFutureKGV_Rule1Growth($localit = true, $precision = 2)
	{
		if($localit)
            return $this->toNumber($this->getRule1Growth(false) * 2, $precision, true);
        else 
            return $this->getRule1Growth(false) * 2;
	}
	public function getEstimatedGrowthTestvalue($localit = true, $precision = 2,  $printZero = false)
	{
		if($localit)
            return $this->toNumber($this->my_estimated_growth_testvalue, $precision, $printZero);
        else 
            return $this->my_estimated_growth_testvalue;
	}
	public function getFutureKgvTestvalue($localit = true, $precision = 2, $printZero = false)
	{
		if($localit)
            return $this->toNumber($this->my_future_kgv_testvalue, $precision, $printZero);
        else 
            return $this->my_future_kgv_testvalue;
	}
	public function getEpsTestvalue($localit = true, $precision = 2, $printZero = false)
	{
		if($localit)
            return $this->toNumber($this->my_eps_testvalue, $precision, $printZero);
        else 
            return $this->my_eps_testvalue;
	}
	public function getMaxNumberEquityGrowth()
	{
		return $this->_getNumberDatasets($this->data["equity_rate"]);
	}
	public function getMaxNumberHistoricalKgv()
	{
		return $this->_getNumberDatasets($this->data["kgv"]);
	}
	protected function _getNumberDatasets($array)
	{
		$count = 0;
		for($i=0;$i<count($array);$i++)
		{
			if($array[$i] !== null)
				$count++;
		}
		return $count;
	}
	
	protected function calculatePaybackTime($paybackPrice)
	{
		if(!$paybackPrice || $paybackPrice == 0 || $paybackPrice < 0)
			return false;
			
		if(!$this->data["rule1_growth"])
			$this->calculateRule1Growth();
			
		if($this->getEpsTestvalue(false) != null)		
			$eps = $this->getEpsTestvalue(false);	
		else 
			$eps = $this->getCurrentEPS(false);

		$paybackTime = 1;
		
		if(!$eps || $eps == 0 || $eps < 0)
			return false;
			
		$earning = $eps;
		while ($earning < $paybackPrice)
		{
			$eps = $eps * ($this->data["rule1_growth"] / 100 + 1); //gewachsenes eps
			$earning = $earning + $eps;
			$paybackTime++;
			
			if($paybackTime >= 100)
			{ 
				//Quasi-Endlosschleife abfangen, weil EPS durch negatives Rule1Growth immer kleiner wird
				return "> 100";
			}
		}
		
		return $paybackTime;
	}
	protected function calculatePaybackTimeMOS()
	{
		$this->data["paybacktime_mos"] = $this->calculatePaybackTime($this->getMOS(false));
	}
	public function getPaybackTimeMOS($localit = true, $precision = 2,  $printZero = false)
	{
		if(is_string($this->data["paybacktime_mos"])) // die > 100 abfangen
			return $this->data["paybacktime_mos"];
			
		if($localit)
            return $this->toNumber($this->data["paybacktime_mos"], $precision, $printZero);
        else 
            return $this->data["paybacktime_mos"];
	}
	public function getPaybackTimePrice($localit = true, $precision = 2,  $printZero = false, $price)
	{
		if(is_string($this->calculatePaybackTime($price))) // die > 100 abfangen
			return $this->calculatePaybackTime($price);
			
		if($localit)
            return $this->toNumber($this->calculatePaybackTime($price), $precision, $printZero);
        else 
            return $this->calculatePaybackTime($price);
	}
}