<?php
/**
 * Klasse für Kursdaten (Quotes), abhängig von Company und Market
 *
 */
class Quotes extends Abstraction
{
	/**
	 * Company-Objekt
	 *
	 * @var Company
	 */
	protected $company;
	/**
	 * Market-Objekt
	 *
	 * @var Market
	 */
	protected $market;
	/**
	 * Quotes_Quote-Objekt des letzten Kurses
	 *
	 * @var Quotes_Quote|NULL
	 */
	protected $_lastQuote = null;
	
	/**
	 * Konstruktor
	 *
	 * @param Company $company
	 * @param Market $market
	 */
	public function __construct(Company $company, Market $market)
	{
		$this->company = $company;
		$this->market = $market;
	}
	/**
	 * Holt das Quote Objekt zum letzten Kurs
	 *
	 * @return Quotes_Quote
	 */
	public function getLastQuote()
	{
		
		if(!($this->_lastQuote instanceof Quotes_Quote))
		{
			$this->_lastQuote = new Quotes_Quote($this->company, $this->market);
			$this->_lastQuote->getLastQuote();
		}
		
		return $this->_lastQuote;
	}
	/**
	 * Gibt alle Quotes eines bestimmten Zeitraums zurück
	 *
	 * @param STRING till, im Format date("Y-m-d")
	 * @param INT $days
	 * @return ARRAY|FALSE
	 */
	public function getQuotes($till, $days)
	{
		$table = new StockQuotesEODModel();
		
		$select = $table->select()->where("date <= ?", $till)
								->where("company_id = ?", $this->company->getId())
								->where("market_id = ?", $this->market->getId())
								->order("date DESC")
								->limit($days);
		
		//echo $select->__toString();
		
		$rows = $table->fetchAll($select);
		if(count($rows) > 0)
			return array_reverse($rows->toArray());
		else
			return false;
	}
	/**
	 * Gibt die Währung zurück
	 *
	 * @return STRING
	 */
	public function getCurrency()
	{
		return $this->market->getCurrency();
	}
	/**
	 * Gibt das Company-Objekt zurück
	 *
	 * @return Company
	 */
	public function getCompany()
	{
		return $this->company;
	}
	/**
	 * Gibt das Market-Objekt zurücl
	 *
	 * @return Market
	 */
	public function getMarket()
	{
		return $this->market;
	}
	
	public function getIndikatorSignal(User $user)
	{
		$indikators = $user->getSignalIndikators();

		$period = $indikators["SMA"][0]["period"] * 6;						
		$quote = $this->getLastQuote();
		if($quote->getDate() !== null)
		{	
			$identifier = Zend_Registry::get('systemtype')."_indikatoren"
			."_"
			.$this->getCompany()->getId()
			."_"
			.$this->getMarket()->getId()
			."_"
			.$period
			."_"
			.str_replace("-","_",$quote->getDate())
			;
			
			ksort($indikators);
			foreach($indikators as $kat => $indi_kat)
			{
				$identifier .= "_".$kat;
				foreach($indi_kat as $indi)
				{
					foreach($indi as $key => $value)
					{
						$identifier .= "_".$value;
					}
				}
			}
			
			$cache = Zend_Registry::get('Zend_Cache_Core');
			//echo $identifier;
			if (!($data = $cache->load($identifier))) {
				$num_datasets = Indikator::expectedDataSets($period, $indikators);
				if($results = $this->getQuotes(date("Y-m-d"), $num_datasets))
				{
					$indicators = new Indikator($results, $indikators);
					$data = $indicators->getLastSignals();
				}
				else
					$data = false; //konnte keine Daten holen
				//echo "no cache";	
    			$cache->save($data, $identifier, array('indikatoren'), 129600);  //Cache-Lifetime 36h  	
    		}
			return $data;
		}
		else 
			return false;
	
	}


	
/***********************************************************************************************
** STATIC PART
************************************************************************************************/	
	/**
	 * Erster Crawle einer Aktie
	 *
	 * @param ARRAY $crawlelist mit $crawle["company_id"], $crawle["market_id"]
	 */
	public static function firstInputCrawle($crawlelist)
	{
		$till = time();
		$days = 400;
		
		foreach($crawlelist as $crawle)
		{
			Quotes::crawleQuotes($crawle["company_id"], $crawle["market_id"], $till, $days);
		}
		
		//Aktuelle Kursdaten holen (wenn möglich)
		Quotes::crawleEODQuotes($crawlelist);
		
	}
	
