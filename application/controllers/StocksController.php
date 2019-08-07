<?php
/**
 * StocksController
 * 
 * @author
 * @version 
 */

class StocksController extends Zend_Controller_Action
{
	function preDispatch() 
	{ 
		$this->_helper->getHelper('SwitchContext')	
						->addActionContext('list', 'mobile')
						->addActionContext('search', 'mobile')
						->addActionContext('show', 'mobile')
						->addActionContext('single-quote', 'json')
						->addActionContext('chart-urls', 'json')
						->initContext();
	}
	function init()
	{
		$this->view->params = $this->_request->getParams();
	}
	
	function listAction()
	{
		$this->view->headTitle($this->view->translate("Unternehmen"));
		
		$this->view->page = $this->_getParam('page');
		
		if($this->_getParam('orderby') == "isin")
			$this->view->orderby ="isin";
		else
			$this->view->orderby = "name";
				
		//Unternehmen
		$this->view->paginator = new Zend_Paginator($this->getPaginatorAdapter(null, $this->view->orderby));
		$this->view->paginator->setItemCountPerPage(30);
		$this->view->paginator->setCurrentPageNumber($this->view->page);
		
	}
	function searchAction()
	{
		$this->_redirector = $this->_helper->getHelper('Redirector');
		
		$needle = $this->_getParam('needle');
		if($this->_getParam('q'))
		{
			$this->_redirector->gotoRoute(array('needle' => $this->_getParam('q'), 
												"language" => Zend_Registry::get('Zend_Locale')->getLanguage())
										, 'stocksearch');
			
		}
		
		$this->view->needle = $needle;
			
		
		$page = $this->_getParam('page');
		if($this->_getParam('alpha'))
			$alphabet = true;
		else
			$alphabet = false;
		
		//@TODO needle Filtern (injection)	
	
		if($needle) //es wurde gesucht
		{
			//Struktur vom needle prüfen um festzustellen, ob ISIN, Name oder Symbol
			//teste auf ISIN
			$searchmodel = new CompaniesModel();
			$validator_isin = new Validate_Isin(); //ISIN validieren
			$validator_length = new Zend_Validate_StringLength("1"); //Überhaupt eine Zeichenkette?
			$vali_isin = $validator_isin->isValid($needle);
			$vali_length = $validator_length->isValid($needle);
			
			$this->view->localsearch = false;
	
			if($vali_isin || $vali_length)
			{
				if($vali_isin)
					$select = $searchmodel->getRowsetByISIN($needle, true);
				else
					$select = $searchmodel->getRowsetByNameOrSymbol($needle, true, $alphabet);
				
				//Unternehmen
				$paginator = new Zend_Paginator($this->getPaginatorAdapter($select));
				$paginator->setItemCountPerPage(50);
				$paginator->setCurrentPageNumber($page);
				$count = $paginator->getTotalItemCount();	
				
				if($count > 0)
				{
					if($count == 1)
					{
						//Nur ein Unternehmen, direkter redirect
						//@TODO theoretisch könnte noch uns unbekannte Unternehmen dazu kommen
						$this->_redirector->gotoRoute(array('isin' => $paginator->getItemsByPage(1)->current()->getIsin(), "language" => Zend_Registry::get('Zend_Locale')->getLanguage()), 'stock');
					}
	
					else
					{
						//Mehrere Unternehmen... User muss auswählen
						//@TODO theoretisch könnte noch uns unbekannte Unternehmen dazu kommen
					
						$this->view->paginator = $paginator;
						$this->view->localsearch = true;
					}
				}
				else
				{
					//nichts gefunden
					
					//dann muss wohl nochmal yahoo ran
					$yf_search = new YahooFinanceStockSearch($needle, Zend_Registry::get('config')->general->proxy->toArray());
					$dataSet = $yf_search->getResponseParsedGroupByISIN();
					
					
					$paginator = new Zend_Paginator(new Zend_Paginator_Adapter_Iterator($dataSet));
					$paginator->setItemCountPerPage(50);
					$paginator->setCurrentPageNumber($page);
					
					$count = $paginator->getTotalItemCount();
					
					
					if($count > 0)
					{
						if($count == 1)
						{
							foreach ($paginator as $item)
							{
								//Nur ein Unternehmen, direkter redirect
								$this->_redirector->gotoRoute(array('isin' => $item->getIsin(), "language" => Zend_Registry::get('Zend_Locale')->getLanguage()), 'stock');
							}
						}
						else
						{
							//Mehrere Unternehmen... User muss auswählen
							
							$this->view->paginator = $paginator;
						}	
					}
					else 
					{
						//Nichts gefunden in externer Quelle
						$mbox = new MessageBox();
						$mbox->setMessage("MSG_STOCKSEARCH_001", $needle);
						$this->view->mbox = $mbox;
					}
				}
				
			}
			else
			{
	
				//Needle kann nicht verarbeitet werden!
				//Fehler - bitte neue Suche
				$mbox = new MessageBox();
				$mbox->setMessage("MSG_STOCKSEARCH_002");
				$this->view->mbox = $mbox;
			}			
		}
		else // es wurde noch gar nicht gesucht
		{
			
		}
			


	}
	function showAction()
	{
		$this->view->headTitle($this->view->translate("Unternehmen"));
		
		$this->view->errorlist = array();
		if(!isset($this->view->params["isin"]))
			$this->view->params["isin"] = "";
		
		$this->_redirector = $this->_helper->getHelper('Redirector');
		
		//Validator
		$validator = new Validate_Isin(); //ISIN validieren
		if($validator->isValid($this->view->params["isin"]))
		{
			//ISIN in Ordnung
			
			//weiter...
			$company = new Company();
			if($company->getCompanyByISIN($this->view->params["isin"]))
			{
			//Daten Ausgeben!
				$this->view->company = $company;
					
				$this->view->headTitle($company->getName());
				
				//Kommentare - Seiten/pages
				//$this->view->CommentPageAnalysis = $this->_getParam('CommentPageAnalysis');
				//$this->view->CommentPageCompany = $this->_getParam('CommentPageCompany');
				
				
				if($analysis_id = $company->getPreselectedAnalysisId(Zend_Registry::get("UserObject")->getUserId()))
				{
					$analysis = new Analysis_Calculator();
					$analysis->getAnalysisById($analysis_id);
					$this->view->analysis = $analysis;
					//Kommentare
					//$this->view->commentPaginatorAnalysis = new Zend_Paginator($analysis->getThread()->getPaginatorAdapter());
					//$this->view->commentPaginatorAnalysis->setCurrentPageNumber($this->view->CommentPageAnalysis);
				}
				//Kommentare
				//$this->view->commentPaginatorCompany = new Zend_Paginator($company->getThread()->getPaginatorAdapter());
				//$this->view->commentPaginatorCompany->setCurrentPageNumber($this->view->CommentPageCompany);
				
				$ns = new Zend_Session_Namespace('Rule1Tool');
				if($ns->recommendedLayout == "mobile" || $ns->layout == "mobile")
				{
					$this->view->charts = $this->charts($company, $company->getMainMarketId());					
				}

				
				//Watchlists
				$watchlistSet = Zend_Registry::get("UserObject")->getWatchlists();
				//@TODO GUEST-Login beachten 
				if(count($watchlistSet) == 0)
				{
					//Keine Watchlist vorhanden
					$this->view->watchlist_id = 0;
				}
				elseif (count($watchlistSet) == 1)
				{
					//genau eine Watchlist vorhanden
					$this->view->watchlist_id = $watchlistSet->current()->getWatchlistId();
				}
				elseif (count($watchlistSet) > 1)
				{
					//mehrere Watchlists vorhanden
					$this->view->watchlist_id = 0;
				}
				
			}
			else
			{
				//unbekannte ISIN
				//Suche bei yahoo
				$yf_search = new YahooFinanceStockSearch($this->view->params["isin"], Zend_Registry::get('config')->general->proxy->toArray());
				if($response = $yf_search->getResponseParsed())
				{
					//irgendetwas gefunden, aber was?
					$tmp_row_isin = $response[0]["isin"]; //die erste ISIN als Vergleichswert
					$justonecompany = true;
					foreach($response as $row)
					{
						if(!$row["isin"] == $tmp_row_isin)
							$justonecompany = false;
	
					}
					if($justonecompany)
					{
						//Nur ein Unternehmen
						//Neues Unternehmen anlegen
						$new_company = new Company();
						//Daten aufbereiten für den Insert
						//basics
							$name = $response[0]["name"];
							$isin = $response[0]["isin"];
						// börsenplätze und symbole
							$exchanges = array(); // market
							foreach($response as $row)
							{
								$exchanges[] = array(
													"exchange_name" => $row["market"], 
													"symbol" => $row["symbol"]
									);
							}
							
							/* auch Unternehmen ohne Kursdaten zulassen
							if(count($exchanges) == 0)
								throw new Zend_Exception("Konnte keinen Börsenplatz finden");
							*/					
						$result = $new_company->setCompany($name, $isin, $exchanges);
						if($result)
						{
							//Hat wohl geklappt...
							//Also fix anzeigen
							$this->_redirector->gotoRoute(array('isin' => $response[0]["isin"], "language" => Zend_Registry::get('Zend_Locale')->getLanguage()), 'stock'); //REDIRECT, damit Speicher freigegeben wird
						}
						else
						{
							//Da hat was nicht geklappt
							//Was machen wir denn jetzt?
							throw new Zend_Exception("Daten konnten nicht eingefügt werden.");
						}
						
					}
					else
					{
						//Mehrere Unternehmen
						//Na das kann bei einer ISIN ja wohl gar nicht sein!!!
						//Fehler!!!
						$this->view->errorlist = array("isinIsNotValid" => "Die ISIN stimmt nicht. Ein unerwartetes Resultat kam bei der Suche. Bitte Eingabe überprüfen bzw. die Suchfunktion nutzen");
						$this->_helper->viewRenderer('isinNotFound');
					}
				} 
				else
				{
					//kein Unternehmen gefunden
					$mbox = new MessageBox();
					$mbox->setMessage("MSG_STOCKSEARCH_001", $this->view->params["isin"]);
					$this->view->mbox = $mbox;
						
					$this->_helper->viewRenderer('isinNotFound');
				}
					
			}
		}
		else
		{
			//ISIN-Format nicht korrekt
			$this->view->errorlist = $validator->getMessages();
			$this->_helper->viewRenderer('incorrect-isin');
		}
	}
	
