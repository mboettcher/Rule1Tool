<?php
/**
 * Überklasse für alle Charts
 *
 */
class ChartSet extends Abstraction
{
	protected $company_id = null;
	/**
	 * Company-Objekt
	 *
	 * @var Company
	 */
	protected $company = null;
	protected $market_id = null;
	/**
	 * Market-Objekt
	 *
	 * @var Market
	 */
	protected $market = null;
	protected $indikatoren = null;
	protected $period = null;
		
		/*
		 * Erwarte einen Array mit Daten
		 
		$indis = array(
					"EMA" => array(array("period" => 10), array("period" => 120)),
					"SMA" => array(array("period" => 10)),
					"MACD" => array(array("fastEMA" => 8, "slowEMA" => 17, "signalEMA" => 9)),
					"STO" => array(array("k" => 14, "d" => 5, "type" => "slow"))
		);*/
	
	protected $rawdata = null;
	protected $chart_data = null;
	
	protected $charts = null;
	
	/**
	 * Identifier für Cache
	 *
	 * @var STRING
	 */
	protected $identifier;
	/**
	 * Zend_Cache_Core-Objekt
	 *
	 * @var Zend_Cache_Core
	 */
	protected $cache;
	
	protected $colors = array("EEAA00", "b9b9b9", "ffa200", "2db722", "cccccc", "0f0f0f");
	protected $colorKurs = "226cb7";
	protected $colorChartBg = "ffffff";//"6699cc";
	
	protected $chartWidth = 600;
	protected $chartHeightKurs = 300;
	protected $chartHeightMACD = 150;
	protected $chartHeightSTO = 150;
	
	/*
	 * Chart-Margins
	 * chma=25,0,0,0
	 */
	protected $chartMargins = array(25,0,0,0);
	
	/**
	 * Datum des letzten Kurses
	 *
	 * @var Zend_Date
	 */
	protected $_chartDate;

	/**
	 * @param INT Company-ID
	 * @param INT Market-ID
	 * @param ARRAY Indikatoren
	 * @param INT Zeitraum in Tagen
	 */
	public function __construct($company, $market_id, $indikatoren, $anzeige_zeitraum = 60)
	{
		if($company instanceof Company)
			$this->company = $company;
		else 
			$this->_setCompanyId($company);
		$this->_setMarketId($market_id);
		$this->_setIndikatoren($indikatoren);
		$this->_setPeriod($anzeige_zeitraum);
		
		$this->_makeIdentifier();
		
		$this->cache = Zend_Registry::get('Zend_Cache_Core'); 
	}
	/**
	 * Erstellt und setzt den unique Identifier für den Cache
	 *
	 * @return BOOLEAN
	 */
	protected function _makeIdentifier()
	{
		//Datum des letzten Kurses ermitteln
		$quotes = new Quotes($this->_getCompany(), $this->_getMarket());
		$quote = $quotes->getLastQuote(false,true);
		if($quote->date !== null)
			$this->_chartDate = new Zend_Date($quote->date, Zend_Date::ISO_8601);
		else
		{
			$this->identifier = false;
			return false; //offensichtlich sind keine Daten vorhanden
		}
		
		$this->identifier = "chart"
		."_"
		.$this->_getCompany()->getId()
		."_"
		.$this->_getMarket()->getId()
		."_"
		.$this->period
		."_"
		.$this->_chartDate->get(Zend_Date::YEAR).$this->_chartDate->get(Zend_Date::MONTH).$this->_chartDate->get(Zend_Date::DAY)
		;
		/*
		$data = $this->indikatoren;
		ksort($data);
		foreach($data as $kat => $indi_kat)
		{
			$this->identifier .= "_".$kat;
			foreach($indi_kat as $indi)
			{
				foreach($indi as $key => $value)
				{
					$this->identifier .= "_".$value;
				}
			}
		}
		*/
		return true;
	}

	
	/**
	 * Gibt einen Array mit den HTML-Bild-Urls zurück
	 * @return ARRAY Urls-Tags
	 */
	public function getUrls($withoutSize = false)
	{
		$this->_isInit();
		$urls = $this->_getUrls();
		
		if($withoutSize)
		{
			foreach ($urls as $key => $url)
			{
				//&chs=600x300
				$urls[$key]["url"] = preg_replace('&\&chs=(\d+)x(\d+)&is', "", $urls[$key]["url"]);
			}
		}
		return $urls;
	}
	
