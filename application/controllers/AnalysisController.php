<?php

/**
 * AnalysisController
 * 
 * @mb
 */

class AnalysisController extends Zend_Controller_Action {

	
	public function init()
	{
		$this->view->isFramed = $this->_request->getParam("FRAMED");
		
        //Layout deaktivieren
        if($this->view->isFramed)
        	$this->_helper->layout->setLayout('framed');
        else 
        	$this->view->isFramed = false;
                
		//Layout deaktivieren wenn AJAX-Request
        if($this->view->isAjax = $this->_request->isXmlHttpRequest())
              $this->_helper->layout->disableLayout();
         
        $contextSwitch = $this->_helper->getHelper('SwitchContext');
		$contextSwitch	->addActionContext('set-favourite', 'json')
						->initContext();
	}
	/**
	 * Gibt das Formular für die Dateneingabe zurück / Validiert und fügt gesendete Daten ein
	 *
	 */
    public function createAction()
    {
        $company_id = $this->_request->getParam("CID");

        if($company_id)
		{
			$this->view->company = new Company($company_id);
			
			//Caching des Formulars
            $identifier = "form_analysis_input_".$company_id;
    		if(!$form = Zend_Registry::get("Zend_Cache_Core")->load($identifier))
        	{
    			$form = new Form_AnalysisInput(null, $company_id);
    			Zend_Registry::get("Zend_Cache_Core")->save($form, $identifier, array("form", "analysis_input"));
        	}
    		
            if($this->getRequest()->isPost() && $this->_getParam("moveOneYear"))
            {
            	//$form->isValid($this->view->analysis->getFormArray());
                $form->isValid($this->_getAllParams(), true);
                $this->view->form = $form;
            }
    		elseif ($this->getRequest()->isPost())
            {
                if (!$form->isValid($this->_getAllParams())) {
                	// Fehlgeschlagene Prüfung; Form wieder anzeigen
                	$this->view->form = $form;
                	//informieren, dass Analyse noch nicht gespeichert wurde
                	$this->view->mbox = new MessageBox();
            		$this->view->mbox->setMessage("MSG_ANALYSIS_005");
               		
            	}
            	else
            	{
    				$values1 = $form->basicdata->getValues();
    				
    				$values2 = array_merge_recursive(
    											$form->keydata->a->getValues(),
    											$form->keydata->b->getValues(), 
    											$form->keydata->c->getValues(), 
    											$form->keydata->d->getValues(), 
    											$form->keydata->e->getValues(), 
    											$form->keydata->f->getValues(), 
    											$form->keydata->g->getValues(), 
    											$form->keydata->h->getValues(), 
    											$form->keydata->i->getValues(), 
    											$form->keydata->j->getValues()
    											 );
    				$values = array_merge($values1, $values2);
    				//USER_ID ist immer der angemeldete Nutzer
    				//$values["basicdata"]["user_id"] = Zend_Registry::get("UserObject")->getId(); //Wird in Classe gehandelt
    				//Erstelle neue Analyse anhand der Eingabedaten
            		$analysis = new Analysis();
            		//Prüfe ob Erfolgreich
            		if($analysis->setNewAnalysis($values))
            		{
           			
            			$this->view->mbox = new MessageBox();
            			$this->view->mbox->setMessage("MSG_ANALYSIS_003", $analysis->getId());
            			
            			// Einen Meta Refresh mit 3 Sekunden zu einer neuen URL setzen:
						$this->view->headMeta()->appendHttpEquiv('Refresh',
                                   '3;URL='.$this->view->url(
						array("AID" => $analysis->getId(),
							"CID" => $analysis->getCompanyId(),
							 "language" => Zend_Registry::get('Zend_Locale')->getLanguage(),
							"FRAMED" => $this->view->isFramed
						), "analysis_show"));
            			
            			//Message ausgeben
            			$this->view->analysis_create = true;
            			$this->view->analysis_id = $analysis->getId();
            			
            		}
            		else
            		{
            			throw new Zend_Exception("Analyse konnte nicht angelegt werden.");
            		}		
            	}
            }
            else
            {
            	$analysis_id = $this->_getParam("AID");
            	$doCopie = $this->_getParam("COPIE");
            	if($analysis_id && $doCopie)
            	{
            		//DATEN holen für Klonierung
                	$this->view->analysis = new Analysis_Calculator($analysis_id);
               		$form->isValid($this->view->analysis->getFormArray());	
            	}
            	 
                $this->view->form = $form;
            }   		    
		}
		else 
		    echo "Keine Company-ID angegeben!";
    }
	/**
	 * Gibt das Formular für die Dateneingabe zurück / Validiert und fügt gesendete Daten ein
	 *
	 */
    public function editAction()
    {
        $analysis_id = $this->_request->getParam("AID");
        $this->view->new_analysis = $this->_request->getParam("NEW");

        if($analysis_id)
		{
            $identifier = "form_analysis_input_edit_".$analysis_id;
    		if(!$form = Zend_Registry::get("Zend_Cache_Core")->load($identifier))
        	{
    			$form = new Form_AnalysisInput(null, null, $analysis_id);
    			Zend_Registry::get("Zend_Cache_Core")->save($form, $identifier, array("form", "analysis_input"));
        	}
        	
        	$this->view->analysis = new Analysis_Calculator($analysis_id);
    		$this->view->company = new Company($this->view->analysis->getCompanyId()); 
    		  
            if($this->getRequest()->isPost() && $this->_getParam("moveOneYear"))
            {
                $form->isValid($this->_getAllParams(), true);
                $this->view->form = $form;
            }
    		elseif ($this->getRequest()->isPost())
            {
                if (!$form->isValid($this->_getAllParams())) {
                	// Fehlgeschlagene Prüfung; Form wieder anzeigen
                	$this->view->form = $form;

					//informieren, dass Analyse noch nicht gespeichert wurde
                	$this->view->mbox = new MessageBox();
            		$this->view->mbox->setMessage("MSG_ANALYSIS_006");		
            	}
            	else
            	{
    				$values1 = $form->basicdata->getValues();
    				
    				$values2 = array_merge_recursive(
    											$form->keydata->a->getValues(),
    											$form->keydata->b->getValues(), 
    											$form->keydata->c->getValues(), 
    											$form->keydata->d->getValues(), 
    											$form->keydata->e->getValues(), 
    											$form->keydata->f->getValues(), 
    											$form->keydata->g->getValues(), 
    											$form->keydata->h->getValues(), 
    											$form->keydata->i->getValues(), 
    											$form->keydata->j->getValues()
    											 );
    				$values = array_merge($values1, $values2);
              		    
            		//Prüfe ob Erfolgreich
            		if($this->view->analysis->editAnalysis($values))
            		{
            			$this->view->mbox = new MessageBox();
            			$this->view->mbox->setMessage("MSG_ANALYSIS_004");
    
            			//Erfolgreich editiert
            			$this->view->analysis_edit = true;
            			//Formular für die Ausagbe
            			$this->view->form = $form;
            		}
            		else
            		{
            			throw new Zend_Exception("Analyse konnte nicht angelegt werden.");
            		}		
            	}
            }
            else
            {
                //DATEN holen
                
                $form->isValid($this->view->analysis->getFormArray());
                $this->view->form = $form;
            }
    			    		    
		}
		else 
		    echo "Keine Analysis-ID angegeben!";
    }
    public function showAction()
	{
		$company_id = $this->_request->getParam("CID");
		$analysis_id = $this->_request->getParam("AID");
             
		if($company_id)
		{
			$company = new Company($company_id);
			
			//Alle Analysen zum Unternehmen holen
			//@TODO mit Paginator arbeiten
			$this->view->analysis_list = $company->getAnalysesList(Zend_Registry::get("UserObject")->getUserId());

			if(empty($analysis_id))
			{
				//Automatisch eine Analysis-Id holen, wenn keine angegeben
				$analysis_id = $company->getPreselectedAnalysisId(Zend_Registry::get("UserObject")->getUserId());
			}
			
			if($this->view->analysis_list && $analysis_id)
			{
			    $this->view->company = $company;
			    
			    $analysis = new Analysis_Calculator($analysis_id);
			    
			    $my_estimated_growth_testvalue = $this->_request->getParam("my_estimated_growth_testvalue");
				if($my_estimated_growth_testvalue && !empty($my_estimated_growth_testvalue))
					$analysis->setMyEstimatedGrowthTestvalue($my_estimated_growth_testvalue);
				
				$my_future_kgv_testvalue = $this->_request->getParam("my_future_kgv_testvalue");
				if($my_future_kgv_testvalue && !empty($my_future_kgv_testvalue))
					$analysis->setMyFutureKgvTestvalue($my_future_kgv_testvalue);
			    
				$my_eps_testvalue = $this->_request->getParam("my_eps_testvalue");
				if($my_eps_testvalue && !empty($my_eps_testvalue))
					$analysis->setMyEpsTestvalue($my_eps_testvalue);
					
    			//Hole die Analyse
    			//Prüfe ob i.O.
    			if($analysis->getAnalysisById())
    			{
    				//Gib das Ergebnis zurück
    				$this->view->analysis_show = true;
    				//Gib die Analysis-Daten zurück
    				$this->view->analysis = $analysis;
    				
    				//Kommentare
    				//Kommentare - Seiten/pages
					//$this->view->CommentPageAnalysis = $this->_getParam('CommentPageAnalysis');
					//$this->view->commentPaginatorAnalysis = new Zend_Paginator($analysis->getThread(Zend_Registry::get("Zend_Locale")->getLanguage())->getPaginatorAdapter());
					//$this->view->commentPaginatorAnalysis->setCurrentPageNumber($this->view->CommentPageAnalysis);
       			}
    			else
    			{
    				//Gib FALSE und den Fehler zurück
    				$this->view->analysis_show = false;
    				$this->view->messageBox = $analysis->getMessageBox();
    			}	    
			}
			else
			{
			    //Keine Analysen vorhanden!
			    $this->view->analysis_show = false;
			    $this->view->messageBox = $company->getMessageBox();
			}
    					
		}
		else
		{
			$this->view->analysis_show = false;
		    echo "Keine Company-ID angegeben!";
		}				
	}
	/**
	 * Set Favourite Analysis with AJAX
	 *
	 */
	public function setFavouriteAction()
	{
  	    $company_id = $this->_request->getParam("CID");
		$analysis_id = $this->_request->getParam("AID");
		$user_id = $this->_request->getParam("UID");
		
		if($company_id && $analysis_id && $user_id)
		{
			$company = new Company($company_id);
			$res = $company->setAnalysisFavourit($analysis_id, $user_id);
			$this->view->success = $res;
			$this->view->messages = $company->getMessages();
		}
	    
	}
	public function helpAction()
	{
		$this->view->headTitle('Analysen');
		$this->view->headTitle('Hilfe');
	}
	
	public function latestAnalysisAction()
	{
		$this->view->headTitle($this->view->translate("Analysen"));
		$this->view->headTitle($this->view->translate("Letzten 25 Analysen"));
		/*
		 * 
		SELECT count(*) as anzahl, c.name, c.isin 
		FROM watchlist_companies as w 
		join companies as c on w.company_id = c.company_id  
		group by w.company_id  
		ORDER BY `anzahl`  DESC
		 */
		
		$cm = new CompaniesModel();
		$am = new AnalysisModel();
		
		$select = $cm->select()->setIntegrityCheck(false)
			->from(array('a' => $am->getTableName()), array("user_id", "date_edit", 'analysis_id'))
			->join(array('c' => $cm->getTableName()), "c.company_id = a.company_id", array("name", "isin"))
			->where("a.private = 0")
			->order("a.date_edit DESC")
			->limit(25);
		
		$this->view->lastAnalysises = $cm->fetchAll($select);	
		
	}
}
