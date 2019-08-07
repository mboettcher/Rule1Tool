<?php

/**
 * AdminController
 * 
 * @author
 * @version 
 */

require_once 'Zend/Controller/Action.php';

class AdminController extends Zend_Controller_Action {
	/**
	 * The default action - show the home page
	 */
	public function indexAction() {
		// TODO Auto-generated AdminController::indexAction() default action
	}
	public function companiesAction()
	{
		$tbl = new CompaniesModel();
		$select = $tbl->select()->order("name ASC");
		
		$paginator = new Zend_Paginator(new Zend_Paginator_Adapter_DbTableSelect($select));
		$paginator->setItemCountPerPage(1000);
		$paginator->setCurrentPageNumber($this->_getParam('page'));
		$this->view->paginator = $paginator;
	}
	
	public function companyEditAction()
	{
		$company = new Company($this->_getParam("CID"));
		$this->view->company = $company;
		
		$form = new Zend_Form();
		
		$name = new Zend_Form_Element_Text('name');
		$name->addValidator(new Zend_Validate_StringLength(2,250));
		$name->setLabel("Name");
		
		$isin = new Zend_Form_Element_Text("isin");
		$isin->addValidator(new Validate_Isin());
		$isin->setLabel("ISIN");
		
		$website = new Zend_Form_Element_Text("website");
		$website->addValidator(new Validate_URI());
        $website->setLabel("Website");
        
        $wikipedia = new Zend_Form_Element_Text("wikipedia");
		$wikipedia->addValidator(new Validate_URI());
        $wikipedia->setLabel("Wikipedia URL");
		      
        $submit = new Zend_Form_Element_Submit("Speichern");
        
        
        
		$form->addElements(array($name, $isin, $website, $wikipedia));
		$form->addElement($submit);
		$form->setDecorators(array(
		    'FormElements',
		    array('HtmlTag', array('tag' => 'dl')),
		    'Form'
		));
		
		$companyModel = new CompaniesModel();

		if ($this->getRequest()->isPost())
		{
			if ($form->isValid($this->_getAllParams())) {
			    // erfolgreich!
			    $values = $form->getValues();
			    
			    
			    $update = $companyModel->update(
			    		array("wikipedia" => $values["wikipedia"], 
			    		"website" => $values["website"],
			    		"name" => $values["name"]
			    		
			    		)
			    		, $companyModel->getAdapter()->quoteInto("company_id = ?", $this->_getParam("CID")));
			    if($update)
			    	echo "updated";
				
			} else {
			    // fehlgeschlagen!
			    echo "error";
			}			
		}
		else 
		{
			
			$form->populate(array("name" => $company->getName(), "isin" => $company->getISIN(), 
							"website" => $company->getWebsite(), "wikipedia" => $company->getWikipedia()));
		}

		


		$this->view->form = $form;
				
		
	}
	public function companyEditPictureAction()
	{
		$company = new Company($this->_getParam("CID"));
		
		$form = new Zend_Form();
		$form->setAction($this->view->url(array("language" => Zend_Registry::get('Zend_Locale')->getLanguage(), "CID" => $company->getId()), "admin_company_edit_pic"));
        $form->setMethod('post');
        $form->setAttrib('enctype', 'multipart/form-data');    
        
		$options = array('disableLoadDefaultDecorators' => true, "decorators" => array(array('ViewHelper')));
		$image = new Zend_Form_Element_File('image', $options);
		
        $submit = new Zend_Form_Element_Submit("submit", $options);
        $submit->setLabel("Bild hochladen");
        $reset = new Zend_Form_Element_Reset("reset", $options);
        $reset->setLabel("Abbrechen");        
        
		$form->addElements(array($image));
		$form->addElements(array($submit, $reset));
		$form->setDecorators(array(array('ViewScript', array(
							    'viewScript' => 'forms/_admin_company_edit_picture.phtml',
							    'class'      => ''
								))));
		

								
		if ($this->getRequest()->isPost())
		{
			$form->isValid($this->_getAllParams());

			$picSet = new Company_PictureSet();
			if($picSet->input($company->getId()))
			{
				//UserObject aktualisieren
				$company->getCompanyById($company->getId());
				
				$this->view->mbox = $picSet->getMessageBox();
			}
			else 
			{
				$this->view->mbox = $picSet->getMessageBox();
			}			
		}
		$this->view->company = $company;
		$this->view->form = $form;
		
		
	}
	public function companyAddAction()
	{
		if($this->_request->getParam('name') && $this->_request->getParam('isin'))
		{
			$new_company = new Company();
			$result = $new_company->setCompany($this->_request->getParam('name'), $this->_request->getParam('isin'), array());
			if($result)
			{
				//Hat wohl geklappt...
				//Also fix anzeigen
				$this->_redirector = $this->_helper->getHelper('Redirector');
				$this->_redirector->gotoRoute(array('isin' => $this->_request->getParam('isin'), "language" => Zend_Registry::get('Zend_Locale')->getLanguage()), 'stock'); //REDIRECT, damit Speicher freigegeben wird
			}
			else
			{
				//Da hat was nicht geklappt
				//Was machen wir denn jetzt?
				throw new Zend_Exception("Daten konnten nicht eingefügt werden.");
			}	
		}
		
	}

}
?>

