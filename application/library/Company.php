<?php
/*
 * Company
 */

class Company extends Abstraction
{
	//Eigenschaften
	protected $needles = array("company_id", 
						"name", 
						"isin", 
						"main_market", 
						"picture_id", 
						"group_id", 
						"wikipedia", 
						"website",
						"type");
	
	//Basics
	protected $name = null; //String
	protected $isin = null; //String
	protected $company_id = null; //INT
	protected $main_market = null;
	protected $picture_id = null;
	protected $group_id = null;
	
	protected $wikipedia = null;
	protected $website = null;
	
	protected $type;
	
	/**
	 * Array mit Quotes-Objekten
	 *
	 * @var ARRAY
	 */
	protected $quotes = array(); 
	
	protected $_quotesFetched = false;
	
	//Analyses
	protected $analyses_list = null; //Array
	protected $analyses_preselected = null; //INT ID
	protected $analyses_favourit = null; // INT ID
	
	//Thread
	protected $_thread_id;
	protected $_thread;

	//Models
	/**
	 * CompaniesModel-Objekt
	 *
	 * @var CompaniesModel
	 */
	protected $_CompaniesModel;
	/**
	 * AnalysisModel-Objekt
	 *
	 * @var AnalysisModel
	 */
	protected $_AnalysisModel;
	/**
	 * AnalysisFavouritsModel-Objekt
	 *
	 * @var AnalysisFavouritsModel
	 */
	protected $_AnalysisFavouritsModel;
	
