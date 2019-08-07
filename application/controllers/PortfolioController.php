<?php
/**
 * PortfolioController
 * 
 * @author
 * @version 
 */

class PortfolioController extends Zend_Controller_Action
{
	public function init()
	{
		$this->view->headTitle($this->view->translate("Depot"));
	}

	function indexAction()
	{
		
		
		//Zeige alle Portfolios
		$username = $this->_getParam("username");
		
		$user = new User();
                                
		if(!$user->getUser($username))
		{
			//Nutzer nicht gefunden
			$this->_redirector = $this->_helper->getHelper('Redirector');
			$this->_redirector->gotoRoute(array('username' => Zend_Registry::get("UserObject")->getNickname(), 
													"language" => Zend_Registry::get('Zend_Locale')->getLanguage())
											, 'user_portfolios');	
		}

		$this->view->user = $user;
		$m = new PortfolioModel();
		$rows = $m->fetchAll($m->select()->where("user_id = ?", $user->getUserId()));
		
		if($rows->count() == 1)
		{
			//redirect to show
			$this->_redirector = $this->_helper->getHelper('Redirector');
			$this->_redirector->gotoRoute(array('username' => $user->getNickname(), 
													"PID" => $rows->current()->id,
													"language" => Zend_Registry::get('Zend_Locale')->getLanguage())
											, 'user_portfolio_transactions');	
		}
		elseif ($rows->count() == 0)
		{
			//redirect to create
			$this->_redirector = $this->_helper->getHelper('Redirector');
			$this->_redirector->gotoRoute(array('username' => Zend_Registry::get("UserObject")->getNickname(), 
												"language" => Zend_Registry::get('Zend_Locale')->getLanguage())
											, 'user_portfolio_create');	
		}
		else 
			$this->view->list = $rows;
	}
	function showAction()
	{
		$pid = $this->getRequest()->getParam("PID");
		
		$this->view->portfolio = new Portfolio($pid);
				
		if($this->_getParam("TID_add"))
		{
			$m = new PortfolioTransactionsModel();
			$rows = $m->find($this->_getParam("TID_add"));
			if($rows->count() > 0)
			{
				$row = $rows->current();
				$company = new Company($row->company_id);
				//Msg einblenden mit der hinzugefügten Transaktion
				$mbox = new MessageBox();
				//Unternehmen: %value%, Kurs: %value%, Anzahl: %value%, Gebühren: %value%, Datum: %value%
				$zdate = new Zend_Date($row->date);
				$mbox->setMessage("MSG_PORTFOLIO_001", array($company->getName(), $row->price, $row->anzahl, $row->gebuehren, $zdate->get("yyyy-MM-dd HH:mm")));
				$this->view->mbox = $mbox;
			}
				
		}
		
		
	}
	
