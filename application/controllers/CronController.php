<?php

/**
 * CronController
 * 
 * @author
 * @version 
 */

class CronController extends Zend_Controller_Action {
/*
 * Nur von localhost bzw. Admin zugänglich (per ACL)
 * 
 */
	public function jobAction() {
		
	}
	public function eodQuotesAction()
	{
		
		$crawlelist = array();
		
   		//Aktien auf den Märkten suchen
   		$table_ae = new AvailablestocksonexchangesModel();
   		$table_eod = new StockQuotesEODModel();
   		$stockex = new StockexchangesModel();
   		$companyModel = new CompaniesModel();
   		/*
   		 * 
   		 	SELECT * FROM `availablestocksonexchanges` as avs 
			LEFT JOIN stockquotes_eod as eod 
				on avs.company_id = eod.company_id 
				AND avs.market_id = eod.market_id 
				AND eod.date = "2009-12-10" 
			where date IS NULL 
			group by avs.company_id, avs.market_id
   		 */
   		$Zend_Date = new Zend_Date(time()-25*60); // 25 Minuten Zeitverzögert
		$date = $Zend_Date->get("yyyy-MM-dd");
   		$select = $table_ae->select()->setIntegrityCheck(false)
   					->from(array("avs" => $table_ae->getTableName()))
   					->joinLeft(array("eod" => $table_eod->getTableName()), 
   								'avs.company_id = eod.company_id 
								AND avs.market_id = eod.market_id 
								AND eod.date = "'.$date.'"',"date")
   					->join(array("sex" => $stockex->getTableName()), 'avs.market_id = sex.market_id', "symbolextension")
   					->join(array("c" => $companyModel->getTableName()), 'c.company_id = avs.company_id', "noquotes")
   					->where("c.noquotes IS NULL")
   					->where("eod.date IS NULL")
   					->where("sex.time_end <= ?", $Zend_Date->get(Zend_Date::HOUR).$Zend_Date->get(Zend_Date::MINUTE))
   					->group(array("avs.company_id","avs.market_id") );
   		//echo $select->__toString();
   		$rows_ae = $table_ae->fetchAll($select);
   		$i = 0;
   		if(count($rows_ae) > 0)
   		{
   			foreach($rows_ae as $row_ae)
   			{
   				$crawlelist[] = array(
   									"company_id" => $row_ae->company_id, 
   									"market_id" => $row_ae->market_id,
   									"symbol" => $row_ae->symbol,
   									"symbolextension" => $row_ae->symbolextension
   								);
   				$i++;
   				if($i >= 100)
   				{
   					set_time_limit(90);
   					//in hunderter pakete teilen
   					Quotes::crawleEODQuotes($crawlelist);
   					//zurücksetzen
   					$i = 0;
   					$crawlelist = array();
   					sleep(0.3);
   				}
   			}
   		}
       				
		
		//Quotes crawlen und einfügen		
		//print_r($crawlelist);
		Quotes::crawleEODQuotes($crawlelist);
		
		$this->preGenerateSignalsAction();

	}
	public function eodQuotesForceAction()
	{
		//$notToday = $this->_getParam("notToday");
		$notToday = true;
		
		$crawlelist = array();
		
   		//Aktien auf den Märkten suchen
   		$table_ae = new AvailablestocksonexchangesModel();
   		$stockex = new StockexchangesModel();
   		
   		//@TODO Nur Aktien, die noch keinen Kurs für heute haben
   		$select = $table_ae->select()->setIntegrityCheck(false)
   					->from(array("avs" => $table_ae->getTableName()))
   					->join(array("sex" => $stockex->getTableName()), 'avs.market_id = sex.market_id', "symbolextension");
   					
   		$rows_ae = $table_ae->fetchAll($select);
   		$i = 0;
   		if(count($rows_ae) > 0)
   		{
   			foreach($rows_ae as $row_ae)
   			{
   				$crawlelist[] = array(
   									"company_id" => $row_ae->company_id, 
   									"market_id" => $row_ae->market_id,
   									"symbol" => $row_ae->symbol,
   									"symbolextension" => $row_ae->symbolextension
   								);
   				$i++;
   				
   				if($i >= 100)
   				{
   					//in hunderter pakete teilen
   					Quotes::crawleEODQuotes($crawlelist, true, $notToday);
   					//zurücksetzen
   					$i = 0;
   					$crawlelist = array();
   				}
   			}
   		}
       				
		
		//Quotes crawlen und einfügen		
		//print_r($crawlelist);
		Quotes::crawleEODQuotes($crawlelist, true, $notToday);

	}
	public function historicalQuotesAction()
	{
		//Prüfen ob von irgendeiner aktie die historischen Kurse geholt werden müssen
		$table = new StockQuotesEODModel();
		$table_ae = new AvailablestocksonexchangesModel();
		
		/*
		 * 
		 * SELECT count(volume), ase.market_id, ase.company_id, ase.symbol as anzahl 
		 * FROM `availablestocksonexchanges` ase 
		 * left JOIN stockquotes_eod eod ON ase.market_id = eod.market_id AND ase.company_id = eod.company_id 

GROUP BY eod.market_id,  eod.company_id

having anzahl < 200
		 * 
		 */
		$select = $table->select()
		->setIntegrityCheck(false)
							->from(array('eod' => $table->getTableName()), array("anzahl" => "COUNT(volume)", "company_id", "market_id"))
							
							->joinRight(array('ase' => $table_ae->getTableName()),"ase.market_id = eod.market_id AND ase.company_id = eod.company_id")
							->having("anzahl < ?",200)
							->group(array("ase.company_id","ase.market_id"))
							;
		//echo $select->__toString();
		$this->view->cronMsg = array();
		$cid = $this->_getParam("CID");
		if($cid)
			$select->where("ase.company_id = ?", $cid);
		$rows = $table->fetchAll($select);
		if(count($rows) > 0)
		{
			foreach($rows as $row)
			{
				set_time_limit(60); //60 Sekunden pro Quote
				Quotes::crawleQuotes($row->company_id, $row->market_id, time(), 390);
				sleep(1);//Pause machen...
				$this->view->cronMsg[] = 'CID '.$row->company_id.' auf MID '.$row->market_id.' versucht zu crawln';
			}
		}
	}
	public function newCompaniesAction()
	{
		$tbl = new NewCompaniesModel();
		$select = $tbl->select()->where("date_add <= ?", time()-(2*24*60*60)); //Alles was schon zwei Tage drin ist
		
		$rows = $tbl->fetchAll($select);
		foreach($rows as $row)
		{

			$table_ae = new AvailablestocksonexchangesModel();
        	$select_ae = $table_ae->select()->where("company_id = ?",$row->company_id);
        	$rows_ae = $table_ae->fetchAll($select_ae);
        	foreach($rows_ae as $row_ae)
        	{
        		set_time_limit(60); //60 Sekunden pro Quote
        		Quotes::crawleQuotes($row_ae->company_id, $row_ae->market_id, time(), 390);
        		sleep(1);//Pause machen...
        	}
        	
			//Zum Schluss aus der Liste der Neuen löschen
			$row->delete();
		}
	}
	public function sendLogAction()
	{
		$mail = new Mail(Zend_Registry::get("config")->general->mail->from->default->email);
		
		if(file_exists("../logs/application.txt"))
		{
			//prüfen ob es was zu senden gibt	
			if(filesize("../logs/application.txt") != 0)
			{
				
				$files = array(
							array(
									"content" => file_get_contents("../logs/application.txt"), 
									"filename" => "application.txt"));
				$mail = $mail->sendApplicationLogsMail($files);
				
				//datei leeren
				$file = fopen("../logs/application.txt", "w+");
				fwrite($file, "");
				fclose($file);
			}		
			else 
				echo "nothing loggd";	
		}		
	}
	
	public function reviewStockMarketsForWrongMainMarketsAction()
	{
		$exch_table = new StockexchangesModel();
		$markets = $exch_table->fetchAll($exch_table->select()->from($exch_table, array("market_id"))
											->where('countrycode = "US"'));
		$selectMarkets = "";
		
		foreach ($markets as $row)
		{
			if($selectMarkets != "")
				$selectMarkets .= ' AND ';
			$selectMarkets .= 'main_market != '. $row->market_id;
		}
		
		$tbl = new CompaniesModel();
		$select = $tbl->select()
					->where("type = ?", 1) //nur aktien
					->order("date_reviewed_markets ASC") //erstmal die, die länger nicht betrachtet wurden
					->where("isin like 'US%'")
					->where('('.$selectMarkets.')');
		$rows = $tbl->fetchAll($select);
		
		foreach ($rows as $row)
		{
			echo $row->isin." ".$row->main_market."<br>";
			$this->reviewStockMarketsAction($row->company_id);
		}
	}
	public function reviewStockMarketsAction($company_id = null)
	{
		if($company_id != null)
			$cid = $company_id;
		else
			$cid = $this->_getParam("CID");
		
		$m = new CompaniesModel();
		$select = $m->select()->where("type = ?", 1)->order("date_reviewed_markets ASC")->limit(5);
		if($cid)
			$select->where("company_id = ?", $cid);
		$rows = $m->fetchAll($select);
		
		foreach ($rows as $row)
		{
			set_time_limit(60); //60 Sekunden pro Unternehmen
			
			$company_id = $row->company_id;
			
			$yf_search = new YahooFinanceStockSearch($row->isin, Zend_Registry::get('config')->general->proxy->toArray());
			
			if(($response = $yf_search->getResponseParsed()))
			{
				//irgendetwas gefunden, aber was?
				$tmp_row_isin = $response[0]["isin"]; //die erste ISIN als Vergleichswert
				$justonecompany = true;
				foreach($response as $respon)
				{
					if(!$respon["isin"] == $tmp_row_isin)
						$justonecompany = false;

				}
				if($justonecompany)
				{
					//Nur ein Unternehmen
					
					Zend_Registry::get('Zend_Db')->beginTransaction();
					try {
						// börsenplätze und symbole
						$exchanges = array(); // market
						foreach($response as $respon)
						{
							$exchanges[] = array(
												"exchange_name" => $respon["market"], 
												"symbol" => $respon["symbol"]
							);
						}
						$company = new Company();
						
						$crawlelist = $company->getCrawlelistForSupportedExchanges($company_id, $exchanges, $row->isin);
						
						$comp_exch_table = new StockQuotesEODModel();
						//bereits in DB enthaltene Märkte entfernen
						foreach ($crawlelist as $key => $item)
						{
							if($comp_exch_table->fetchRow($comp_exch_table
										->select()
											->where("market_id = ?", $item["market_id"])
											->where("company_id = ?", $company_id)
											))
							{
								unset($crawlelist[$key]);
							}
						}
						
						Quotes::firstInputCrawle($crawlelist);
						
						$company->kickMarketsWithLowVolumeAndSetMainMarket($company_id);
						
						$row->date_reviewed_markets = time();
						$row->save();
						
						Zend_Registry::get('Zend_Db')->commit();
					} catch (Zend_Exception $e) {
						Zend_Registry::get('Zend_Db')->rollBack();
					    throw new Zend_Exception($e->getMessage(). "\n" .  $e->getTraceAsString());
					}
	
						
					
				}
			}
		}			
	}
	public function reviewStockMarketsInternalAction()
	{
		$cid = $this->_getParam("CID");
		
		$m = new CompaniesModel();
		$select = $m->select()->where("type = ?", 1)->order("date_reviewed_markets ASC")->limit(5);
		if($cid)
			$select->where("company_id = ?", $cid);
		$rows = $m->fetchAll($select);
		
		
		foreach ($rows as $row)
		{
			$company_id = $row->company_id;
			
			Zend_Registry::get('Zend_Db')->beginTransaction();
			try {	
				$company = new Company();		
				$company->kickMarketsWithLowVolumeAndSetMainMarket($company_id);
				
				$row->date_reviewed_markets = time();
				$row->save();
				
				Zend_Registry::get('Zend_Db')->commit();
			} catch (Zend_Exception $e) {
				Zend_Registry::get('Zend_Db')->rollBack();
			    throw new Zend_Exception($e->getMessage(). "\n" .  $e->getTraceAsString());
			}
		}			
	}
	public function correctWatchlistMarketsAction()
	{
		$m = new CompaniesModel();
		$rows = $m->fetchAll($m->select()->where("type = ?", 1));
		$mw = new WatchlistCompaniesModel();
		
		foreach ($rows as $row)
		{
			$where = $mw->getAdapter()->quoteInto("company_id = ?", $row->company_id);
			$data = array("market_id" => $row->main_market);
			$mw->update($data, $where);
		}
	}
	public function historicalQuotesLastWeekAction()
	{
		//Prüfen ob von irgendeiner aktie die historischen Kurse geholt werden müssen
		$table = new StockQuotesEODModel();
		
		$date = new Zend_Date(time()-9*24*60*60, Zend_Date::TIMESTAMP);
		$dateStart = $date->get("yyyy-MM-dd");
		
		$date = new Zend_Date(time()-2*24*60*60, Zend_Date::TIMESTAMP);
		$dateEnd = $date->get("yyyy-MM-dd");
		
		$anzahlcrawls = 0;
                $anzahlCrawlsSuccesses = 0;

		/*
SELECT *, Count(*)  
FROM `stockquotes_eod` 
WHERE `date` > '2009-11-05' 
	AND (market_id = 1 OR market_id=6) 
group by company_id, market_id   
ORDER BY Count(*)  DESC limit 0, 1


SELECT company_id, market_id, Count( * ) AS anzahl
FROM `stockquotes_eod`
WHERE `date` > '2009-11-05'
AND (
market_id =1
OR market_id =6
)
GROUP BY company_id, market_id
HAVING anzahl <5
ORDER BY Count( * ) DESC
LIMIT 0 , 500
		 */	

                /********************************************/
		//DEUTSCHLAND
		$select = $table->select()
							->from($table, array("anzahl" => "COUNT(*)", "company_id", "market_id"))
							->group(array("company_id","market_id"))
							->where("date > ?", $dateStart)
							->where("date <= ?", $dateEnd)
							->where("(market_id = 1 OR market_id = 6 OR market_id = 11 OR market_id = 12)")
							->order("anzahl DESC")
							->limit(1)
							;
		$row = $table->fetchRow($select);
		$anzahlQuotes = $row->anzahl;
							
		$select = $table->select()
							->from($table, array("anzahl" => "COUNT(*)", "company_id", "market_id"))
							->group(array("company_id","market_id"))
							->where("date > ?", $dateStart)
							->where("date <= ?", $dateEnd)
							->where("(market_id = 1 OR market_id = 6 OR market_id = 11 OR market_id = 12)")
							->having("anzahl < ?", $anzahlQuotes)
							->order("anzahl DESC")
							;
		$rows = $table->fetchAll($select);
		if(count($rows) > 0)
		{
			foreach($rows as $row)
			{
				echo $row->company_id." ".$row->market_id."<br>";
				set_time_limit(60); //60 Sekunden pro Quote
				if(Quotes::crawleQuotes($row->company_id, $row->market_id, time(), 70))
                                        $anzahlCrawlsSuccesses++;
                                $anzahlcrawls++;
				sleep(1); //Pause machen...
			}
		}
		
                /********************************************/		
		//USA
		$select = $table->select()
							->from($table, array("anzahl" => "COUNT(*)", "company_id", "market_id"))
							->group(array("company_id","market_id"))
							->where("date > ?", $dateStart)
							->where("date <= ?", $dateEnd)
							->where("(market_id = 3 OR market_id = 4)")
							->order("anzahl DESC")
							->limit(1)
							;
		$row = $table->fetchRow($select);
		$anzahlQuotes = $row->anzahl;
							
		$select = $table->select()
							->from($table, array("anzahl" => "COUNT(*)", "company_id", "market_id"))
							->group(array("company_id","market_id"))
							->where("date > ?", $dateStart)
							->where("date <= ?", $dateEnd)
							->where("(market_id = 3 OR market_id = 4)")
							->having("anzahl < ?", $anzahlQuotes)
							->order("anzahl DESC")
							;
		$rows = $table->fetchAll($select);
		if(count($rows) > 0)
		{
			foreach($rows as $row)
			{
				echo $row->company_id." ".$row->market_id."<br>";
				set_time_limit(60); //60 Sekunden pro Quote
                                if(Quotes::crawleQuotes($row->company_id, $row->market_id, time(), 70))
                                        $anzahlCrawlsSuccesses++;
				$anzahlcrawls++;
				sleep(1); //Pause machen...
			}
		}
		
		
		/********************************************/
                //UK
		$select = $table->select()
							->from($table, array("anzahl" => "COUNT(*)", "company_id", "market_id"))
							->group(array("company_id","market_id"))
							->where("date > ?", $dateStart)
							->where("date <= ?", $dateEnd)
							->where("(market_id = 7)")
							->order("anzahl DESC")
							->limit(1)
							;
		$row = $table->fetchRow($select);
		$anzahlQuotes = $row->anzahl;
							
		$select = $table->select()
							->from($table, array("anzahl" => "COUNT(*)", "company_id", "market_id"))
							->group(array("company_id","market_id"))
							->where("date > ?", $dateStart)
							->where("date <= ?", $dateEnd)
							->where("(market_id = 7)")
							->having("anzahl < ?", $anzahlQuotes)
							->order("anzahl DESC")
							;
		$rows = $table->fetchAll($select);
		if(count($rows) > 0)
		{
			foreach($rows as $row)
			{
				echo $row->company_id." ".$row->market_id."<br>";
				set_time_limit(60); //60 Sekunden pro Quote
                                if(Quotes::crawleQuotes($row->company_id, $row->market_id, time(), 70))
                                        $anzahlCrawlsSuccesses++;
				$anzahlcrawls++;
				sleep(1); //Pause machen...
			}
		}
		
	        /********************************************/
		//Niederlande
		$select = $table->select()
							->from($table, array("anzahl" => "COUNT(*)", "company_id", "market_id"))
							->group(array("company_id","market_id"))
							->where("date > ?", $dateStart)
							->where("date <= ?", $dateEnd)
							->where("(market_id = 8)")
							->order("anzahl DESC")
							->limit(1)
							;
		$row = $table->fetchRow($select);
		$anzahlQuotes = $row->anzahl;
							
		$select = $table->select()
							->from($table, array("anzahl" => "COUNT(*)", "company_id", "market_id"))
							->group(array("company_id","market_id"))
							->where("date > ?", $dateStart)
							->where("date <= ?", $dateEnd)
							->where("(market_id = 8)")
							->having("anzahl < ?", $anzahlQuotes)
							->order("anzahl DESC")
							;
		$rows = $table->fetchAll($select);
		if(count($rows) > 0)
		{
			foreach($rows as $row)
			{
				echo $row->company_id." ".$row->market_id."<br>";
				set_time_limit(60); //60 Sekunden pro Quote
                                if(Quotes::crawleQuotes($row->company_id, $row->market_id, time(), 70))
                                        $anzahlCrawlsSuccesses++;
				$anzahlcrawls++;
				sleep(1); //Pause machen...
			}
		}
		
	
                /********************************************/
        	//Östereich
		$select = $table->select()
							->from($table, array("anzahl" => "COUNT(*)", "company_id", "market_id"))
							->group(array("company_id","market_id"))
							->where("date > ?", $dateStart)
							->where("date <= ?", $dateEnd)
							->where("(market_id = 9)")
							->order("anzahl DESC")
							->limit(1)
							;
		$row = $table->fetchRow($select);
		$anzahlQuotes = $row->anzahl;
							
		$select = $table->select()
							->from($table, array("anzahl" => "COUNT(*)", "company_id", "market_id"))
							->group(array("company_id","market_id"))
							->where("date > ?", $dateStart)
							->where("date <= ?", $dateEnd)
							->where("(market_id = 9)")
							->having("anzahl < ?", $anzahlQuotes)
							->order("anzahl DESC")
							;
		$rows = $table->fetchAll($select);
		if(count($rows) > 0)
		{
			foreach($rows as $row)
			{
				echo $row->company_id." ".$row->market_id."<br>";
				set_time_limit(60); //60 Sekunden pro Quote
				if(Quotes::crawleQuotes($row->company_id, $row->market_id, time(), 70))
                                        $anzahlCrawlsSuccesses++;
				$anzahlcrawls++;
				sleep(1); //Pause machen...
			}
		}
	
		/********************************************/
		//Schweiz
		$select = $table->select()
							->from($table, array("anzahl" => "COUNT(*)", "company_id", "market_id"))
							->group(array("company_id","market_id"))
							->where("date > ?", $dateStart)
							->where("date <= ?", $dateEnd)
							->where("(market_id = 10)")
							->order("anzahl DESC")
							->limit(1)
							;
		$row = $table->fetchRow($select);
		$anzahlQuotes = $row->anzahl;
							
		$select = $table->select()
							->from($table, array("anzahl" => "COUNT(*)", "company_id", "market_id"))
							->group(array("company_id","market_id"))
							->where("date > ?", $dateStart)
							->where("date <= ?", $dateEnd)
							->where("(market_id = 10)")
							->having("anzahl < ?", $anzahlQuotes)
							->order("anzahl DESC")
							;
		$rows = $table->fetchAll($select);
		if(count($rows) > 0)
		{
			foreach($rows as $row)
			{
				echo $row->company_id." ".$row->market_id."<br>";
				set_time_limit(60); //60 Sekunden pro Quote
                                if(Quotes::crawleQuotes($row->company_id, $row->market_id, time(), 70))
                                        $anzahlCrawlsSuccesses++;
				$anzahlcrawls++;
				sleep(1); //Pause machen...
			}
		}

                
                /********************************************/

		Zend_Registry::get('Zend_Log')->log($anzahlcrawls.' HistoryCrawls davon .'.$anzahlCrawlsSuccesses.' mit Erfolg', Zend_Log::NOTICE);
		
		
	}
	
	public function clearChartImagesAction()
	{
		$dir = Zend_Registry::get("config")->general->upload->chartImages->destination;
		if ($handle = opendir($dir)) {
		    while (false !== ($file = readdir($handle))) {
		        if ($file != "." && $file != ".." && $file != ".svn") 
		        {
		            unlink($dir.$file);
		        }
		    }
		    closedir($handle);
		}
	}
	public function preGenerateChartsAction()
	{
		$indikatoren = array(
							"SMA" => array(array("period" => 10), array("period" => 30), array("period" => 50)),
							"MACD" => array(array("fastEMA" => 8, "slowEMA" => 17, "signalEMA" => 9)),
							"STO" => array(array("k" => 14, "d" => 5, "type" => "slow"), array("k" => 14, "d" => 5, "type" => "fast"))
						);
						
		$m = new LogProfilerModel();
		$select = $m->select()
					->from($m, array("anzahl" => "COUNT(*)", "uri"))
					->where("uri like ?", "%/api/charturls/CID/%")
					->group("uri")
					->order("anzahl DESC")
					->limit(20)
					;
		$rows = $m->fetchAll($select);
		if(count($rows) > 0)
		{
			foreach ($rows as $row)
			{
				preg_match("#/CID/(\d+)(\?PERIOD=(\d+))?#", $row->uri, $data);
				$cid = $data[1];
				if(isset($data[3]))
					$days = $data[3];
				else 	
					$days = 60;
				
				$company = new Company($cid);
				if($company->_isInit(false))
				{
					if($company->getMainMarketId())
					{
						$charts = new ChartSet($company, $company->getMainMarketId(), $indikatoren, $days);
		    		
		    			$charts->getUrls();						
					}					
				}
			}
		}
		//print_r($rows);
	}
	public function preGenerateSignalsAction()
	{
		
		/**
		 * 
		 SELECT company_id, count( * ) AS anzahl, user_id, indikator_sma
		FROM `watchlist_companies` AS wc
		JOIN watchlist AS w ON w.watchlist_id = wc.watchlist_id
		JOIN users AS u ON u.user_id = w.owner_id
		GROUP BY company_id, market_id, indikator_sma
		HAVING anzahl >1
		ORDER BY `anzahl` DESC
		 * 
		 */
		$wcm = new WatchlistCompaniesModel();
		$wm = new WatchlistModel();
		$um = new UsersModel();
		
		$select = $wcm->select()->setIntegrityCheck(false)
			->from(array('wc' => $wcm->getTableName()), array('anzahl' => 'count(*)', 'company_id'))
			->join(array('w' => $wm->getTableName()), 'w.watchlist_id = wc.watchlist_id', array())
			->join(array('u' => $um->getTableName()), 'u.user_id = w.owner_id', array('indikator_sma', 'user_id'))			
			->group(array('company_id', 'market_id', 'indikator_sma'))
			->order('anzahl DESC')
			->having('anzahl > 1')
			->limit(500)
			;
		
		$list = $wcm->fetchAll($select);	
				
		foreach ($list as $stock)
		{
			set_time_limit(90);
			
			$company = new Company($stock->company_id);
			
			$market = new Market($company->getMainMarketId());
						
            $quotes = new Quotes($company, $market);
            
            $quotes->getIndikatorSignal(new User($stock->user_id));
			
			sleep(0.8);
		}
	}
	public function sendSignalMailsAction()
	{
		set_time_limit(300);
		
		$mailLogModel = new MailSignalLogModel();
		
		$uID = $this->_getParam("UID");
		
		$tmpdate = $this->_getParam("DATE");
		$mailDate = new Zend_Date();
		
		if($tmpdate)
			$mailDate->set($tmpdate, Zend_Date::ISO_8601); // 2010-08-06 z.B.
			
		$mailDateInIso = $mailDate->get('yyyy-MM-dd');
		
		sleep(rand(0,15)); //Komplett gleichzeitiges Ausführen verhindern...
					
		//Alle Nutzer holen
		$m = new UsersModel();
		$select = $m->select()->where('status = 1');
		
		if($uID)
			$select->where("user_id = ?", $uID);
		
		$users = $m->fetchAll($select);
	
		$anzahlmails = 0;
		$anzahlmailsReal = 0;
		
		//Pro Nutzer eine Mail
		foreach ($users as $user)
		{
			set_time_limit(300); //300 Sekunden pro Mail
			//sleep(1);
			
			//Prüfen ob bereits in Mail-Log enthalten
			if(!$mailLogModel->findUserIdAndDate($user->user_id, $mailDateInIso))
			{
				$userObj = new User($user->user_id);
				
				$lists = array();
				
				//Watchlists des Nutzers holen
				$watchlists = $userObj->getWatchlists();
				
				foreach ($watchlists as $watchlist)
				{
					//prüfen ob Signal gewünscht
					if($watchlist->sendSignalMail())
					{
						//Objekt zur Mail hinzufügen
						$lists[] = $watchlist;
					}
				}
							
				//Depots des Nutzers holen
				$depots = $userObj->getPortfolios();
				foreach ($depots as $portfolio)
				{
					//prüfen ob Signal gewünscht
					if($portfolio->sendSignalMail())
					{
						//Objekt zur Mail hinzufügen
						$lists[] = $portfolio;
					}
				}
				
				if(count($lists) > 0)
				{	
					$anzahlmailsReal++;
					
					try {						
						$mail = new Mail($userObj->getEmail());
						$mail->sendSignalMail(array("lists" => $lists, "user" => $userObj, "zdate" => $mailDate));
						//if($mailsend)
							$anzahlmails++;	

						//In DB loggen
						$mailLogModel->insertMaillog($userObj->getUserId(), $mailDateInIso);
					}
					catch (Zend_Exception $e)
					{
						Zend_Registry::get('Zend_Log')
							->log($e->getMessage(). "\n" .  $e->getTraceAsString(), Zend_Log::NOTICE);
					}
				}				
			}
			else 
			{
				//bereits in Log enthalten...
				//nichts tuen
			}
			
						
			/**
			 * Signal-Kriterien
			 * 
			 * KAUF
			 * Alle Signale auf Kaufen + mind. bei 1 Signal: signal-date == lastquoate_date
			 * 
			 * VERKAUF
			 * 1. Alle Signale auf Verkaufen + mind. bei 1 Signal: signal-date == lastquoate_date 
			 * 2. MACD & STO auf Verkaufen + MACD oder STO von gestern + lastquate_change < 0,5
			 */
			
			//Mail senden			
		}
		
		Zend_Registry::get('Zend_Log')->log($anzahlmails." Signal-Mails queued.".$anzahlmailsReal, Zend_Log::NOTICE);

	}
	public function sendQueuedMailsAction()
	{
		$anzahlmails = 0;
		$table = new MailQueueModel();
		
		for($i=0; $i < 20; $i++) //Maximal 20 Mails pro Durchlauf
		{
			$rows = $table->fetchAll($table->select()->limit(1)); 
			foreach ($rows as $row)
			{
				set_time_limit(160); //160 Sekunden pro Mail
				
				$mail = unserialize($row->mail);
				$row->delete();
				
				$mailsend = Mail::sendRenderedMail($mail, true);
				sleep(1);
				
				if($mailsend)
					$anzahlmails++;	
			}	
		}
			
		if($anzahlmails > 0)
			Zend_Registry::get('Zend_Log')->log($anzahlmails.' Mails versandt.', Zend_Log::NOTICE);
	}
}