	/**
	 * Company
	 *
	 * @param INT $company_id
	 * @param ARRAY CompanyDaten
	 */
	public function __construct($company_id = null, $data = null)
	{
		if($company_id != null)
			$this->getCompanyById($company_id);
		if($data != null)
			$this->setObjectData($data);
	}
	/**
	 * Prüft ob Objekt richtig initialisiert, wirft ansonsten Exception
	 * 
	 * @param BOOLEAN $throwException
	 *
	 * @return BOOLEAN
	 */
	public function _isInit($throwException = true)
	{
		if ($this->company_id !=  null)
		{
			return true;	
		}
		else
		{
			if($throwException)
				throw new Zend_Exception("ID noch nicht gesetzt");
			else 
				return false;
		}
	}
	/**
	 * Setzt die Objekt-Variablen
	 *
	 * @param ARRAY $data
	 */
	protected function setObjectData($data)
	{
		if(!isset($data["company_id"]))
			throw new Zend_Exception("CompanyId nicht enthalten");
			
		$needles = $this->needles;
		
		foreach ($needles as $key)
		{
			if(isset($data[$key]))
			{
				$this->$key = $data[$key];
			}
		}
	}
	/**
	 * Speichert die aktuellen Werte des Objekts in die DB
	 *
	 * @return INT
	 */
	public function save()
	{
		//Speichert Änderungen in DB
		$row = $this->_getCompaniesModel()->find($this->getId())->current();
		foreach ($this->needles as $key)
		{
			if(isset($row->$key))
			{
				$row->$key = $this->$key;
			}
		}
		return $row->save();
	}
	/**
	 * Holt Objekt-Daten anhand der ISIN
	 *
	 * @param STRING $isin
	 * @return BOOLEAN
	 */
	public function getCompanyByISIN($isin)
	{
		//Prüfe ISIN
		$validator = new Validate_Isin();

		if($validator->isValid($isin))
		{
			//Okay, weiter!
			$row = $this->_getCompaniesModel()->getAllDataByISIN($isin);
			if($row)
			{
				$this->setObjectData($row->toArray());

				$this->getThreadId();
				
	 			return true;
			}
			else
			{
				//ISIN nicht gefunden
				$this->_getMessageBox()->setMessage("MSG_COMPANY_001", $isin);
				return false;
			}		
		}
		else
		{
			$this->_getMessageBox()->setMessagesDirect($validator->getMessages());
			return false;
		}
	}
	/**
	 * Daten holen
	 *
	 * @param INT CompanyID
	 * @return Company
	 */
	public function getCompanyById($id)
	{
		$row = $this->_getCompaniesModel()->find($id)->current();
		if($row)
		{
			$this->setObjectData($row->toArray());
			
 			$this->getThreadId();
 			
 			return $this;
		}
		else
		{
			//@TODO man könnt auch eine Fehlermeldung ausgeben
			//throw new Zend_Exception("Company_Id nicht gefunden");
		}	
	}
	/**
	 * Gibt den UnternehmensName zurück
	 *
	 * @return STRING
	 */
	public function getName()
	{
		if($this->name === null)
			throw new Zend_Exception("Objekt nicht richtig initialisiert - Daten nicht vorhanden");
		return $this->name;
	}
	/**
	 * Gibt die ISIN zurück
	 *
	 * @return STRING
	 */
	public function getISIN()
	{
		if($this->isin === null)
			throw new Zend_Exception("Objekt nicht richtig initialisiert - Daten nicht vorhanden");
		return $this->isin;
	}
	/**
	 * Gibt den Ländercode (DE/US...) aus der ISIN zurück
	 *
	 * @return STRING
	 */
	public function getCountryCode()
	{
		return substr($this->getISIN(),0,2);
	}
	/**
	 * Gibt die Company-Id zurück
	 *
	 * @return INT
	 */
	public function getId()
	{
		if($this->company_id === null)
			throw new Zend_Exception("Objekt nicht richtig initialisiert - Daten nicht vorhanden");
		return $this->company_id;
	}
	/**
	 * Gibt die Website-Adresse zurück
	 *
	 * @return STRING
	 */
	public function getWebsite()
	{
		if($this->getId() === null)
			throw new Zend_Exception("Objekt nicht richtig initialisiert - Daten nicht vorhanden");
		return $this->website;
	}
	/**
	 * Gibt die URL zum Wikipedia-Eintrag zurück
	 *
	 * @return STRING
	 */
	public function getWikipedia()
	{
		if($this->getId() === null)
			throw new Zend_Exception("Objekt nicht richtig initialisiert - Daten nicht vorhanden");
		return $this->wikipedia;
	}
	/**
	 * Gibt die passende Gruppen-ID zurück
	 *
	 * @return INT
	 */
	public function getGroupId()
	{
		if($this->group_id === null)
			throw new Zend_Exception("Objekt nicht richtig initialisiert - Daten nicht vorhanden");
		return $this->group_id;
	}
	/**
	 * Gibt den MainMarket zurück
	 *
	 * @return Market
	 */
	public function getMainMarket()
	{
		throw new Zend_Exception("Funktion nicht verfügbar");
		//@TODO Funktion machen
		if($this->getId() === null)
			throw new Zend_Exception("Objekt nicht richtig initialisiert - Daten nicht vorhanden");
		return $this->main_market;	
	}
	/**
	 * Gibt die MainMarket-Id zurück
	 *
	 * @return INT
	 */
	public function getMainMarketId()
	{
		if($this->getId() === null)
			throw new Zend_Exception("Objekt nicht richtig initialisiert - Daten nicht vorhanden");
		return $this->main_market;	
	}
	/**
	 * Gibt den Typ der Aktie zurück, 1 = CompanyStock, 2 = Index
	 *
	 * @return INT
	 */
	public function getType()
	{
		$this->_isInit();
		
		return $this->type;
	}
	/**
	 * Pürft ob Akie ein CompanyStock
	 *
	 * @return BOOLEAN
	 */
	public function isStock()
	{
		if($this->getType() == 1)
			return true;
		else
			return false;
	}
	/**
	 * Pürft ob Akie ein Index
	 *
	 * @return BOOLEAN
	 */
	public function isIndex()
	{
		if($this->getType() == 2)
			return true;
		else
			return false;
	}
	/**
	 * Gibt die ImageId zurück
	 *
	 * @return INT
	 */
	public function getImageId()
	{
		return $this->picture_id;
	}
	/**
	 * Gibt ein PictureObjekt zurück
	 *
	 * @return Image
	 */
	public function getPicture()
	{
		$this->_isInit();
		
		return new Image($this->getImageId());
	}
	/**
	 * Setzt die Image-Id
	 *
	 * @param INT $image_id
	 */
	public function setImageId($image_id)
	{
		$this->picture_id = $image_id;
	}

	/**
	 * Gibt eine Url zur Unternehmensseite zurück, generiert nach Route
	 *
	 * @return STRING
	 */
	public function getUrl()
	{
		return Zend_Registry::get('Zend_View')->url(array(
							"language" => Zend_Registry::get('Zend_Locale')->getLanguage(), 
							"isin" => $this->getISIN()), 
						"stock");
	}
	