	function createAction()
	{		
		$this->view->headTitle($this->view->translate("Depot anlegen"));
		
		$form = new Zend_Form();
		$form->setAction($this->view->url(array("language" => Zend_Registry::get('Zend_Locale')->getLanguage(), "username" => Zend_Registry::get("UserObject")->getNickname()), "user_portfolio_create"));
        $form->setMethod('post');
 

        $name = new Zend_Form_Element_Text("portfolio_name", array('size' => '30'));
        $name->setRequired(true);
        $submit = new Zend_Form_Element_Submit("submit");
        
        $currency = new Zend_Form_Element_Select("currency");
 		$currency
 				->setLabel("Währung")
 				->addValidator(new Zend_Validate_InArray(Zend_Registry::get("config")->general->currencies->toArray()))
 				->addMultiOptions(Zend_Registry::get("config")->general->currencies->toArray())
 				->addFilters(array('StripTags', 'StringTrim')); 
  		$currency->setRequired(true);
  		
  		$newsletter = new Zend_Form_Element_Checkbox("signal_newsletter");
       	$newsletter->setLabel("Tägliche E-Mail mit aktuellen Signalen abonnieren");
 
		$name->setLabel("Depot-Name"); 
		
        $submit->setLabel("» Depot anlegen");  

		$form->addElements(array($name, $currency, 
		$newsletter, 
		$submit));
		
		$form->setDefault("currency", "EUR");
								
		if ($this->getRequest()->isPost())
		{
			$form->isValid($this->_getAllParams());

			$name = $form->getValue("portfolio_name");
			$currency = $form->getValue("currency");
			$owner_id = Zend_Registry::get("UserObject")->getUserId();
			$signalNewsletter = $form->getValue("signal_newsletter");
			
			$portfolio = new Portfolio();
			$create = $portfolio->add(array(
					"name" => $name, 
					"user_id" => $owner_id,
					"currency" => $currency,
					"send_signal_mail" => $signalNewsletter
			));
			
			//Bei erfolg kann gleich die zur Portfolio gesprungen werden
			if($create)
			{
				$this->_redirector = $this->_helper->getHelper('Redirector');
				$this->_redirector->gotoRoute(array('username' => Zend_Registry::get("UserObject")->getNickname(), 
													"PID" => $create,
													"language" => Zend_Registry::get('Zend_Locale')->getLanguage())
											, 'user_portfolio_transactions');	
			}
				
			$this->view->mbox = $portfolio->getMessageBox();
						
		}
		else 
		{
			$form->setDefaults(array("portfolio_name" => Zend_Registry::get("UserObject")->getDisplayname()."s "."Depot"));
		}
		
		$this->view->form = $form;
	
	}
	function editAction()
	{
		$this->view->headTitle($this->view->translate("Depot bearbeiten"));
		
		$pid = $this->getRequest()->getParam("PID");
		
		$form = new Zend_Form();
		$form->setAction($this->view->url(array("language" => Zend_Registry::get('Zend_Locale')->getLanguage(), "username" => Zend_Registry::get("UserObject")->getNickname()), "user_portfolio_edit"));
        $form->setMethod('post');
 
        $name = new Zend_Form_Element_Text("portfolio_name", array('size' => '30'));
        $name->setRequired(true);
        $newsletter = new Zend_Form_Element_Checkbox("signal_newsletter");
       	$newsletter->setLabel("Tägliche Mail mit aktuellen Signalen abonnieren");
        
        $submit = new Zend_Form_Element_Submit("submit");
        
		$name->setLabel("Depot-Name"); 
		
        $submit->setLabel("» Änderungen speichern");  

		$form->addElements(array($name, 
		$newsletter, 
		$submit));
		
								
		if ($this->getRequest()->isPost())
		{
			$form->isValid($this->_getAllParams());

			$name = $form->getValue("portfolio_name");
			$signalNewsletter = $form->getValue("signal_newsletter");
			
			$portfolio = new Portfolio($pid);
			$create = $portfolio->edit(array(
					"name" => $name, 
					"send_signal_mail" => $signalNewsletter
			));
			
			//Bei erfolg kann gleich die zur Portfolio gesprungen werden
			if($create)
			{
				$this->_redirector = $this->_helper->getHelper('Redirector');
				$this->_redirector->gotoRoute(array('username' => Zend_Registry::get("UserObject")->getNickname(), 
													"PID" => $pid,
													"language" => Zend_Registry::get('Zend_Locale')->getLanguage())
											, 'user_portfolio_transactions');	
			}
				
			$this->view->mbox = $portfolio->getMessageBox();
						
		}
		else 
		{
			$portfolio = new Portfolio($pid);
			$form->populate(array("portfolio_name" => $portfolio->getName(), "signal_newsletter" => $portfolio->getSendSignalMail()));
		}
		
		$this->view->form = $form;		
	}
	function deleteAction()
	{
		$this->view->headTitle($this->view->translate("Depot bearbeiten"));
		
		$pid = $this->getRequest()->getParam("PID");
		$portfolio = new Portfolio($pid);
				
		$this->view->success = $portfolio->delete();
		$this->view->messages = $portfolio->getMessages();
		//$this->view->mbox = $portfolio->getMessageBox();
		
		// Einen Meta Refresh mit 3 Sekunden zu einer neuen URL setzen:
		$this->view->headMeta()->appendHttpEquiv('Refresh',
                               '3;URL='.$this->view->url(array(
				"language" => Zend_Registry::get('Zend_Locale')->getLanguage(), 
				"username" => $this->_getParam("username")), 
				"user_portfolios"));
	}
	
