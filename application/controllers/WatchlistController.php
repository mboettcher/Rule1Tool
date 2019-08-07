<?php
/**
 * WatchlistController
 * 
 * @author
 * @version 
 */

class WatchlistController extends Zend_Controller_Action
{
	public function init()
    {
    	$contextSwitch = $this->_helper->getHelper('SwitchContext');
		$contextSwitch	->addActionContext('add', 'json')
						->addActionContext('delete', 'json')
						->addActionContext('show-json', 'json')
						->addActionContext('index', 'mobile')
						->addActionContext('show', 'mobile')
						->addActionContext('create', 'mobile')
						->addActionContext('edit', 'mobile')
						->addActionContext('add', 'mobile')
						->addActionContext('delete', 'mobile')
						->addActionContext('remove', 'mobile')
						->initContext();	
    }	
	function indexAction()
	{
		$this->view->headTitle($this->view->translate("Watchlist"));
		
		$username = $this->_getParam("username");
		$page = $this->_getParam("page");

		$user = new User();
		if(!$user->getUser($username))
		{
			//Username nicht gefunden
			$this->_redirector = $this->_helper->getHelper('Redirector');
				
			$this->_redirector->gotoRoute(array("language" => Zend_Registry::get('Zend_Locale')->getLanguage(), "controller" => 'error',"action" => 'notfound'), 'default');
		}
	
		$this->view->user = $user;
		
		$model = new WatchlistModel();
		$select = $model->select()
								->where("owner_id = ?", $user->getUserId());
		
		$paginator = new Zend_Paginator(new Watchlist_PaginatorAdapter($select));
		$paginator->setCurrentPageNumber($page);
		$paginator->setItemCountPerPage(1000);
		
		if($paginator->getTotalItemCount() == 1)
		{
			//Bei einer kann die Watchlist direkt geöffnet werden
			$this->_redirector = $this->_helper->getHelper('Redirector');
			
			$this->_redirector->gotoRoute(array('username' => $username, 
												"WID" => $paginator->getItemsByPage(1)->current()->getWatchlistId(),
												"language" => Zend_Registry::get('Zend_Locale')->getLanguage())
										, 'user_watchlist_show');
		}
		
		$this->view->paginator = $paginator;
		
		if (!count($paginator)) {
			$this->view->mbox = new MessageBox();
			$this->view->mbox->setMessage("MSG_WATCHLIST_012", $this->view->url(array("language" => Zend_Registry::get('Zend_Locale')->getLanguage(), "username" => $username), "user_watchlist_create"));
		}
		
		
	}
	