	/**
	 * Gibt ein Quotes-Objekt zurück
	 *
	 * @param INT $market_id
	 * @return Quotes
	 */
	public function getQuotes($market_id = null)
	{
		if($market_id !== null)
		{
			if(isset($this->quotes[$market_id]))
				return $this->quotes[$market_id];
			else
			{
				$this->_getQuotes();
					
				if(isset($this->quotes[$market_id]))
					return $this->quotes[$market_id];
				else
					return false;
			}
				
		}
		else
			return null;
	}
	/**
	 * Gibt ein Quotes-Objekt zurück
	 *
	 * @param STRING $currency
	 * @return Quotes
	 */
	public function getQuotesByCurrency($currency)
	{
		foreach ($this->quotes as $quotes)
		{
			if($quotes->getMarket()->getCurrency() == $currency)
				return $quotes;			
		}
		//Wenn nicht gefunden, dann Quotes-Liste aktualisieren und nochmal suchen
		if(!$this->_quotesFetched)
		{
			$this->_getQuotes();
			foreach ($this->quotes as $quotes)
			{
				if($quotes->getMarket()->getCurrency() == $currency)
					return $quotes;			
			}			
		}

		return false; //keinen passenden Markt gefunden
	}
	/**
	 * Holt die Quotes und speichert sie in die Objekt-Variable
	 *
	 */
	protected function _getQuotes()
	{
		//Liste der Märkte auf dem das Unternehmen gehandelt wird erstellen
		$tbl = new AvailablestocksonexchangesModel();
		$select = $tbl->select()->where("company_id = ?", $this->getId());
		$rows = $tbl->fetchAll($select);
		$this->quotes = array();
		foreach($rows as $row)
			$this->setQuotes(new Quotes($this, new Market($row->market_id)));
			
		$this->_quotesFetched = true;
	}
	
	public function setQuotes(Quotes $quotes)
	{
		$this->quotes[$quotes->getMarket()->getId()] = $quotes;
	}

