<?php
/**
 * AuthController
 * 
 * @author Rule1Tool GbR
 * @version 
 */

class AuthController extends Zend_Controller_Action
{
	
	function init()
	{
		$this->view->params = $this->_request->getParams();
		
		$contextSwitch = $this->_helper->getHelper('SwitchContext');
		$contextSwitch	->addActionContext('login', 'json')
						->addActionContext('login', 'mobile')
						->initContext();
		
	}
	function loginAction()
	{
		$this->view->mbox = new MessageBox();
		//robots noindex setzen
		$this->view->headMeta()->appendName('robots', 'noindex');
		
    	if ($this->_request->isPost()) 
    	{ 
    	    // collect the data from the user 
            Zend_Loader::loadClass('Zend_Filter_StripTags'); 
            $f = new Zend_Filter_StripTags(); 
            $email = $f->filter($this->_request->getPost('email')); 
            $password = $f->filter($this->_request->getPost('password'));
    	    if (empty($email)) 
            { 
            	$this->view->mbox->setMessage("MSG_AUTH_001");
            	$this->view->success = false;
                $this->view->messages = $this->view->mbox->getMessages();
            } 
            else 
            { 
                // setup Zend_Auth adapter for a database table  
                $authAdapter = new Zend_Auth_Adapter_DbTable(Zend_Registry::get('Zend_Db')); 
                $authAdapter->setTableName('users'); 
                $authAdapter->setIdentityColumn('email'); 
                $authAdapter->setCredentialColumn('password');
                $authAdapter->setCredentialTreatment('MD5(?) AND status = 1');
                
                // Set the input credential values to authenticate against 
                $authAdapter->setIdentity($email); 
                $authAdapter->setCredential($password); 
                 
                // do the authentication  
                $auth = Zend_Auth::getInstance(); 
                $result = $auth->authenticate($authAdapter); 
                if ($result->isValid()) 
                { 
                    // success: store database row to auth's storage 
                    // system. (Not the password though!) 
                    $data = $authAdapter->getResultRowObject("user_id", 'password'); 
                    
                    $auth->getStorage()->write($data); 

                    Zend_Session::regenerateId();
                    
                    /* Login in DB schreiben */
                    $lgModel = new LoginsModel();
                    $lgModel->insert(array("user_id" => $data->user_id, "SID" => Zend_Session::getId()));
                    
                    //Session Namespace registieren für allgemeine verwendung
                    new Zend_Session_Namespace('Rule1Tool');
                    
                  	$this->_helper->getHelper('Redirector')->setPrependBase(false);
                    if(!strstr($_SERVER["REQUEST_URI"], "/login") && !strstr($_SERVER["REQUEST_URI"], "/logout"))
                   	  	$this->_redirect($_SERVER["REQUEST_URI"]);  //Wenn vorherige Seite bekannt, dann wieder dahin zurück
                   	else
                   		$this->_redirect($this->view->baseUrl()); //Ansonsten ab zur Startseite
                    
                } else 
                { 
                	//Prüfen ob Account nur noch nicht aktiviert
                	$tbl = new UsersModel();
                	$rows = $tbl->fetchAll($tbl->select()->where("email = ?", $email)->where("password = MD5(?)", $password)->where("status = 0"));
                	
                	if($rows->count() > 0)
                	{
                		//d.h. Mail und PW sind richtig
                		// ==> status ist nicht richtig
                		$this->view->mbox->setMessage("MSG_AUTH_003");
                	}
                	else 
                	{
                		$this->view->mbox->setMessage("MSG_AUTH_002");
                	}
      
                	$this->view->success = false;
                	$this->view->messages = $this->view->mbox->getMessages();
                } 
            }
    	}
    	else 
    	{
    		$this->view->success = false;
			$this->view->messages = array(array("msg" => $this->view->translate("Authorisierung fehlgeschlagen. Bitte anmelden.")));
    	}
    	
    	//Je nach Systemstatus andere View darstellen ggf Layout ausschalten
		//Zend_Registry::get('systemstatus')
		if(Zend_Registry::get('systemstatus') == "ONLINE")
		   	$this->_helper->viewRenderer('login');
		else
		{
		    $this->_helper->layout->setLayout('wartung');
    		$this->_helper->viewRenderer('xlogin');
		}

	}
	function logoutAction()
	{
		//robots noindex setzen
		$this->view->headMeta()->appendName('robots', 'noindex');
		
	    Zend_Session::forgetMe();
		Zend_Auth::getInstance()->clearIdentity(); 
		
		$ns = new Zend_Session_Namespace('Rule1Tool');
		$ns->unsetAll();
		
        $this->_redirect('/'); 
	}


}