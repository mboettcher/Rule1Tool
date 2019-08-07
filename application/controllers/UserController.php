<?php
/**
 * UserController
 * 
 * @author
 * @version 
 */

class UserController extends Zend_Controller_Action
{
	function preDispatch() 
	{ 
	
	}
function init()
	{		
		$contextSwitch = $this->_helper->getHelper('SwitchContext');
		$contextSwitch	->addActionContext('register', 'mobile')
						->addActionContext('profile', 'mobile')
						//->addActionContext('setup', 'mobile')
						->addActionContext('invite', 'mobile')
						->addActionContext('edit', 'mobile')
						->addActionContext('reset-password', 'mobile')
						->addActionContext('activate', 'mobile')
						->initContext();		
		
		
	}
	public function profileAction()
	{
		//Profil ausgeben
		$username = $this->_getParam("username");
		$this->view->user = new User();
		$this->view->user->getUser($username);
		
	}
	public function setupAction()
	{
		//Einstellungen
		$username = $this->_getParam("username");
		$this->view->user = new User();
		$this->view->user->getUser($username);
		
		$this->view->isFramed = $this->_request->getParam("FRAMED");
        //Layout deaktivieren
        if($this->view->isFramed)
        	$this->_helper->layout->setLayout('framed');

		$form = new Zend_Form();
		$form->setAction($this->view->url(array("language" => Zend_Registry::get('Zend_Locale')->getLanguage(), "username" => $username), "user_setup"));
	    $form->setMethod('post');
	    
		$options = array('disableLoadDefaultDecorators' => true, "decorators" => array(array('ViewHelper')));
		
		$email = new Zend_Form_Element_Text('email', $options);
		$password = new Zend_Form_Element_Password('password', $options);
		$password->setAttrib("autocomplete", "off");
		$password_confirm = new Zend_Form_Element_Password('password_confirm', $options);
		$password_confirm->setAttrib("autocomplete", "off");
		$newsletter = new Zend_Form_Element_Checkbox("newsletter", $options);
		$indikatorSMA = new Zend_Form_Element_Select('indikator_sma', $options);
		$indikatorSMA->setMultiOptions(array(10 => "10 Tage", 30 => "30 Tage", 50 => "50 Tage"));
        $submit = new Zend_Form_Element_Submit("submit", $options);
        $submit->setLabel("» Einstellungen speichern");
      
		$form->addElements(array($email, $password, $password_confirm, $indikatorSMA, $newsletter));
		$form->addElements(array($submit));
		$form->setDecorators(array(array('ViewScript', array(
							    'viewScript' => 'forms/_user_setup.phtml',
							    'class'      => ''
								))));

		if ($this->getRequest()->isPost())
		{
			$form->isValid($this->_getAllParams());

			
			if(Zend_Registry::get("UserObject")->edit($form->getValues()))
			{
				$this->view->mbox = Zend_Registry::get("UserObject")->getMessageBox();
				$this->view->form = $form;
			}
			else 
			{
				$this->view->mbox = Zend_Registry::get("UserObject")->getMessageBox();
				$this->view->form = $form;
			}			
		}
		else 
		{
			$form->populate(array(
					"email" => Zend_Registry::get("UserObject")->getEmail(), 
					"newsletter" => Zend_Registry::get("UserObject")->getNewsletter(),
					"indikator_sma" => Zend_Registry::get("UserObject")->getIndikatorSMA()
			));
			$this->view->form = $form;
		}
	}
	