	/**
	 * Gibt die Analysislist des Nutzers zurück
	 *
	 * @param INT $user_id
	 * @return ARRAY
	 */
	public function getAnalysesList($user_id)
	{
		if($this->analyses_list === null)
			$this->_getAnalysisList($user_id);
		return $this->analyses_list;
	}
	/**
	 * Gibt die Analysis-Id der persönlich vorselektierten Analyse zurück
	 *
	 * @param INT $user_id
	 * @return Analysis-Id
	 */	
	public function getPreselectedAnalysisId($user_id)
	{
		if($this->analyses_preselected === null)
			$this->_getPreselectedAnalysisId($user_id);
		return $this->analyses_preselected;
	}
	/**
	 * Holt die Liste der Analysen und speichert sie in die Objekt-Variable
	 *
	 * @param INT $user_id
	 */
	protected function _getAnalysisList($user_id)
	{
		$analyseslist = $this->_getKeydataanalysesModel()->getListOfAnalysesByCompanyId($this->getId(), $user_id);
		if($analyseslist)
		{
			$this->analyses_list = $analyseslist;
		}
		else
		{
			$this->analyses_list = false;
			$this->analyses_preselected = false;
		    $this->_getMessageBox()->setMessage("MSG_COMPANY_002");	
		}
	}
	/**
	 * Gibt die Favourit-Analyse zur Company und zum User zurück
	 *
	 * @param INT $user_id
	 * @return INT|BOOLEAN ID oder FALSE
	 */
	protected function getAnalysisFavourit($user_id)
	{
		if($this->analyses_favourit === null)
		{
			$analysis_fav_model = new AnalysisFavouritsModel();
			$select = $analysis_fav_model->select()->where("company_id = ?", $this->getId())
											->where("user_id = ?", $user_id);
			$row = $analysis_fav_model->fetchRow($select);
			if($row)
				$this->analyses_favourit = $row->analysis_id;
			else
				$this->analyses_favourit = false;		 //keine gesetzt	
		}
		
		return $this->analyses_favourit;		
	}
	/**
	 * Setzt den Analyse-Favourit für eine Company und User
	 *
	 * @param INT $analysis_id
	 * @param INT $user_id
	 * @return BOOLEAN true/false
	 */
	public function setAnalysisFavourit($analysis_id, $user_id)
	{
		$analysis_fav_model = new AnalysisFavouritsModel();
		$data = array("company_id" => $this->getId(), "user_id" => $user_id, "analysis_id" => $analysis_id);	
		if(!$this->getAnalysisFavourit($user_id))
			$res = $analysis_fav_model->insert($data);
		else 
			$res = $analysis_fav_model->update($data, array(
							$analysis_fav_model->getAdapter()->quoteInto('user_id = ?', $user_id), 
							$analysis_fav_model->getAdapter()->quoteInto('company_id = ?', $this->getId())));
							
		if($res)
		{
			$this->analyses_favourit = $analysis_id;
			$this->getMessageBox()->setMessage("MSG_COMPANY_003");
			return true;
		}
		else
		{
			$this->analyses_favourit = null;
			$this->getMessageBox()->setMessage("MSG_COMPANY_004");
			return false;
		}		
	}
	/**
	 * Ermittelt die vorselektierte AnalysisId
	 *
	 * @param INT $user_id
	 * @return INT
	 */
	protected function _getPreselectedAnalysisId($user_id)
	{
		if($this->analyses_list === null)
			$this->_getAnalysisList($user_id);
	    	    
	    //0. Testen ob überhaupt mehr als eine Analyse vorhanden
		if(count($this->analyses_list) > 1)
		{
			//1. Testen on Favourit festgeleget
			if(!($fav_id = $this->getAnalysisFavourit($user_id)))
			{
				//2. Testen ob eigene Analyse vorhanden
				$select = $this->_getKeydataanalysesModel()->select()->where("company_id = ?", $this->getId())
										->where("user_id = ?", $user_id)
										->order("date_edit DESC");
				if($row = $this->_getKeydataanalysesModel()->fetchRow($select))
					$fav_id = $row->analysis_id;
				else
				{
					//3. Analyse die am meisten Favouriten hat
					$fav_id = $this->_getAnalysisFavouritsModel()->getMostPopular($this->getId());
					if(!$fav_id)
					{
						//4. Zuletzt erstellte Analyse
						$fav_id = $this->analyses_list[0]["analysis_id"];
					}
				}
			
			
			}
		
		}
		else
			$fav_id = $this->analyses_list[0]["analysis_id"];
		
		if(!empty($this->analyses_list) && empty($fav_id))
		    throw new Zend_Exception("Konnte vorausgewählte Analyse nicht ermitteln!");

		$this->analyses_preselected = $fav_id;
	    //ID zurückgeben
		return $fav_id;
	}
	/**
	 * Gibt den Gruppen-Tread zurück
	 *
	 * @return Group_Thread
	 */
	public function getThread()
	{
   		if(!($this->_thread instanceof Group_Thread))
   			$this->_thread = new Group_Thread($this->_thread_id);
   		return $this->_thread;
	}
	/**
	 * Holt Thread zur aktuellen Sprache
	 *
	 * @return BOOLEAN|EXCEPTION
	 */
	protected function getThreadId()
	{
		$m = new GruppenThreadsModel();
		$select = $m->select()->where("group_id = ?", $this->getGroupId())
					->where("type = ?", 2)
					->where("language = ?", Zend_Registry::get("Zend_Locale")->getLanguage());
		
		$row = $m->fetchRow($select);
		if($row)
		{
			$this->_thread_id = $row->thread_id;
			return true;
		}
		else
			throw new Zend_Exception("Keinen Unternehmens-Thread gefunden!");
	}
	/**
	 * Erstellt eine Gruppe zum Unternehmen
	 *
	 * @param STRING $company_name
	 * @return INT|EXCEPTION
	 */
	protected function _createCompanyGroup($company_name)
	{
		//Gruppe/Thread/Beitrag anlegen
		//Gruppe erstellen
		$group_data = array("founder_id" => 3, //System
						"open" => 1,
						"title" => $company_name,
						"description" => $this->_getTranslate()->translate("Gruppe zum Unternehmen"),
						"language" => NULL
						);

		$group = new Group();

		if($group->createGroup($group_data, false))	
		{
				$languages = Zend_Registry::get("config")->general->language->toArray();
				
				foreach ($languages as $language)
				{
					//Thread erstellen (Kommentar-Thread)
	    			$thread_data = array("founder_id" => $group_data["founder_id"], 
	    						"title" => NULL, 
	    						"group_id" => $group->getId(),
	    						"type" => 2,
	    						"language" => $language["short"]);
	    			$thread = $group->createThread($thread_data, false);
				}
				
    		return $group->getId();
    	}
    	else
			throw new Zend_Exception("Konnte Group nicht anlegen.");		
	}
	