	public function createAction()
	{
		$company_id = $this->_getParam("CID");
		
		$form = new Zend_Form();
		$form->setAction($this->view->url(array("language" => Zend_Registry::get('Zend_Locale')->getLanguage(), "username" => Zend_Registry::get("UserObject")->getNickname()), "user_watchlist_create"));
        $form->setMethod('post');
 
        $options = array('disableLoadDefaultDecorators' => true);
		$name = new Zend_Form_Element_Text("watchlist_name", array_merge($options,array('size' => '30')));
        $submit = new Zend_Form_Element_Submit("submit", $options);
   
        //Prepend an opening div tag before "one" element:
		$name->addDecorator('HtmlTag', array(
		    'tag' => 'div',
		    'openOnly' => true,
		    'placement' => Zend_Form_Decorator_Abstract::PREPEND
		));
		$name->addDecorators(array(
            'ViewHelper', 'Label'
        ));
		$name->setLabel("Watchlist-Name"); 
		 
		
		$submit->addDecorators(array(
            'ViewHelper'
        ));
        //Append a closing div tag after "two" element:
		$submit->addDecorator('HtmlTag', array(
		    'tag' => 'div',
		    'closeOnly' => true,
		    'placement' => Zend_Form_Decorator_Abstract::APPEND
		));
 
        $submit->setLabel("» Watchlist anlegen");  

        $company = new Zend_Form_Element_Hidden("company_id");
        $company->addDecorators(array(
            'ViewHelper'
        ));
        
        if($company_id)
        $company->setValue($company_id);
        
		$form->addElements(array($name, $submit,$company));
		//Set the decorators we need:
        
								
		if ($this->getRequest()->isPost())
		{
			$form->isValid($this->_getAllParams());

			$name = $form->getValue("watchlist_name");
			$owner_id = Zend_Registry::get("UserObject")->getUserId();
			
			$company_id = $form->getValue("company_id");
			if($company_id)
			{
				$companyObj = new Company($company_id);
				$market_id = $companyObj->getMainMarketId();
				$stocks = array(array("market_id" => $market_id, "company_id" => $company_id));
			}
			else 	
				$stocks = null;

			$watchlist = new Watchlist();
			$create = $watchlist->create(array("name" => $name, "owner_id" => $owner_id), $stocks);
			
			//Bei erfolg kann gleich die zur Watchlist gesprungen werden
			if($create)
			{
				$this->_redirector = $this->_helper->getHelper('Redirector');
				
				$this->_redirector->gotoRoute(array('username' => Zend_Registry::get("UserObject")->getNickname(), 
													"WID" => $watchlist->getWatchlistId(),
													"language" => Zend_Registry::get('Zend_Locale')->getLanguage())
											, 'user_watchlist_show');	
			}
				
			
			
			$this->view->mbox = $watchlist->getMessageBox();
						
		}
		else 
		{
			$form->setDefaults(array("watchlist_name" => Zend_Registry::get("UserObject")->getDisplayname()."s "."Watchlist"));
		}
		
		$this->view->form = $form;
	}
	public function editAction()
	{		
		$username = $this->_getParam("username");
		$watchlist_id = $this->_getParam("WID");
		
		$form = new Zend_Form();
		$form->setAction($this->view->url(array("language" => Zend_Registry::get('Zend_Locale')->getLanguage(), "username" => $username), "user_watchlist_edit"));
        $form->setMethod('post');
 
		$name = new Zend_Form_Element_Text("watchlist_name", array('size' => '30'));
		$newsletter = new Zend_Form_Element_Checkbox("signal_newsletter");
        $submit = new Zend_Form_Element_Submit("submit");
   
		$name->setLabel("Watchlist-Name"); 
		$name->setRequired(true);
		$newsletter->setLabel("Tägliche E-Mail mit aktuellen Signalen abonnieren");
        $submit->setLabel("» Änderungen speichern");  
        
		$form->addElements(array(
			$name, 
			$newsletter, 
			$submit));

		$watchlist = new Watchlist($watchlist_id);
								
		if ($this->getRequest()->isPost())
		{
			$form->isValid($this->_getAllParams());

			$name = $form->getValue("watchlist_name");
			$newsletter = $form->getValue("signal_newsletter");

			$edit = $watchlist->edit(array("name" => $name, "send_signal_mail" => $newsletter));
			
			//Bei erfolg kann gleich die zur Watchlist gesprungen werden
			if($edit)
			{
				$this->_redirector = $this->_helper->getHelper('Redirector');
				
				$this->_redirector->gotoRoute(array('username' => Zend_Registry::get("UserObject")->getNickname(), 
													"WID" => $watchlist->getWatchlistId(),
													"language" => Zend_Registry::get('Zend_Locale')->getLanguage())
											, 'user_watchlist_show');	
			}
				
			$this->view->mbox = $watchlist->getMessageBox();
						
		}
		else 
		{
			$form->populate(array("watchlist_name" => $watchlist->getName(), "signal_newsletter" => $watchlist->getSendSignalMail()));
		}
		
		$this->view->form = $form;
	}
	
