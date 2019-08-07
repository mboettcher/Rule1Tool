<?php
/**
 * ErrorController
 * 
 * @author
 * @version 
 */

class ErrorController extends Zend_Controller_Action
{
 	protected $_redirector = null;

 	public function init()
 	{
 		$this->_redirector = $this->_helper->getHelper('Redirector');
 		
 		$contextSwitch = $this->_helper->getHelper('SwitchContext');
		$contextSwitch	->addActionContext('privileges', 'json')
						->addActionContext('notfound', 'json')
						->addActionContext('error', 'json')
						->addActionContext('error', 'mobile')
						->addActionContext('notfound', 'mobile')
						->initContext();
 	}
   public function errorAction()
    {
        $errors = $this->_getParam('error_handler');
		// Vorherige Inhalte löschen
        $this->getResponse()->clearBody();
        
        switch ($errors->type) {
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:

				//$this->_redirector->goto(, 'error', null, array("language" => Zend_Registry::get('Zend_Locale')->getLanguage()));
				$this->_redirector->gotoRoute(array("language" => Zend_Registry::get('Zend_Locale')->getLanguage(), "controller" => 'error',"action" => 'notfound'), 'default');
				
                break;
            default:
   
                // Anwendungsfehler
                $content =
				"<h1>".$this->view->translate("Ups, es trat ein Fehler auf")."</h1>
				<p>".$this->view->translate("Spätestens jetzt sind auch wir uns des Problems bewusst und werden uns schnellmöglichst um die Behebung kümmern. Bitte versuchen Sie es etwas später noch einmal.")."</p>";
				$content .="<p><a href='".$this->view->baseUrl()."'>".$this->view->baseUrl()."</a></p>";   
   
		if(Zend_Registry::get('systemtype') != "live" )  {       
			
			//Fehler loggen
			if(Zend_Registry::get("Zend_Auth")->hasIdentity())
				$user = "\n"."USER_ID: ".Zend_Registry::get("UserObject")->getUserId(). " NICKNAME: ".Zend_Registry::get("UserObject")->getNickname();
			else
				$user = "";
			Zend_Registry::get('Zend_Log')->err($errors->exception->getMessage() . $user . "\n" .  $errors->exception->getTraceAsString());
			
			//Fehler ausgeben
			$content .="<h3>DEBUG</h3>
			<p>".$errors->exception->getMessage() . "\n" .  $errors->exception->getTraceAsString()."</p>";
			
		
		}else
		{
			//Fehler loggen
			if(Zend_Registry::get("Zend_Auth")->hasIdentity())
				$user = "\n"."USER_ID: ".Zend_Registry::get("UserObject")->getUserId(). " NICKNAME: ".Zend_Registry::get("UserObject")->getNickname();
			else
				$user = "";
			Zend_Registry::get('Zend_Log')->err($errors->exception->getMessage() . $user . "\n" .  $errors->exception->getTraceAsString());
		}


        $this->view->content = $content;
                break;
        }
        $this->view->success = false;
		$this->view->messages = array(array("msg" => $this->view->translate("Ein unerwarteter Fehler trat in der Anwendung auf.")));

		//robots noindex setzen
		$this->view->headMeta()->appendName('robots', 'noindex');
		
    }
    
    public function privilegesAction()
    {
    	$this->view->success = false;
		$this->view->messages = array(array("msg" => $this->view->translate("Authorisierung fehlgeschlagen. Bitte anmelden.")));
		//robots noindex setzen
		$this->view->headMeta()->appendName('robots', 'noindex');
	}
    public function notfoundAction()
    {
        // 404 Fehler -- Kontroller oder Aktion nicht gefunden ODER keine View erreichbar(mutmaßlich)
        $this->getResponse()->setRawHeader('HTTP/1.1 404 Not Found');
        
        $this->view->success = false;
		$this->view->messages = array(array("msg" => $this->view->translate("Angefragte Seite wurde nicht gefunden.")));
		
		//robots noindex setzen
		$this->view->headMeta()->appendName('robots', 'noindex');
    }
}