	/**
	 * Kurs-Historie holen (von AUSSEN)
	 *
	 * @param INT $company_id
	 * @param INT $market_id
	 * @param INT timestamp
	 * @param INT $days
	 */
	public static function crawleQuotes($company_id, $market_id, $till, $days = 350)
	{
                $didSomething = false;
		//Symbol holen 
		$symbol = Quotes::_getSymbol($company_id, $market_id);
		if($symbol)
		{
    		//holen
    		$vendor = new YahooFinanceStockHistoricalQuotes($symbol, Zend_Registry::get('config')->general->proxy->toArray(), $till, $days);
    		//Array mit werten holen
    		$response = $vendor->getResponse();		
    	    for($i=0; $i < count($response); $i++)
    		{
         		//in DB einfügen
        		$table = new StockQuotesEODModel();
        		
        		if($response[$i]["date"] != "0000-00-00" 
        			&& $response[$i]["open"] != 0 
        			&& $response[$i]["last_price"] != 0)
				{
					//prüfen ob bereits in DB
	        		if(!$table->isAllreadyInside($company_id, $market_id, $response[$i]["date"]))
	        		{
	            		//einfügen
	            		try {
	            			$table->insert(array(
	            									"company_id" => $company_id,
	            									"market_id" => $market_id,
	            									"open" => $response[$i]["open"],
	                                        		"close"=> $response[$i]["last_price"],
	                                        		"high" => $response[$i]["high"],
	                                        		"low" => $response[$i]["low"],
	                                        		"date" => $response[$i]["date"],
	                                        		"volume" => $response[$i]["volume"]
	            							));
                                        $didSomething = true;
	            		}
	            		catch (Zend_Exception $e)
	            		{
	            			//Zend_Registry::get('Zend_Log')->log($e->getMessage(). "\n" . 'C:"'.$company_id.'" ' . 'M:"'.$market_id.'" ' . 'D:"'.$response[$i]["date"].'" ' , Zend_Log::ERR);	
	            		}
	        		}
	        		else
	        		{
	        			//nix ausgeben?
	        		}	
				}
	        		
    		}   		
		}
                
                return $didSomething;

	}

	/**
	 * Aktuellen Kurs holen (von AUSSEN)
	 *
	 * @param ARRAY Liste der Unternehmen und Märkte
	 */
	public static function crawleEODQuotes($crawlelist, $force = false, $notToday = false)
	{
            
		$crawlelist_count = count($crawlelist); 
		if($crawlelist_count > 0 && isset($crawlelist[0]))
		{
    		//Markets holen und prüfen ob bereits geschlossen
    		$stockex = new StockexchangesModel();
    		$date = new Zend_Date(time()-23*60); // 23 Minuten Zeitverzögert
    		if(!$force)
    		{
    			$select = $stockex->select()->where("time_end <= ?", $date->get(Zend_Date::HOUR).$date->get(Zend_Date::MINUTE)); 
	    		$rows = $stockex->fetchAll($select);
    		}
    		else 
    		{
    			$rows = $stockex->fetchAll();
    		}

    		$availiblemarkets = array();
    		foreach($rows as $row)
    		{
    			$availiblemarkets[$row->market_id] = true;
    		}
			
    		//Prüfen ob Marktdaten zu diesem Zeitpunkt überhaupt verfügbar, ggf. Markt aus crawlelist löschen
    		$new_crawelist = array();
    		foreach($crawlelist as $crawle)
    		{
                    if(isset($availiblemarkets[$crawle["market_id"]]))
                        $new_crawelist[] = $crawle;
    		}
		
                $crawlelist = $new_crawelist;

                $StockQuotesEODModel = new StockQuotesEODModel();

    		$crawlelist_count = count($crawlelist); 
    		if($crawlelist_count > 0 && isset($crawlelist[0]))
    		{
                    $symbollist = Quotes::_createSymbolList($crawlelist);
                    $ySymbollist = $symbollist; //extra liste für Yahoo
                    
                    //DJI kommt von Google, daher ausnahme nötig - PART 1
                    $djiSearch = array_search("^DJI", $symbollist);
                                           
                    if($djiSearch !== FALSE)
                    {
                        $gVendor = new GoogleFinanceStockQuotes(array("^DJI")); //Google ohne Proxy
                        $gResponse = $gVendor->getResponse();
                        unset($ySymbollist[$djiSearch]); //darf dann nicht mehr in der Yahoo liste sein
                    }
                    
                    //Datenvendor initialisieren
                    $vendor = new YahooFinanceStockQuotes($ySymbollist, Zend_Registry::get('config')->general->proxy->toArray());
                    //Array mit werten holen
                    $response = $vendor->getResponse();	
                    
                    //DJI kommt von Google, daher ausnahme nötig - PART 2
                    if($djiSearch !== FALSE)
                    {
                        $response = array_merge($response, $gResponse); // Google Response den anderen beimischen
                    }
                    
                    $response_count = count($response);

                    $symbolstreichliste = $symbollist;

                    //in DB einfügen

               	
                    //print_r($crawlelist);
                    print_r($symbollist);
                    print_r($response);

                    if($notToday !== false)
                    {
                            //heutiges Datum bauen
                            $todayZendDate = new Zend_Date(); 
                            $todayDate = $todayZendDate->get("yyyy-MM-dd");
                            $todayDate2 = $todayZendDate->get("yyyy-M-d");
                    }
               	
       		   	for($i=0; $i < $response_count; $i++)
       			{
       				//Falls Anzahl der Datensätze nicht übereinstimmt muss richtige ID gesucht werden
       				$searchID = array_search($response[$i]["symbol"], $symbollist);
       				if ($searchID !== FALSE)
       				{
       					//echo $response[$i]["symbol"]. " ".$searchID;
       					
       					unset($symbolstreichliste[$searchID]);
       					
       					if($response[$i]["date"] != "0000-00-00" 
		        			//&& $response[$i]["open"] != 0 
		        			&& $response[$i]["last_price"] != 0)
						{
                                                    $comitInsert = true;

                                                    if($notToday !== false)
                                                    {
                                                            if($response[$i]["date"] == $todayDate || $response[$i]["date"] == $todayDate2)
                                                                    $comitInsert = false; 
                                                    }

                                                    //prüfen ob bereits in DB
                                                    if($comitInsert == true
                                                            && !$StockQuotesEODModel->isAllreadyInside($crawlelist[$searchID]["company_id"], $crawlelist[$searchID]["market_id"], $response[$i]["date"]))
                                                    {
                                                         if($response[$i]["open"] == "N/A") //manchmal wird liefert yahoo kein open
                                                            $response[$i]["open"] = null;

                                                         //einfügen
                                                         $insert = $StockQuotesEODModel->insert(array(
                                                                                            "company_id" => $crawlelist[$searchID]["company_id"],
                                                                                            "market_id" => $crawlelist[$searchID]["market_id"],
                                                                                            "open" => $response[$i]["open"],
                                                                                            "close"=> $response[$i]["last_price"],
                                                                                            "high" => $response[$i]["high"],
                                                                                            "low" => $response[$i]["low"],
                                                                                            "date" => $response[$i]["date"],
                                                                                            "volume" => $response[$i]["volume"]
                                                                                            ));	        		
                                                    }
                                                    else
                                                    {
                                                            //nix ausgeben?
                                                    }
						}
       				}
       				else
       				{
       					//Das sollten wir loggen - wobei dieser Fall eigentlich nie passieren sollte
       					Zend_Registry::get('Zend_Log')->log('Es wurde ein Symbol '.$response[$i]["symbol"].' nicht in der Liste gefunden.', Zend_Log::NOTICE);
       				}
         		}
         		if($response_count != $crawlelist_count)
         		{
         			//Nicht alle Datensätze konnten geholt werden. das sollten wir loggen  
         			$symbolString = "";
         			foreach ($symbolstreichliste as $missingSymbol)
         				$symbolString .= " ".$missingSymbol;
       				Zend_Registry::get('Zend_Log')->log($crawlelist_count-$response_count.' Symbole wurden nicht gecrawlt: '.$symbolString, Zend_Log::NOTICE);
         		}
         		else 
         		{
         			//erflogreicher crawl 
         			$symbolString = "";
         			foreach ($symbollist as $missingSymbol)
         				$symbolString .= " ".$missingSymbol;
         			//Zend_Registry::get('Zend_Log')->log($crawlelist_count.' Symbole wurde gecrawlt: '.$symbolString, Zend_Log::NOTICE);
         		}
  		
    		}


		}

	}