	public function showAction()
	{
		$this->view->debugTimeStart = microtime(true);
		
		$this->view->headTitle($this->view->translate("Watchlist"));

		$username = Zend_Registry::get("FilterChainRequest")->filter($this->_getParam("username"));
		$watchlist_id = Zend_Registry::get("FilterChainRequest")->filter($this->_getParam("WID"));
		
		$user = new User();
		if(!$user->getUser($username))
		{
			//Username nicht gefunden
			$this->_redirector = $this->_helper->getHelper('Redirector');
				
			$this->_redirector->gotoRoute(array("language" => Zend_Registry::get('Zend_Locale')->getLanguage(), "controller" => 'error',"action" => 'notfound'), 'default');
		}

		$watchlist = new Watchlist($watchlist_id);
		
		if(!$watchlist->isWatchlist())
		{
			//Watchlist-ID ungültig oder so
			$this->_redirector = $this->_helper->getHelper('Redirector');
				
			$this->_redirector->gotoRoute(array('username' => Zend_Registry::get("UserObject")->getNickname(),
													"language" => Zend_Registry::get('Zend_Locale')->getLanguage())
											, 'user_watchlists');	
		}
		
		if($watchlist->getOwnerId() != $user->getId())
		{
			//URL FAKING verhinern
			$this->_redirector = $this->_helper->getHelper('Redirector');
			
			$this->_redirector->gotoRoute(array('username' => $watchlist->getOwner()->getNickname(), 
												"WID" => $watchlist->getWatchlistId(),
												"language" => Zend_Registry::get('Zend_Locale')->getLanguage())
										, 'user_watchlist_show');
		}
	
		$this->view->user = $user;
		
		$watchlist->getStocklist();
		$this->view->watchlist = $watchlist;
		
		$this->view->headTitle($watchlist->getName());
		
		$this->view->paginator = $watchlist;
		
		if (!count($watchlist)) {
			$this->view->mbox = new MessageBox();
			$this->view->mbox->setMessage("MSG_WATCHLIST_013");
		}	
	
	}
	public function showJsonAction()
	{
		$watchlist_id = $this->_getParam("WID");
		
		$watchlist = new Watchlist($watchlist_id);
		$watchlist->getStocklist();
		
		$arr = $watchlist->toArray();
		
		for($i=0;$i<count($arr);$i++)
		{
			$arr[$i]["url"] = $this->view->url(array(
							"language" => Zend_Registry::get('Zend_Locale')->getLanguage(), 
							"isin" => $arr[$i]["isin"]), 
						"stock");
			
			if(isset($arr[$arr[$i]["changeNumber"]]))
				$arr[$i]["changeNumber"] += 1; //um eins erhöhen, um überschreiben zu verhindern
			$arr[$arr[$i]["changeNumber"]] = $arr[$i];
			
			unset($arr[$i]);
		}
		
		krsort($arr);
		
		$this->view->watchlist = $arr;
		
	}
	public function addAction()
	{
		$watchlist_id = $this->_getParam("WID");
		$watchlist_id_post = $this->_getParam("watchlist_id");
		$company_id = $this->_getParam("CID");
		$market_id = $this->_getParam("MID");
		
		//Prüfen wieviel Watchlists der Nutzer hat
		$watchlistSet = Zend_Registry::get("UserObject")->getWatchlists();
		if(count($watchlistSet) == 0)
		{
			//Keine Watchlist vorhanden
			//watchlist anlegen
			$this->_redirector = $this->_helper->getHelper('Redirector');
				
			$this->_redirector->gotoRoute(array('username' => Zend_Registry::get("UserObject")->getNickname(), 
												"CID" => $company_id,
												"language" => Zend_Registry::get('Zend_Locale')->getLanguage())
										, 'user_watchlist_create');
			
		}
		elseif (count($watchlistSet) == 1 || $watchlist_id_post > 0)
		{
			//genau eine Watchlist vorhanden oder WatchlistId bekannt
			if($watchlist_id_post > 0)
				$watchlist = new Watchlist($watchlist_id_post);
			else
				$watchlist = $watchlistSet->current();
			
			//Zusätzliche Sicherheitsüberprüfung	
			if($watchlist->getOwner()->getNickname() != Zend_Registry::get("UserObject")->getNickname())
			{
				$this->_redirector = $this->_helper->getHelper('Redirector');
				
				$this->_redirector->gotoRoute(array(
							"language" => Zend_Registry::get('Zend_Locale')->getLanguage(), 
							"CID" => $company_id,
							"WID" => $watchlist_id,
							"MID" => $market_id,
							"username" => $watchlist->getOwner()->getNickname()
							)
						, 'user_watchlist_create');
			}
				
			$this->view->success =  $watchlist->addStock($company_id, $market_id);
			$this->view->messages = $watchlist->getMessages();
			
			// Einen Meta Refresh mit 3 Sekunden zu einer neuen URL setzen:
			$this->view->headMeta()->appendHttpEquiv('Refresh',
                                '3;URL='.$this->view->url(array(
										"language" => Zend_Registry::get('Zend_Locale')->getLanguage(), 
										"username" => Zend_Registry::get("UserObject")->getNickname(),
										"WID" => $watchlist->getWatchlistId()
												), "user_watchlist_show"));
		}
		elseif (count($watchlistSet) > 1)
		{
		
			//mehrere Watchlists vorhanden
			//dann muss ausgewählt werden
			$form = new Zend_Form();
			$form->setAction($this->view->url(array(
				"language" => Zend_Registry::get('Zend_Locale')->getLanguage(), 
				"username" => Zend_Registry::get("UserObject")->getNickname(),
				"FRAMED" => false), 
			"user_watchlist_add"));
	        $form->setMethod('post');
	 
	        $options = array('disableLoadDefaultDecorators' => true);
			$wid = new Zend_Form_Element_Select("watchlist_id", $options);
	
			foreach ($watchlistSet as $watchlist)
			{
				$wid->addMultiOption($watchlist->getWatchlistId(), $watchlist->getName());
			}
			
	        $submit = new Zend_Form_Element_Submit("submit", $options);
	   
	        //Prepend an opening div tag before "one" element:
			$wid->addDecorator('HtmlTag', array(
			    'tag' => 'div',
			    'openOnly' => true,
			    'placement' => Zend_Form_Decorator_Abstract::PREPEND
			));
			$wid->addDecorators(array(
	            'ViewHelper', 'Label'
	        ));
			$wid->setLabel("Watchlist auswählen"); 
			 
			
			$submit->addDecorators(array(
	            'ViewHelper'
	        ));
	        //Append a closing div tag after "two" element:
			$submit->addDecorator('HtmlTag', array(
			    'tag' => 'div',
			    'closeOnly' => true,
			    'placement' => Zend_Form_Decorator_Abstract::APPEND
			));
	 
	        $submit->setLabel("weiter");     
	        
			$form->addElements(array($wid, $submit));
			
			$this->view->form = $form;
		}

	}
	public function deleteAction()
	{
		$watchlist_id = $this->_getParam("WID");
		$watchlist = new Watchlist($watchlist_id);
		$this->view->success = $watchlist->delete();
		$this->view->messages = $watchlist->getMessages();
	}
	public function removeAction()
	{
		$watchlist_id = $this->_getParam("WID");
		$company_id = $this->_getParam("CID");
		
		$watchlist = new Watchlist($watchlist_id);
		$this->view->success = $watchlist->removeStock($company_id);
		$this->view->messages = $watchlist->getMessages();
		// Einen Meta Refresh mit 3 Sekunden zu einer neuen URL setzen:
		$this->view->headMeta()->appendHttpEquiv('Refresh',
                               '1;URL='.$this->view->url(array(
				"language" => Zend_Registry::get('Zend_Locale')->getLanguage(), 
				"username" => $this->_getParam("username"),
				"WID" => $watchlist_id
						), "user_watchlist_show"));
	}
	
	public function mostWatchedStocksAction()
	{
		$this->view->headTitle($this->view->translate("Watchlist"));
		$this->view->headTitle($this->view->translate("Top 25"));
		/*
		 * 
		SELECT count(*) as anzahl, c.name, c.isin 
		FROM watchlist_companies as w 
		join companies as c on w.company_id = c.company_id  
		group by w.company_id  
		ORDER BY `anzahl`  DESC
		 */
		
		$cm = new CompaniesModel();
		$wcm = new WatchlistCompaniesModel();
		
		$select = $cm->select()->setIntegrityCheck(false)
			->from(array('w' => $wcm->getTableName()), array("anzahl" => "count(*)"))
			->join(array('c' => $cm->getTableName()), "c.company_id = w.company_id", array("name", "isin"))
			->group("w.company_id")
			->order("anzahl DESC")
			->limit(25);
		
		$this->view->mostWatchedStocks = $cm->fetchAll($select);	
		
	}

}