	function singleQuoteAction()
	{
		$cid = $this->_getParam("CID");
		$mid = $this->_getParam("MID");
		$pid = $this->_getParam("PID");
		
		if($cid)
		{
			$company = new Company($cid);
			$quotes = false;
			if($mid)
			{
				$quotes = $company->getQuotes($mid);
			}
			elseif ($pid)
			{
				$portfolio = new Portfolio($pid);
				$quotes = $company->getQuotesByCurrency($portfolio->getCurrency());
			}
			if (!$quotes)
				$quotes = $company->getQuotes($company->getMainMarketId());
			if($quotes)	
			{
				$lastquote = $quotes->getLastQuote();
				
				$this->view->close = $lastquote->getClose();
				$this->view->closeNumber = $lastquote->getClose(true, 999);
				$this->view->change = $lastquote->getChange();
				$this->view->date = $lastquote->getDate();			
				$this->view->high = $lastquote->getHigh();	
				$this->view->low = $lastquote->getLow();	
				$this->view->currency = $lastquote->getCurrency();				
			}

		}
	}
	
	function charts($company, $market_id, $indikatoren = null, $days = 60)
	{
		if($indikatoren == null)
		{
			$indikatoren = array(
							"SMA" => array(array("period" => 10), array("period" => 30), array("period" => 50)),
							"MACD" => array(array("fastEMA" => 8, "slowEMA" => 17, "signalEMA" => 9)),
							"STO" => array(array("k" => 14, "d" => 5, "type" => "slow"), array("k" => 14, "d" => 5, "type" => "fast"))
						);			
		}
				
		if($market_id)
		{
			if(!$days || $days < 10 || $days > 400)
				$days = 60;		//Fallback		

			if($days > 200)
			{
				$indikatoren["SMA"][] = array("period" => 200);
			}
			
    		$charts = new ChartSet($company, $market_id, $indikatoren, $days);
    		
    		return $charts->getUrls();		
		}
		else
			return null;	
	}
	/**
	 * Gibt den PaginatorAdapter zurück
	 *
	 * @return Company_PaginatorAdapter
	 */
	public function getPaginatorAdapter(Zend_Db_Table_Select $select = null, $orderby = "name", $sort = "ASC")
	{
		if($select == null)
		{
			$tbl = new CompaniesModel();
			$select = $tbl->select();
			$select->order($orderby." ".$sort);			
		}
		return new Company_PaginatorAdapter($select);	
	}
	
	public function chartUrlsAction()
	{
		$cid = $this->_getParam("CID");
		$days = $this->_getParam("PERIOD");
		$company = new Company($cid);
		/*
		if(!$this->_request->isXmlHttpRequest())
		{
			//Nicht Json/XML-Request sollen zur Unternehmensseite weitergeleitet werden
			$this->_helper->getHelper('Redirector')->gotoRoute(array(
						'isin' => $company->getIsin(), 
						"language" => Zend_Registry::get('Zend_Locale')->getLanguage())
							, 'stock');
			
		}
*/
		if($company->getMainMarketId())
		{
			$this->view->charts = $this->charts($company->getId(), $company->getMainMarketId(), null, $days);
		}
		else
			$this->view->charts = false;
	}
	
}