	function performanceMonitorAction()
	{
		$username = $this->_getParam("username");		
		
		$this->view->user = new User();
		$this->view->user->getUser($username);
		
		$pid = $this->getRequest()->getParam("PID");
		
		if(!$pid)
		{
			$this->_redirector = $this->_helper->getHelper('Redirector');
			$this->_redirector->gotoRoute(array('username' => Zend_Registry::get("UserObject")->getNickname(), 
													"language" => Zend_Registry::get('Zend_Locale')->getLanguage())
											, 'user_portfolios');	
		}
		$portfolio = new Portfolio($pid);
		$this->view->portfolio = $portfolio;
		$this->view->portfolioStocks = $portfolio->getCurrentPortfolio();
		if(!$this->view->portfolioStocks)
		{
			//kein Portfolio gefunden
			//404 ausgeben
			$this->_redirector = $this->_helper->getHelper('Redirector');
			$this->_redirector->gotoSimple('notfound',
                                       'error',
										null,
										array("language" => Zend_Registry::get('Zend_Locale')->getLanguage())
                                       );
		}
	
		
		if($this->_getParam("TID_add"))
			{				
				$m = new PortfolioTransactionsModel();
				$rows = $m->find($this->_getParam("TID_add"));
				if($rows->count() > 0)
				{
					$row = $rows->current();
					$company = new Company($row->company_id);
					//Msg einblenden mit der hinzugefügten Transaktion
					$mbox = new MessageBox();
					//Unternehmen: %value%, Kurs: %value%, Anzahl: %value%, Gebühren: %value%, Datum: %value%
					$zdate = new Zend_Date($row->date);
					$mbox->setMessage("MSG_PORTFOLIO_001", array($company->getName(), $row->price, $row->anzahl, $row->gebuehren, $zdate->get("yyyy-MM-dd HH:mm")));
					$this->view->mbox = $mbox;
				}
					
			}
		$identifier = Zend_Registry::get('systemtype')."_portfoliotransaction_".$pid;
		$cache = Zend_Registry::get('Zend_Cache_Core');
		if (!($data = $cache->load($identifier))) {
			
			$model = new PortfolioTransactionsModel();
			$select = $model->select()
							->where("`portfolio_id` = ?", $pid)
							->order(array("date DESC","date_edit DESC"));
			$transactions = $model->fetchAll($select)->toArray();
		
			$jahresbilanzen = array();
	
			$transactionsSorted = array();
			//Daten ergänzen
			for ($i = 0; $i<count($transactions); $i++)
			{
				$cTransaction = new Portfolio_Transaction($transactions[$i]["tid"], $transactions[$i]);
				
				$zdate = new Zend_Date($transactions[$i]["date"]);
				$year = $zdate->get("yyyy");
				//Jahresbilanz erstellen
				if(!isset($jahresbilanzen[$zdate->get("yyyy")]))
				{
					$jahresbilanzen[$year] =  array(
						"year" => $year,
						"ertragWert" => 0,
						"einstandsWert" => 0);
				}
					
				if($transactions[$i]["anzahl"] < 0 || $transactions[$i]["type"] == 2)
				{	//SALE
					
					//weiter zum aktuellen Jahr addieren // Jahresbilanz
					$jahresbilanzen[$year]["ertragWert"] += $cTransaction->getErtragWert();
					$jahresbilanzen[$year]["einstandsWert"] += $cTransaction->getEinstandsWert();
									
				}
				
				$transactions[$i]["gesamtWert"] = $cTransaction->getGesamtWert();
				$transactions[$i]["aktienWert"] = $cTransaction->getAktienWert();
				$transactions[$i]["ertragWert"] = $cTransaction->getErtragWert();
				$transactions[$i]["ertragProzent"] = $cTransaction->getErtragProzent();
				
				$transactions[$i]["einstandsWert"] = $cTransaction->getEinstandsWert();
				
				$transactions[$i]["anzahl"] = $cTransaction->getAnzahl();
				$transactions[$i]["type"] = $cTransaction->getType();
				
				$transactions[$i]["integrity"] = $cTransaction->getIntegrity();
				
				$companytmp = new Company($transactions[$i]["company_id"]);
				
				$transactions[$i]["companyName"] = $companytmp->getName();
				$transactions[$i]["companyIsin"] = $companytmp->getISIN();
				$transactions[$i]["companyMainMarketId"] = $companytmp->getMainMarketId();
				
				$transactionsSorted[$year][] = $transactions[$i];
			}

			foreach ($jahresbilanzen as $key => $jahresbiz)
			{
				if($jahresbilanzen[$key]["einstandsWert"] > 0)
					$jahresbilanzen[$key]["ertragProzent"] = $jahresbilanzen[$key]["ertragWert"] / $jahresbilanzen[$key]["einstandsWert"];
				else 
					$jahresbilanzen[$key]["ertragProzent"] = "";	
			}
			if(count($jahresbilanzen) > 0)
			{
				$zdate = new Zend_Date();
				//Jahresbilanzen um Jahre ohne Transaktionen auffüllen
				for($i = $year; $i<=$zdate->get("yyyy");$i++) //von kleinestem Jahr bis aktuelles
				{
					if(!isset($jahresbilanzen[$i]))
					{
						$jahresbilanzen[$i] = array(
							"year" => $i,
							"ertragWert" => 0,
							"einstandsWert" => 0,
							"ertragProzent" => "");
					}
				}
			}
			krsort($jahresbilanzen);

		
			$data = array("transactionsSorted" => $transactionsSorted, "jahresbilanzen" => $jahresbilanzen);
   			$cache->save($data, $identifier, array('portfoliotransactions'), 2592000);  //30-Tage speichern   	
   		}	
		//Alle Transactionen
		//$this->view->transactions = $transactions;
		$this->view->transactionsSorted = $data["transactionsSorted"];
		$this->view->jahresbilanzen = $data["jahresbilanzen"];
		
		
		//Aktuelle Bilanz holen
		$endBilanzErtrag = 0;
		$endBilanzEinstandsWert = 0;
		$portfolioGesamtWert = 0;
		foreach ($this->view->portfolioStocks as $key => $stock)
		{			
			$endBilanzErtrag += $stock["ertragWert"];
			$endBilanzEinstandsWert += $stock["einstandsWert"];
			$portfolioGesamtWert += $stock["gesamtWert"];
		}
		if($endBilanzEinstandsWert != 0)
			$this->view->endBilanzErtragProzent = $endBilanzErtrag / $endBilanzEinstandsWert;
		else 
			$this->view->endBilanzErtragProzent = 0;
		$this->view->endBilanzErtrag = $endBilanzErtrag;
		$this->view->endBilanzEinstandsWert = $endBilanzEinstandsWert;
		$this->view->portfolioGesamtWert = $portfolioGesamtWert;
		
		
		//Überalles Bilanz
		$this->view->overallBilanzErtrag = 0;
		$this->view->overallBilanzEinstandsWert = 0;
		foreach ($this->view->jahresbilanzen as $jahrbil)
		{
			$this->view->overallBilanzErtrag += $jahrbil["ertragWert"];
			$this->view->overallBilanzEinstandsWert += $jahrbil["einstandsWert"];
		}
		$this->view->overallBilanzErtrag += $this->view->endBilanzErtrag;
		$this->view->overallBilanzEinstandsWert += $this->view->endBilanzEinstandsWert;
		
		if($this->view->overallBilanzEinstandsWert != 0)
			$this->view->overallBilanzErtragProzent = $this->view->overallBilanzErtrag / $this->view->overallBilanzEinstandsWert; 
		else 
			$this->view->overallBilanzErtragProzent = 0;
			
		$this->view->headTitle($portfolio->getName());

	}
	