	/**
	 * Hole die Kurs-Daten
	 * 
	 * @return BOOLEAN
	 */
	protected function _getRawData()
	{
		$num_datasets = Indikator::expectedDataSets($this->period, $this->indikatoren);

		$quotes = new Quotes($this->_getCompany(), $this->_getMarket());
		if($results = $quotes->getQuotes(date("Y-m-d"), $num_datasets))
		{
			$this->rawdata = $results;
			
			//Um zu verhindern, dass halbe Charts dargestellt werden
			$anzahlResults = count($this->rawdata);
			if($anzahlResults < $this->period)
				$this->period = $anzahlResults;
			
			$this->chart_data["kurs"] = array_slice($this->_getRawDataPart("close"), -$this->period);
			$this->chart_data["date"] = array_slice($this->_getRawDataPart("date"), -$this->period);
			$this->chart_data["date"] = $this->_prepareLabelDate();
			return true;
		}
		else
			return false; //konnte keine Daten holen
	}
	

	/**
	 * Hole die Indikatoren-Daten
	 * 
	 * @return BOOLEAN
	 */
	protected function _getIndikatoren()
	{
		if(empty($this->rawdata))
		{
			if(!$this->_getRawData())
				return false; //wenn keine Daten geholt werden konnten
		}
			
		$indicators = new Indikator($this->rawdata, $this->indikatoren);

		if($data = $indicators->getIndicators())
			$this->chart_data = array_merge($this->chart_data, $this->_sliceArrays($data));
			
		return true;
	}
	/**
	 * Erstellt die Chart-Objekte
	 *
	 * @return BOOLEAN
	 */
	protected function _getCharts()
	{
		if(empty($this->chart_data))
		{
			if(!$this->_getIndikatoren())
				return false; //konnte Indikatoren/Daten nicht holen
		}
			
		$this->charts = array();
		//Haupt-Chart

		$GphpChart = new GphpChart("lc","e");
		$GphpChart->setSize($this->chartWidth, $this->chartHeightKurs);
		$GphpChart->title = $this->_getCompany()->getName().' ('.$this->period." Tage), ".$this->_chartDate->get(Zend_Date::DATES); // this title will be on the chart image
		$GphpChart->setAxis(array("x","y")); //no arguments means all on
    	$GphpChart->setAxisRange(array(0, 0, $this->period));
    	$GphpChart->setAxisRangeMaxMin(1);
    	$GphpChart->addAxisLabel(array_values($this->chart_data["date"]));
    	$GphpChart->addLabelPosition(array_merge(array(0), array_keys($this->chart_data["date"])));
    	$GphpChart->add_legend(array("Kurs"));
    	$GphpChart->setLegendPosition("t");
		$GphpChart->add_data($this->chart_data["kurs"], $this->colorKurs); // adding values
		$GphpChart->fill("bg","lg","90,".$this->colorChartBg.",0.9,ffffff,0.5");
		$GphpChart->add_grid("20,20,1,6");
		//$GphpChart->setChartMargins($this->chartMargins);
		
		$this->charts[0] = $GphpChart;
		
		$main_chart_color = 1;
		
		foreach($this->chart_data as $kat => $indikators)
		{
			if($kat == "SMA")
			{
    			foreach($indikators as $key => $indikator)
    			{
    				
    				foreach($indikator["data"] as $name => $data)
    				{
    					//print_r($data);
        				$this->charts[0] -> add_data($data, $this->colors[$main_chart_color]); // adding values
        				$this->charts[0] -> add_legend(array($indikator["name"]));   

        				$main_chart_color++;	
    				}
    				//Shape markers
    				if(is_array($indikator["signals"]))
    				{
    					foreach ($indikator["signals"] as $index => $signal)
	        			{
	        				if($signal !== null && $index != 0)
	        				{
	        					//echo $signal["signal"];
	        					//echo $index-1+$signal["diff"]. " ";
	        					//<marker type>,<color>,<data set index>,<data point>,<size>,<priority>
	        					if($signal["signal"] == "b")
	        						$signalColor = "2db722";
	        					else 
	        						$signalColor = "ff0000";
	        					//Der Index muss noch korrigiert werden, da sonst der Marker immer nach dem Schnittpunkt liegt
	        					$datapoint = $index-1+$signal["diff"];
	        					if($datapoint < 0) //ein minuswert würde eine konstante linie aus punkten bewirken
	        						$datapoint = 0 ;
	        					$this->charts[0] -> setRangeMarkers(array("o",$signalColor, $main_chart_color-1, $datapoint, "5"));		
	        				}
	                					
	        			}
    				}

    			}

			}
			elseif($kat == "EMA")
			{
			    foreach($indikators as $key => $indikator)
    			{
    				foreach($indikator["data"] as $name => $data)
    				{
    					//print_r($data);
        				$this->charts[0] -> add_data($data, $this->colors[$main_chart_color]); // adding values
        				$this->charts[0] -> add_legend(array($indikator["name"]));  
        				$main_chart_color++; 					
    				}
    				//Shape markers
    				if(is_array($indikator["signals"]))
    				{
    					foreach ($indikator["signals"] as $index => $signal)
	        			{
	        				if($signal !== null && $index != 0)
	        				{
	        					//<marker type>,<color>,<data set index>,<data point>,<size>,<priority>
	        					if($signal["signal"] == "b")
	        						$signalColor = "2db722";
	        					else 
	        						$signalColor = "ff0000";
	        					//Der Index muss noch korrigiert werden, da sonst der Marker immer nach dem Schnittpunkt liegt
	        					$this->charts[0] -> setRangeMarkers(array("o",$signalColor, $main_chart_color-1, $index-1+$signal["diff"], "5"));		
	        				}
	                					
	        			}
    				}
	        			
    			}			
			}
			elseif($kat == "MACD")
			{
			    foreach($indikators as $key => $indikator)
    			{
    				$GphpChart = new GphpChart("lc","e");
            		$GphpChart->setSize($this->chartWidth, $this->chartHeightMACD);
            		$GphpChart->title = $this->_getCompany()->getName().' '.$indikator["name"]; // this title will be on the chart image
            		
                	$GphpChart->setAxis(array("x","y")); //no arguments means all on
                	$GphpChart->setAxisRange(array(0, 0, $this->period));
                
                	$GphpChart->setAxisRangeMaxMin(1);
                	
                	$GphpChart->addAxisLabel(array_values($this->chart_data["date"]));
    				$GphpChart->addLabelPosition(array_merge(array(0), array_keys($this->chart_data["date"])));
                
                	$GphpChart->add_legend(array("MACD", "EMA", "Signal"));
                	$GphpChart->setLegendPosition("t");
              		
            		$GphpChart->fill("c","lg","65,".$this->colorChartBg.",0,ffffff,0.95");
            		//$GphpChart->fill("bg","s","b9b9b9");
            		$GphpChart->add_grid("20,20,1,6");
            		$GphpChart->setChartMargins($this->chartMargins);
            		
            		$i = 1;
            		
    				foreach($indikator["data"] as $name => $data)
    				{
        				$GphpChart -> add_data($data, $this->colors[$i]); // adding values
						$i++;
    				}
    				$GphpChart->setZeroLine();
    				
    				
    				//Shape markers
    				if(is_array($indikator["signals"]))
    				{
    					foreach ($indikator["signals"] as $index => $signal)
	        			{
	        				if($signal !== null && $index != 0)
	        				{
	        					//<marker type>,<color>,<data set index>,<data point>,<size>,<priority>
	        					if($signal["signal"] == "b")
	        						$signalColor = "2db722";
	        					else 
	        						$signalColor = "ff0000";
	        					//Der Index muss noch korrigiert werden, da sonst der Marker immer nach dem Schnittpunkt liegt
	        					$GphpChart -> setRangeMarkers(array("o",$signalColor, 2, $index-1+$signal["diff"], "5"));
	        				}
	                					
	        			}
    				}
    				$this->charts[] = $GphpChart;
    			}				
			}
			elseif($kat == "STO")
			{
			    foreach($indikators as $key => $indikator)
    			{
    				$GphpChart = new GphpChart("lc","e");
            		$GphpChart->setSize($this->chartWidth, $this->chartHeightSTO);
            		$GphpChart->title = $this->_getCompany()->getName().' '.$indikator["name"]; // this title will be on the chart image
                	$GphpChart->setAxis(array("x","y")); //no arguments means all on
                	$GphpChart->setAxisRange(array(0, 0, $this->period));
                	$GphpChart->setAxisRangeMaxMin(1);
                	$GphpChart->addAxisLabel(array_values($this->chart_data["date"]));
    				$GphpChart->addLabelPosition(array_merge(array(0), array_keys($this->chart_data["date"])));
                	$GphpChart->add_legend(array("K(14)", "D(5)"));
                	$GphpChart->setLegendPosition("t");
            		$GphpChart->fill("c","lg","65,".$this->colorChartBg.",0,ffffff,0.95");
            		//$GphpChart->fill("bg","s","b9b9b9");
            		$GphpChart->add_grid("20,20,1,6");
            		$GphpChart->setChartMargins($this->chartMargins);
            		
            		$i = 1;
            		
    				foreach($indikator["data"] as $name => $data)
    				{
        				$GphpChart -> add_data($data, $this->colors[$i]); // adding values
        				$i++;
    				}
    				
    				//Shape markers
    				if(is_array($indikator["signals"]))
    				{
    					foreach ($indikator["signals"] as $index => $signal)
	        			{
	        				if($signal !== null && $index != 0)
	        				{
	        					//<marker type>,<color>,<data set index>,<data point>,<size>,<priority>
	        					if($signal["signal"] == "b")
	        						$signalColor = "2db722";
	        					else 
	        						$signalColor = "ff0000";
	        					//Der Index muss noch korrigiert werden, da sonst der Marker immer nach dem Schnittpunkt liegt
	        					$GphpChart -> setRangeMarkers(array("o",$signalColor, 1, $index-1+$signal["diff"], "5"));		
	        				}
	                					
	        			}
    				}
    				
    				$this->charts[] = $GphpChart;
    			}				
			}



		}
		
		return true;

	}
	/**
	 * Holt die Urls
	 * 
	 * @return STRING|FALSE
	 */
	protected function _getUrls()
	{
		if(!$this->identifier) //prüfen ob identifier berechnet
			return false;

		$destinationDir = Zend_Registry::get("config")->general->upload->chartImages->destination;	
		$imagesBaseUrl = Zend_Registry::get("config")->general->upload->chartImages->urlshort;
		
		$endung = ".png";
		$pathUrlPart1 = $imagesBaseUrl.$this->identifier."_";
		$pathDestinationDirPart1 = $destinationDir.$this->identifier."_";
		
		if(!file_exists($pathDestinationDirPart1."1".$endung))
		{
			if(empty($this->charts))
    		{
    			if(!$this->_getCharts())
    			{
    				return false; //Irgendwas stimmt nicht
    			}
    		}
    		
			$urls = array();
    		$i=1;
    		foreach($this->charts as $chart)
    		{
    			$client = new Zend_Http_Client();
				$client->setUri("http://chart.apis.google.com/chart");
				$client->setConfig(array(
			   		'maxredirects' => 2,
			    	'timeout'      => 30
				));
				$client->request('POST');
				
				$data = $chart->getChartArray();
				
				foreach ($data as $k => $v)
					$client->setParameterPost($k, $v);
				
				$response = $client->request();

				if ($response->getStatus() == 200) {
				  
					$fp = fopen($pathDestinationDirPart1.$i.$endung,'w+');  
				    if($fp) 
				      {
				      fwrite($fp,$response->getBody());
				      fclose($fp);
				      }
				} 
				
    			$urls[] = $pathUrlPart1.$i.$endung;
    			$i++;
    		}
		}
		else 
		{
			$urls = array(
				$pathUrlPart1."1".$endung,
				$pathUrlPart1."2".$endung,
				$pathUrlPart1."3".$endung,
				$pathUrlPart1."4".$endung
			);
		}
			
		return $urls;
	}
	