	public function editAction()
	{
		//Profil bearbeiten
		$username = $this->_getParam("username");
		$this->view->user = new User();
		$this->view->user->getUser($username);
		
		$this->view->isFramed = $this->_request->getParam("FRAMED");
        //Layout deaktivieren
        if($this->view->isFramed)
        	$this->_helper->layout->setLayout('framed');

		$form = new Zend_Form();
		$form->setAction($this->view->url(array("language" => Zend_Registry::get('Zend_Locale')->getLanguage(), "username" => $username), "user_edit"));
        $form->setMethod('post');
        
		$options = array('disableLoadDefaultDecorators' => true, "decorators" => array(array('ViewHelper')));
		
		$firstname = new Zend_Form_Element_Text('firstname', $options);
		$lastname = new Zend_Form_Element_Text('lastname', $options);
		$nickname = new Zend_Form_Element_Text('nickname', $options);
		$useRealname = new Zend_Form_Element_Checkbox("use_realname", $options);  
		
        $submit = new Zend_Form_Element_Submit("submit", $options);
        $submit->setLabel("» Änderungen speichern");
        $reset = new Zend_Form_Element_Reset("reset", $options);
        $reset->setLabel("Abbrechen");        
        
		$form->addElements(array($firstname,$lastname, $useRealname, $nickname));
		$form->addElements(array($submit, $reset));
		$form->setDecorators(array(array('ViewScript', array(
							    'viewScript' => 'forms/_user_edit.phtml',
							    'class'      => ''
								))));
								
		if ($this->getRequest()->isPost())
		{
			$form->isValid($this->_getAllParams());

			if($this->view->user->edit($form->getValues()))
			{
				$this->view->mbox = $this->view->user->getMessageBox();
				$this->view->form = $form;
			}
			else 
			{
				$this->view->mbox = $this->view->user->getMessageBox();
				$this->view->form = $form;
			}			
		}
		else 
		{
			$form->populate(
				array(
					"firstname" => $this->view->user->getFirstname(),
					"lastname" => $this->view->user->getLastname(),
					"nickname" => $this->view->user->getNickname(),
					"use_realname" => $this->view->user->isUseRealname()
				));
			$this->view->form = $form;
		}
		
	}
	public function editPictureAction()
	{
		//Profilbild bearbeiten
		$username = $this->_getParam("username");
		$this->view->user = new User();
		$this->view->user->getUser($username);
		
		$this->view->isFramed = $this->_request->getParam("FRAMED");
        //Layout deaktivieren
        if($this->view->isFramed)
        	$this->_helper->layout->setLayout('framed');

		$form = new Zend_Form();
		$form->setAction($this->view->url(array("language" => Zend_Registry::get('Zend_Locale')->getLanguage(), "username" => $username), "user_edit_picture"));
        $form->setMethod('post');
        $form->setAttrib('enctype', 'multipart/form-data');    
        
		$options = array('disableLoadDefaultDecorators' => true, "decorators" => array(array('ViewHelper')));
		//$options=array();
		$image = new Zend_Form_Element_File('image', $options);
		
        $submit = new Zend_Form_Element_Submit("submit", $options);
        $submit->setLabel("Bild hochladen");
        $reset = new Zend_Form_Element_Reset("reset", $options);
        $reset->setLabel("Abbrechen");        
        
		$form->addElements(array($image));
		$form->addElements(array($submit, $reset));
		$form->setDecorators(array(array('ViewScript', array(
							    'viewScript' => 'forms/_user_edit_picture.phtml',
							    'class'      => ''
								))));
		

								
		if ($this->getRequest()->isPost())
		{
			$form->isValid($this->_getAllParams());

			$picSet = new User_PictureSet();
			if($picSet->input($this->view->user->getUserId()))
			{
				//UserObject aktualisieren
				$this->view->user->getUser($this->view->user->getUserId());
				
				$this->view->mbox = $picSet->getMessageBox();
			}
			else 
			{
				$this->view->mbox = $picSet->getMessageBox();
			}			
		}
		
		$this->view->form = $form;		
	}
	function registerAction()
	{
		$this->view->isFramed = $this->_request->getParam("FRAMED");
        //Layout deaktivieren
        if($this->view->isFramed)
        	$this->_helper->layout->setLayout('framed');

		$form = new Zend_Form();
		$form->setAction($this->view->url(array("language" => Zend_Registry::get('Zend_Locale')->getLanguage()), "user_register"));
        $form->setMethod('post');
		/*
		 * "nickname" => $data["nickname"], 
									"password" => md5($data["password"]), 
									"email" => $data["email"], 
									"firstname" => $data["firstname"],
									"lastname" => $data["lastname"],
									"newsletter" => $data["newsletter"]
		 */
		$options = array('disableLoadDefaultDecorators' => true, "decorators" => array(array('ViewHelper')));
		
		$email = new Zend_Form_Element_Text('email', $options);
		$nickname = new Zend_Form_Element_Text('nickname', $options);
		$password = new Zend_Form_Element_Password('password', $options);
		$password_confirm = new Zend_Form_Element_Password('password_confirm', $options);
		$newsletter = new Zend_Form_Element_Checkbox("newsletter", $options);
		//$agb = new Zend_Form_Element_Checkbox("agb", $options);        
        $submit = new Zend_Form_Element_Submit("submit", $options);
        $submit->setLabel("» Anmeldung abschließen");
        $reset = new Zend_Form_Element_Reset("reset", $options);
        $reset->setLabel("Abbrechen");
        $invitation = null;
	    if(Zend_Registry::get("config")->general->invitations->active == true)
        {
        	//INVITE System aktiviert
        	$invitation = new Zend_Form_Element_Text('invitation', $options);
        }
        
        
		$form->addElements(array($email,$nickname, $password, $password_confirm, $newsletter
									//, $agb
									, $invitation));
		$form->addElements(array($submit, $reset));
		$form->setDecorators(array(array('ViewScript', array(
							    'viewScript' => 'forms/_user_register.phtml',
							    'class'      => ''
								))));
		
		

		if ($this->getRequest()->isPost())
		{
			$form->isValid($this->_getAllParams());

			$user = new User();
			if($user->newUser($form->getValues()))
			{
				$this->view->mbox = $user->getMessageBox();
			}
			else 
			{
				$this->view->mbox = $user->getMessageBox();
				$this->view->form = $form;
			}			
		}
		else 
		{
			$form->populate(array("invitation" => $this->_request->getParam("INVITE")));
			$this->view->form = $form;
		}
	}
	public function activateAction()
	{
		$actKey = $this->_getParam("ActKey");
		$uid = $this->_getParam("UID");
		$user = new User($uid);
		if($user->activateUser($actKey))
		{
			//Nutzer aktiviert
			$this->view->mbox = $user->getMessageBox();
			// Einen Meta Refresh mit 3 Sekunden zu einer neuen URL setzen:
			$this->view->headMeta()->appendHttpEquiv('Refresh',
                    '3;URL='.$this->view->url(array("language" => Zend_Registry::get('Zend_Locale')->getLanguage()
					), "user_login"));
		}
		else
		{
			//Nutzer nicht aktiviert
			$this->view->mbox = $user->getMessageBox();
		}
	}
	public function inviteAction()
	{
		$form = new Zend_Form();
		$form->setAction($this->view->url(array("language" => Zend_Registry::get('Zend_Locale')->getLanguage(), "username" => Zend_Registry::get("UserObject")->getNickname()), "user_invite"));
        $form->setMethod('post');
 
        $options = array('disableLoadDefaultDecorators' => true);
		$email = new Zend_Form_Element_Text("empfaenger_mail", $options);
        $submit = new Zend_Form_Element_Submit("submit", $options);
   
        //Prepend an opening div tag before "one" element:
		$email->addDecorator('HtmlTag', array(
		    'tag' => 'div',
		    'openOnly' => true,
		    'placement' => Zend_Form_Decorator_Abstract::PREPEND
		));
		$email->addDecorators(array(
            'ViewHelper', 'Label'
        ));
		$email->setLabel("E-Mail-Adresse"); 
		 
		
		$submit->addDecorators(array(
            'ViewHelper'
        ));
        //Append a closing div tag after "two" element:
		$submit->addDecorator('HtmlTag', array(
		    'tag' => 'div',
		    'closeOnly' => true,
		    'placement' => Zend_Form_Decorator_Abstract::APPEND
		));
 
        $submit->setLabel("Einladung senden");     
        
		$form->addElements(array($email, $submit));
		//Set the decorators we need:
        
								
		if ($this->getRequest()->isPost())
		{
			$form->isValid($this->_getAllParams());

			$empfaenger = $form->getValue("empfaenger_mail");
			if(Zend_Registry::get("UserObject")->sendInvitation($empfaenger))
			{
				$this->view->mbox = Zend_Registry::get("UserObject")->getMessageBox();
			}
			else 
			{
				$this->view->mbox = Zend_Registry::get("UserObject")->getMessageBox();
			}			
		}
		
		$this->view->form = $form;
	}
	public function resetPasswordAction()
	{
		$form = new Zend_Form();
		$form->setAction($this->view->url(array("language" => Zend_Registry::get('Zend_Locale')->getLanguage()), "user_resetpw"));
        $form->setMethod('post');
 
        $options = array('disableLoadDefaultDecorators' => true);
		$email = new Zend_Form_Element_Text("email", $options);
        $submit = new Zend_Form_Element_Submit("submit", $options);
   
        //Prepend an opening div tag before "one" element:
		$email->addDecorator('HtmlTag', array(
		    'tag' => 'div',
		    'openOnly' => true,
		    'placement' => Zend_Form_Decorator_Abstract::PREPEND
		));
		$email->addDecorators(array(
            'ViewHelper', 'Label'
        ));
		$email->setLabel("E-Mail-Adresse"); 
		 
		
		$submit->addDecorators(array(
            'ViewHelper'
        ));
        //Append a closing div tag after "two" element:
		$submit->addDecorator('HtmlTag', array(
		    'tag' => 'div',
		    'closeOnly' => true,
		    'placement' => Zend_Form_Decorator_Abstract::APPEND
		));
 
        $submit->setLabel("Kennwort per Mail senden");     
        
		$form->addElements(array($email, $submit));
		//Set the decorators we need:
        
								
		if ($this->getRequest()->isPost())
		{
			$form->isValid($this->_getAllParams());

			$user = new User();
			
			if($user->getUser($form->getValue("email")))
			{
				//Nutzer-E-Mail existiert
				$user->resetPassword($form->getValue("email"));
			}
			
			$this->view->mbox = $user->getMessageBox();
		
		}
		
		$this->view->form = $form;
	}
	public function setupIndikatorsAction()
	{
		$this->view->isFramed = $this->_request->getParam("FRAMED");
        //Layout deaktivieren
        if($this->view->isFramed)
        	$this->_helper->layout->setLayout('framed');

        $form = new Form_UserIndikatorSetup(Zend_Registry::get("UserObject"));        	
								
		if ($this->getRequest()->isPost())
		{
			$form->isValid($this->_getAllParams());

			//$this->view->user->edit($form->getValues()))
			
			//	$this->view->mbox = $this->view->user->getMessageBox();
				$this->view->form = $form;
						
		}
		else 
		{
			$this->view->form = $form;
		}
		
	}
	
}