	function addTransactionAction()
	{
		$this->view->headTitle($this->view->translate("Transaktion hinzufügen"));
		
		if($this->_getParam("submit"))
		{
			//Daten prüfen und ggf. Transaktion erstellen und Msg ausgeben
			
			$transaction = new Portfolio_Transaction();
			$insert = $transaction->add($this->_getAllParams());
			
			if($insert)
			{
				$identifier = Zend_Registry::get('systemtype')."_portfoliotransaction_".$this->_getParam("portfolio_id");
				$cache = Zend_Registry::get('Zend_Cache_Core');
				$cache->remove($identifier);
				
				$this->_redirector = $this->_helper->getHelper('Redirector');
				$this->_redirector->gotoRoute(array('username' => Zend_Registry::get("UserObject")->getNickname(), 
													"PID" => $this->_getParam("portfolio_id"),
													"TID_add" => $insert,
													"language" => Zend_Registry::get('Zend_Locale')->getLanguage())
											, 'user_portfolio_transactions');
			}
		}
		
		$values = array(
				"portfolio_id" => $this->_getParam("PID"),
				"company_id" => $this->_getParam("CID"),
				"type" => $this->_getParam("type_def"),
				"anzahl" => $this->_getParam("anzahl_def")
		);
		
		$form = new Form_PortfolioTransactionInput(null, $values);
		
		$form->setAction($this->view->url(array(
			"language" => Zend_Registry::get('Zend_Locale')->getLanguage(), 
			"username" => Zend_Registry::get("UserObject")->getNickname()
			), 
		"user_portfolio_transaction_add"));
	
		if($this->_getParam("submit")){
			$form->populate($this->_getAllParams());				
			$this->view->mbox = $transaction->getMessageBox();
		}
							
		$this->view->form = $form;
		
	}
	function editTransactionAction()
	{
		$this->view->headTitle($this->view->translate("Transaktion bearbeiten"));
		
		$values = array(
				"portfolio_id" => $this->_getParam("PID"),
				"company_id" => false,
				"type" => false,
				"anzahl" => false
		);
		$form = new Form_PortfolioTransactionInput(null, $values);

		if(!$this->_getParam("submit")){
			
			$m = new PortfolioTransactionsModel();
			$rows = $m->find($this->_getParam("TID"));
			if(count($rows) > 0)
			{
				
				$row = $rows->current();
				if ($row->type == 1)
				{
					if($row->anzahl > 0)
						$type = "buy";
					else
					{
						$row->anzahl = -$row->anzahl;
						$type = "sell";
					}
				}
				elseif($row->type == 2)
				{
					$type = "dividende";
				}
					
				$zdate = new Zend_Date($row->date);
				//$date = $zdate->get("yyyy-MM-dd HH:mm");
				
				$values = array(
					"company_id" => $row->company_id,
					"portfolio_id" => $row->portfolio_id,
					"price" => $this->view->toNumber($row->price,2),
					"anzahl" => $row->anzahl,
					"gebuehren" => $this->view->toNumber($row->gebuehren,2),
					"type" => $type,
					"date" => $zdate->get("yyyy-MM-dd"),
					"time" => $zdate->get("HH:mm")
				);
				$form->populate($values);	
			}
			else 
			{
				//Transaktion nicht gefunden
				$this->_redirector = $this->_helper->getHelper('Redirector');
				$this->_redirector->gotoRoute(array('username' => Zend_Registry::get("UserObject")->getNickname(), 
													"PID" => $this->_getParam("PID"),
													"language" => Zend_Registry::get('Zend_Locale')->getLanguage())
											, 'user_portfolio_transactions');
			}
		}
		if($this->_getParam("submit"))
		{
			//Daten prüfen und ggf. Transaktion erstellen und Msg ausgeben
			
			$transaction = new Portfolio_Transaction($this->_getParam("TID"));
			$edit = $transaction->edit($this->_getAllParams());
			
			if($edit)
			{
				$identifier = Zend_Registry::get('systemtype')."_portfoliotransaction_".$this->_getParam("portfolio_id");
				$cache = Zend_Registry::get('Zend_Cache_Core');
				$cache->remove($identifier);
				
				$this->_redirector = $this->_helper->getHelper('Redirector');
				$this->_redirector->gotoRoute(array('username' => Zend_Registry::get("UserObject")->getNickname(), 
													"PID" => $this->_getParam("portfolio_id"),
													"TID_edit" => $this->_getParam("TID"),
													"language" => Zend_Registry::get('Zend_Locale')->getLanguage())
											, 'user_portfolio_transactions');
			}
		}
		
		$form->setAction($this->view->url(array(
			"language" => Zend_Registry::get('Zend_Locale')->getLanguage(), 
			"username" => Zend_Registry::get("UserObject")->getNickname(),
			"TID" => $this->_getParam("TID")
			), 
		"user_portfolio_transaction_edit"));
	
		if($this->_getParam("submit")){
			$form->populate($this->_getAllParams());				
			$this->view->mbox = $transaction->getMessageBox();
		}
							
		$this->view->form = $form;
	}
	function deleteTransactionAction()
	{
		$this->view->headTitle($this->view->translate("Transkation löschen"));
		
		$transaction_id = $this->_getParam("TID");
		$portfolio_id = $this->_getParam("PID");
		
		$identifier = Zend_Registry::get('systemtype')."_portfoliotransaction_".$portfolio_id;
		$cache = Zend_Registry::get('Zend_Cache_Core');
		$cache->remove($identifier);
		
		$ta = new Portfolio_Transaction($transaction_id);
		$this->view->success = $ta->delete();
		$this->view->messages = $ta->getMessages();
		// Einen Meta Refresh mit 3 Sekunden zu einer neuen URL setzen:
		$this->view->headMeta()->appendHttpEquiv('Refresh',
                               '1;URL='.$this->view->url(array(
				"language" => Zend_Registry::get('Zend_Locale')->getLanguage(), 
				"username" => $this->_getParam("username"),
				"PID" => $portfolio_id
						), "user_portfolio_transactions"));
	}

}