	/**
	 * Symbol passend zur company_id und der market_id finden
	 *
	 * @param INT $company_id
	 * @param INT $market_id
	 * @return STRING|FALSE 
	 */
	public static function _getSymbol($company_id, $market_id)
	{
		$table = new AvailablestocksonexchangesModel();
		$select = $table->select()->where("market_id = ?", $market_id)
							->where("company_id = ?", $company_id);
		$row = $table->fetchRow($select);
		if($row)
		{
			$symbol = $row->symbol;
			//Hole Symbol-Extension
			$table = new StockexchangesModel();
			$select = $table->select()->where("market_id  = ?", $market_id);
			$row = $table->fetchRow($select);
			if($row)
			{
				if($row->symbolextension != null)
					$symbol = $symbol.".".$row->symbolextension;
				//Wenn keine Esxtension, dann kanns so bleiben
			}
			else
				$symbol = false;	
		
		}
		else
			$symbol = false;
		return $symbol;
	}
	/**
	 * Erstellt ein Array mit den Symbolen anhand einer Crawlelist
	 *
	 * @param ARRAY $crawlelist
	 * @return ARRAY
	 */
	public static function _createSymbolList($crawlelist)
	{
		$symbollist = array();
		$crawlelist_count = count($crawlelist); 
		for($i=0; $i < $crawlelist_count; $i++)
		{
			//Symbol holen
			if(!empty($crawlelist[$i]["symbol"]))
			{
				if(!empty($crawlelist[$i]["symbolextension"]))
					$symbollist[] = $crawlelist[$i]["symbol"].".".$crawlelist[$i]["symbolextension"];
				else 
					$symbollist[] = $crawlelist[$i]["symbol"];
			}
			else 
			{	//alternativ nochmal manuell versuchen
				$symbollist[] = Quotes::_getSymbol($crawlelist[$i]["company_id"], $crawlelist[$i]["market_id"]);	
			}		
		}
		
		return $symbollist;
	}

}