	/**
	 * Erstellt einen Array mit den Datums-Labels für die X-Achse
	 * 
	 * @return ARRAY
	 */
	protected function _prepareLabelDate()
	{
		$in_data_date = array();
		$date = null;
						
		for($i = 0; $i < count($this->chart_data["date"]); $i++)
		{
			if(fmod($i, round($this->period*0.1)) == 0)
			{
				$date = new Zend_Date($this->chart_data["date"][$i], Zend_Date::ISO_8601);
				$in_data_date[$i] = $date->get(Zend_Date::DAY)."/".$date->get(Zend_Date::MONTH);
			}
		}
		return $in_data_date;
	}


	
	
	
	
	
	
	
	/***************************************************************************************************************************************************
	 * Helper Functions
	 */
	/**
	 * Prüft ob Objekt initialisiert
	 *
	 * @return unknown
	 */
	protected function _isInit()
	{
		if($this->company !== null && $this->market !== null && $this->indikatoren !== null)
			return true;
		else
			throw new Zend_Exception("Klasse wurde nicht vollständig initialisiert!");
	}
	/**
	 * Setzt die CompanyId
	 *
	 * @param INT $company_id
	 */
	protected function _setCompanyId($company_id)
	{
		if(!empty($company_id))
			$this->company_id = $company_id;
		else
			throw new Zend_Exception("Ungültige Company-ID");
	}
	/**
	 * Gibt das Company-Objekt zurück
	 *
	 * @return Company
	 */
	protected function _getCompany()
	{
		if($this->company instanceof Company)
			return $this->company;
		else
		{
			$this->company = new Company($this->company_id);
			return $this->company;
		}
	}
	/**
	 * Setzt die MarketId
	 *
	 * @param INT $market_id
	 */
	protected function _setMarketId($market_id)
	{
		if(!empty($market_id))
			$this->market_id = $market_id;
		else
			throw new Zend_Exception("Ungültige Market-ID");
	}
	/**
	 * Gibt das Market-Objekt zurück
	 *
	 * @return Market
	 */
	protected function _getMarket()
	{
		if($this->market instanceof Market)
			return $this->market;
		else
		{
			$this->market = new Market($this->market_id);
			return $this->market;
		}
	}
	/**
	 * Setzt die Indikatoren
	 *
	 * @param ARRAY $indikatoren
	 */
	protected function _setIndikatoren($indikatoren)
	{
		if(!empty($indikatoren))
			$this->indikatoren = $indikatoren;
		else
			throw new Zend_Exception("Ungültige Indikatoren");
	}
	/**
	 * Setzt die Periode
	 *
	 * @param INT $period
	 */
	protected function _setPeriod($period)
	{
		if(!empty($period))
			$this->period = $period;
		else
			throw new Zend_Exception("Ungültiger Zeitraum");		
	}
	/**
	 * Setzt die Bildweite
	 *
	 * @param int $width
	 */
	public function setWidth(int $width)
	{
		$this->chartWidth = $width;
	}
	/**
	 * Kürzt alle Daten-Set auf die Anzahl der anzuzeigenden Sätze
	 * @param ARRAY Data von Indikator
	 */
	protected function _sliceArrays($data)
	{
		foreach($data as $kat => $indikator_kat)
		{
			foreach($indikator_kat as $n => $indikator)
			{
				foreach($indikator["data"] as $dataset => $indi_data)
				{
					$data[$kat][$n]["data"][$dataset] = array_slice($indi_data, -$this->period);
				}
				if(!empty($data[$kat][$n]["signals"]))
					$data[$kat][$n]["signals"] = array_slice($data[$kat][$n]["signals"], -$this->period);
			}
		}
		return $data;
	}
	/**
	 * Gibt einen Teil der Rohdaten zurück
	 *
	 * @param STRING $part
	 * @return ARRAY
	 */
	protected function _getRawDataPart($part)
	{
		$data = array();
		for($i = 0; $i < count($this->rawdata); $i++)
		{
			$data[] = $this->rawdata[$i][$part];
		}
		return $data;
	}
}