	/**
	*	Insert Company
	* 
	* 	@param String Name
	* 	@param String ISIN 
	* 	@param Array "exchange_name", "symbol"
	* 
	* 	@return BOOLEAN
	* */
	public function setCompany($name, $isin, $exchanges)
	{
		$validator = new Validate_Isin(); //ISIN validieren
		if(!$validator->isValid($isin))
			return false;
		if($name == "")
			return false;

		Zend_Registry::get('Zend_Db')->beginTransaction();

		try {
			//Gruppe/Thread/Beitrag anlege
			$group_id = $this->_createCompanyGroup($name);
				
			if($company_id = $this->_getCompaniesModel()->insert(array("isin" => $isin, "name" => $name, "group_id" => $group_id)))
			{
				$crawlelist = $this->getCrawlelistForSupportedExchanges($company_id, $exchanges, $isin);
								
				Quotes::firstInputCrawle($crawlelist);
				
				$this->kickMarketsWithLowVolumeAndSetMainMarket($company_id);
				
				//Alles neues Unternehmen vermerken, damit später noch restliche Quotes gecrawlt werden können
				$newctbl = new NewCompaniesModel();
				$newctbl->insert(array("company_id" => $company_id));
			    
				// Wenn alle erfolgreich waren, übertrage die Transaktion und alle Änderungen werden auf einmal übermittelt
			    Zend_Registry::get('Zend_Db')->commit();

			    Zend_Registry::get('Zend_Log')->log('Neues Unternehmen hinzugefuegt: '.$isin.' '.$name.' '.$company_id, Zend_Log::NOTICE);
			    
				return true;			
			}
			else
				return false;	
		

		
		} catch (Zend_Exception $e) {
		    // Wenn irgendeine der Abfragen fehlgeschlagen ist, wirf eine Ausnahme, wir wollen die komplette Transaktion
		    // zurücknehmen, alle durch die Transaktion gemachten Änderungen wieder entfernen auch die erfolgreichen.
		    // So werden alle Änderungen auf einmal übermittelt oder keine.
		    Zend_Registry::get('Zend_Db')->rollBack();
		    throw new Zend_Exception($e->getMessage(). "\n" .  $e->getTraceAsString());
		}	
	}
	/**
	 * Filtert die Eingabeliste nach unterstützten Märkten
	 *
	 * @param INT $company_id
	 * @param ARRAY $exchanges
	 * @return ARRAY $crawlelist
	 */
	public function getCrawlelistForSupportedExchanges($company_id = null, $exchanges, $isin = null)
	{
		if($company_id == null)
			$company_id = $this->getId();
		if($isin == null)
			$isin = $this->getISIN();	
			
		//Liste um US-Markets ergänzen, da Yahoo diese nicht mehr liefert
		if(substr($isin, 0, 2) == "US") // Wenn US
		{
			$tool = new GoogleFinanceStockSearch($isin, Zend_Registry::get('config')->general->proxy->toArray());
	    	if(($gResponse = $tool->getResponseParsed()))
	    	{
	    		if(!in_array(array("exchange_name" => $gResponse["market"]), $exchanges)) //Prüfen ob bereits im Array
				{
					if($gResponse["market"] == "PINK")
					{
						$gResponse["symbol"] .= ".PK"; //Symbolextension bei Pink anhängen
					}
					elseif($gResponse["market"] == "OTC")
					{
						$gResponse["symbol"] .= ".OB"; //Symbolextension bei Pink anhängen
					}
					
					if(!in_array(array("symbol" => $gResponse["symbol"]), $exchanges)) //Nochmal zur Sicherheit prüfen
					{
						$exchanges[] = array(
									"exchange_name" => $gResponse["market"],
									"symbol" => $gResponse["symbol"]
						);
					}
				}
	    	}
	    	else 
	    	{
	    		//Mitloggen wenns Google nichts für uns hat
	    		 Zend_Registry::get('Zend_Log')->log('GoogleFinance findet nichts: '.$isin.' '.$company_id, Zend_Log::NOTICE);
	    	}
		}	
			
		//Liste der Börsenplätze für den QuotesCrawle
		$crawlelist = array();
		//Auswerutung der Daten von Yahoo
		//exchanges aufbereiten
		//Welche Börsenplätze werden überhaupt unterstützt vom System?
		
		$comp_exch_table = new AvailablestocksonexchangesModel();
		$exch_table = new StockexchangesModel();
			
		foreach($exchanges as $exchange)
		{	
			if(($strpos = strpos ($exchange["symbol"], ".")))
			{
				$symbol_extension = substr($exchange["symbol"], $strpos+1);
				//Nach extension suchen
				
				$result = $exch_table->fetchRow($exch_table->select()->from($exch_table, array("market_id"))
											->where('symbolextension = ?', $symbol_extension));
				if($result)
				{
					//Gefunden, dann nehmen wir den Markt auf!
					if(!$comp_exch_table->fetchRow($comp_exch_table
									->select()
										->where("market_id = ?", $result->market_id)
										->where("company_id = ?", $company_id)
										))
					{
					$comp_exch_table->insert(array("market_id" => $result->market_id, 
													"company_id" => $company_id, 
													"symbol" => substr($exchange["symbol"], 0, $strpos)));
					}
					//Zur Crawlelist hinzufügen
					$crawlelist[] = array("company_id" => $company_id, "market_id" => $result->market_id);
				}

			}
			else
			{
				//das sollte jetzt eigentlich NASDAQ ODER NYSE sein, aber mal gucken...
				
				// geht seit ca. 20.12.2010 nicht mehr, da Y lookup-Seite umgestellt hat und bei NASDAQ und NYSQ Aktien keine ISIN zu finden ist :(
				
				$result = $exch_table->fetchRow($exch_table->select()->from($exch_table, array("market_id"))
											->where('name = ?', $exchange["exchange_name"]));
				if($result)
				{
					if(!$comp_exch_table->fetchRow($comp_exch_table
									->select()
										->where("market_id = ?", $result->market_id)
										->where("company_id = ?", $company_id)
										))
					{
					$comp_exch_table->insert(array("market_id" => $result->market_id, 
													"company_id" => $company_id, 
													"symbol" => $exchange["symbol"]));
					}
					//Zur Crawlelist hinzufügen
					$crawlelist[] = array("company_id" => $company_id, "market_id" => $result->market_id);
				}
			}					
		}

		//print_r($crawlelist);exit;
		
		return $crawlelist;
	}
	/**
	 * Setzt neue MainMarket basieret auf Market mit größtem Volumen
	 *
	 * @param INT $company_id
	 * @return INT market_id
	 */
	public function setMainMarketByMaxVolume($company_id = null)
	{
		if($company_id == null)
			$company_id = $this->getId();
			
		//prüfen welcher Markt das höchste Volumen hat und diesen als MainMarket setzen
		$quotes = new StockQuotesEODModel();
		$select = $quotes->select()
			->from($quotes, array("sum(volume) / count(*) AS volume_avg","market_id"))
			->where("company_id = ?", $company_id)
			->group("market_id")
			->order("volume_avg DESC");
		$rows = $quotes->fetchAll($select);
		if($rows->count() > 0)
		{
			//if($rows->current()->volume_avg > 0)
			//{
				//ist bereits vorsortiert, also nur noch den ersten nehmen
				$where = $this->_getCompaniesModel()->getAdapter()->quoteInto('company_id = ?', $company_id);
				$data = array("main_market" => $rows->current()->market_id);
				$this->_getCompaniesModel()->update($data, $where);	

				return $rows->current()->market_id;
			//}
		}
		
		return false;
	}
	public function kickMarketsWithLowVolumeAndSetMainMarket($company_id = null)
	{
		if($company_id == null)
			$company_id = $this->getId();

		$new_main_market_id = $this->setMainMarketByMaxVolume($company_id);	
			
		$quotes = new StockQuotesEODModel();
		$table_ae = new AvailablestocksonexchangesModel();
		$select = $quotes->select()
					->setIntegrityCheck(false)
   					->from(array("avs" => $table_ae->getTableName()))
   					->joinLeft(array("eod" => $quotes->getTableName()), 
   								'avs.company_id = eod.company_id 
								AND avs.market_id = eod.market_id', array("sum(volume) / count(*) AS volume_avg"))
			->where("avs.company_id = ?", $company_id)
			->group("avs.market_id")
			->order("volume_avg DESC");
		$rows = $quotes->fetchAll($select);
				
		//Um Load und Speicher zu reduzieren, soll nur DE + ein anderer Markt (US, UK, NL...) gecrawlt werden
		//natürlich der mit dem höchsten volumen
		$exchCC = array();
		
		//ERSTMAL einen DE-Markt suchen
		foreach ($rows as $row)
		{
			$market = new Market($row->market_id);
			if($market->getLocal() == "DE") //nur deutsche
			{
				if (!in_array($market->getLocal(), $exchCC))
				{
					$exchCC[] = $market->getLocal();
				}
				else 
				{
					//anderer deutscher Markt mit höherem Volumen bereits enthalten, also diesen löschen
					$model = new AvailablestocksonexchangesModel();
					$drows = $model->find($row->market_id, $company_id);
					if($drows->count() > 0)
					{
						$drows->current()->delete();
						
						$model = new StockQuotesEODModel();
						$where = array();
						$where[] = $model->getAdapter()->quoteInto('company_id = ?', $company_id);
						$where[] = $model->getAdapter()->quoteInto('market_id = ?', $row->market_id);
						$model->delete($where);	
						
						//Watchlists updaten
						if($new_main_market_id)
						{
							$watchlistCompsM = new WatchlistCompaniesModel();
							$watchlistCompsM->update(
										array("market_id" => $new_main_market_id),
										array(
											$watchlistCompsM->getAdapter()->quoteInto("company_id = ?", $company_id),
											$watchlistCompsM->getAdapter()->quoteInto("market_id = ?", $row->market_id)
										)
										);
						}
							
					}
				}				
			}
				
		}
		
		//restlichen Märkte
		foreach ($rows as $row)
		{
			$market = new Market($row->market_id);
			if($market->getLocal() != "DE") //keine deutschen, da diese ggf. schon drin
			{
				if (!in_array($market->getLocal(), $exchCC) && count($exchCC) < 2) //Insgesamt max ZWEI Märkte
					$exchCC[] = $market->getLocal();
				else 
				{
					//anderer Markt mit höherem Volumen bereits enthalten, also diesen löschen
					$model = new AvailablestocksonexchangesModel();
					$drows = $model->find($row->market_id, $company_id);
					if($drows->count() > 0)
					{
						$drows->current()->delete();
						
						$model = new StockQuotesEODModel();
						$where = array();
						$where[] = $model->getAdapter()->quoteInto('company_id = ?', $company_id);
						$where[] = $model->getAdapter()->quoteInto('market_id = ?', $row->market_id);
						$model->delete($where);	
						
						//Watchlists updaten
						if($new_main_market_id)
						{
							$watchlistCompsM = new WatchlistCompaniesModel();
							$watchlistCompsM->update(
										array("market_id" => $new_main_market_id),
										array(
											$watchlistCompsM->getAdapter()->quoteInto("company_id = ?", $company_id),
											$watchlistCompsM->getAdapter()->quoteInto("market_id = ?", $row->market_id)
										)
										);
						}
							
					}
				}
			}
				
		}
		return true;
	}
	
    /******************************************************************************************************************************************************************
     * HelperFunctions
     */		
	/**
	 * CompanyModel
	 *
	 * @return CompaniesModel
	 */
	protected function _getCompaniesModel()
	{
		if($this->_CompaniesModel instanceof CompaniesModel)
			return $this->_CompaniesModel;
		else
		{
			$this->_CompaniesModel = new CompaniesModel();
			return $this->_CompaniesModel;
		}
	}
	/**
	 * AnalysisModel
	 *
	 * @return AnalysisModel
	 */
	protected function _getAnalysisModel()
	{
		if(!($this->_AnalysisModel instanceof AnalysisModel))
			$this->_AnalysisModel = new AnalysisModel();

		return $this->_AnalysisModel;
	}
	
	/**
	 * AnalysisModel
	 *
	 * @return AnalysisModel
	 */
	protected function _getKeydataanalysesModel()
	{
		return $this->_getAnalysisModel();
	}
	/**
	 * AnalysisFavouritsModel
	 *
	 * @return AnalysisFavouritsModel
	 */
	protected function _getAnalysisFavouritsModel()
	{
		if($this->_AnalysisFavouritsModel instanceof AnalysisFavouritsModel)
			return $this->_AnalysisFavouritsModel;
		else
		{
			$this->_AnalysisFavouritsModel = new AnalysisFavouritsModel();
			return $this->_AnalysisFavouritsModel;
		}	
	}	
	
}