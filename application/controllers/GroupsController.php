<?php

/**
 * GroupsController
 * 
 * @author
 * @version 
 */
	
require_once 'Zend/Controller/Action.php';

class GroupsController extends Zend_Controller_Action
{
    public function init()
    {
    	$contextSwitch = $this->_helper->getHelper('SwitchContext');
		$contextSwitch	->addActionContext('create-reply', 'json')
						->addActionContext('edit-reply', 'json')
						->addActionContext('show-reply', 'json')
						
						->addActionContext('delete-group', 'json')
						->addActionContext('join-group', 'json')
						->addActionContext('leave-group', 'json')
						
						->addActionContext('delete-thread', 'json')
						->initContext();	
    }	
    
	public function forumDispatchAction()
	{
		$this->view->headTitle('Forum');
	}


	/**
	 * The default action - show the home page
	 */
    public function indexAction() 
    {
        // TODO Auto-generated GroupsController::indexAction() default action
    }
    
    /*
     * Group-Funktionen
     */    
    public function showGroupAction()
    {
   		$group_id = $this->_request->getParam("GID");
		$group = new Group($group_id);
		$group = $group->getGroup();
		
		$this->view->messages = $group->getMessages();

   		$this->view->success = true;
   		$this->view->group = $group;

    }
    public function createGroupAction()
    {
    	//Form erstellen
    	$form = new Form_GroupInput();
    	$form->setAction($this->view->url(array("language" => Zend_Registry::get('Zend_Locale')->getLanguage()), "groups_group_create"));
    	
    	if ($this->getRequest()->isPost())
		{
			//Daten einfügen
			$form->isValid($this->_getAllParams());
			
			$group = new Group();
			$values = $form->getValues();
			$values["founder_id"] = Zend_Registry::get('UserObject')->getUserId();
			
			$group_create = $group->createGroupWithTransaction($values);
			
			$this->view->messages = $group->getMessages();
			
			if($group_create)
			{
				//Weiterleitung
			}
			else
			{
				$this->view->form = $form;	
			}
			
		}
		else 
		{	
			$this->view->form = $form;	
		}

    	
    }
    public function editGroupAction()
    {
	   	
    	$group_id = $this->_request->getParam("GID");
    	$group = new Group($group_id);
    	
    	if($group->getGroup())
    	{   
    		//Form erstellen
	    	$form = new Form_GroupInput();
	    	$form->setAction($this->view->url(array("language" => Zend_Registry::get('Zend_Locale')->getLanguage()), "groups_group_edit"));
	  
    		if ($this->getRequest()->isPost())
			{
				//Daten einfügen
				$form->isValid($this->_getAllParams());
				
				if($group->editGroup($form->getValues()))
				{
					//Weiterleitung
				}
				else
				{
					$this->view->form = $form;	
				}
			}
			else 
			{
				//Befüllen
				$form->populate(
									array(
											"title" => $group->getTitle(),
											"description" => $group->getDescription(),
											"language" => $group->getLanguage(),
											"open" => $group->getOpenStatus()
									)
					);
				$this->view->form = $form;	
			}
    	}
    	$this->view->messages = $group->getMessages();
    }
    public function deleteGroupAction()
    {
    	$group_id = $this->_request->getParam("GID");
		$group = new Group($group_id);
		
		$this->view->success = $group->delete();
		
		$this->view->messages = $group->getMessages();
    }
    public function joinGroupAction()
    {
    	$group_id = $this->_request->getParam("GID");
		
		$groupmember = new Group_Member($group_id, Zend_Registry::get("UserObject")->getUserId());
		
		$this->view->success = $groupmember->setMember("member");
		
		$this->view->messages = $groupmember->getMessages();
    }
    public function leaveGroupAction()
    {
    	$group_id = $this->_request->getParam("GID");
    	$user_id = $this->_request->getParam("UID");
		
		$groupmember = new Group_Member($group_id, $user_id);
		
		$this->view->success = $groupmember->delete();
		
		$this->view->messages = $groupmember->getMessages();
    }
    
    
    
    /*
     * Thread-Funktionen
     */
    public function showThreadAction()
    {
  		$thread_id = $this->_request->getParam("TID");
  		$page = $this->_getParam("page");
  		
		$thread = new Group_Thread($thread_id);
		$thread = $thread->getThread();
		
		$this->view->messages = $thread->getMessages();
			
    	if($thread)
    	{
    		$this->view->success = true;
    		$this->view->thread = $thread;
    		$paginator = new Zend_Paginator($thread->getPaginatorAdapter());
    		$paginator->setItemCountPerPage(50);
			$paginator->setCurrentPageNumber($page);
			$this->view->paginator = $paginator;
    	}
    	else 
    	{
    		$this->view->success = false;
    	}
    }
    public function createThreadAction()
    {
    	$group_id = $this->_request->getParam("GID");
    	//Form erstellen
    	$form = new Zend_Form();
    	$form->setAction($this->view->url(array("language" => Zend_Registry::get('Zend_Locale')->getLanguage(), "GID" => $group_id), "groups_thread_create"));
    	
    	$form->setMethod('post');
    	
        //$options = array('disableLoadDefaultDecorators' => true);
        $options = null;
        $title = new Zend_Form_Element_Text("title", $options);
		$text = new Zend_Form_Element_Textarea("text", $options);
        $submit = new Zend_Form_Element_Submit("submit", $options);
        
        $submit->setLabel("Thema erstellen");     
        
		$form->addElements(array($title, $text, $submit));
		
		
    	if ($this->getRequest()->isPost())
		{
			//Daten einfügen
			$form->isValid($this->_getAllParams());
			
			$thread = new Group_Thread();
			$values = $form->getValues();
			
			$threadData = array(
									"founder_id" => Zend_Registry::get('UserObject')->getUserId(),
									"title" => $values["title"],
									"type" => 1,
									"language" => "de",
									"group_id" => $group_id
							);
			$replyData = array(
								"text" => $values["text"],
								"writer_id" => Zend_Registry::get('UserObject')->getUserId()
				);
			
			$thread_create = $thread->createThreadWithTransaction($threadData, true, $replyData);
			
			$this->view->messages = $thread->getMessages();
			
			if($thread_create)
			{
				//Weiterleitung
			}
			else
			{
				$this->view->form = $form;	
			}
			
		}
		else 
		{	
			$this->view->form = $form;	
		}
    	
    }
    public function editThreadAction()
    {
    	$thread_id = $this->_request->getParam("TID");
    	//Form erstellen
    	$form = new Zend_Form();
    	$form->setAction($this->view->url(array("language" => Zend_Registry::get('Zend_Locale')->getLanguage(), "TID" => $thread_id), "groups_thread_edit"));
    	
    	$form->setMethod('post');
    	
        //$options = array('disableLoadDefaultDecorators' => true);
        $options = null;
        $title = new Zend_Form_Element_Text("title", $options);
		//$text = new Zend_Form_Element_Textarea("text", $options);
        $submit = new Zend_Form_Element_Submit("submit", $options);
        
        $submit->setLabel("Änderungen speichern");     
        
		$form->addElements(array($title, $submit));
		
		$thread = new Group_Thread($thread_id);
		
    	if ($this->getRequest()->isPost())
		{
			//Daten einfügen
			$form->isValid($this->_getAllParams());
			
			
			$values = $form->getValues();
			
			$threadData = array(
									//"founder_id" => Zend_Registry::get('UserObject')->getUserId(),
									"title" => $values["title"],
									//"type" => 1,
									"language" => "de"
									//"group_id" => $group_id
							);

			
			$thread_edit = $thread->editThread($threadData);
			
			$this->view->messages = $thread->getMessages();
			
			if($thread_edit)
			{
				//Weiterleitung
			}
			else
			{
				$this->view->form = $form;	
			}
			
		}
		else 
		{	
			$form->populate(array("title" => $thread->getTitle()));
			$this->view->form = $form;	
		}
    }
    public function deleteThreadAction()
    {
    	$thread_id = $this->_request->getParam("TID");
		$thread = new Group_Thread($thread_id);
		
		$this->view->success = $thread->delete();
		
		$this->view->messages = $thread->getMessages();
    }
    
    /*
     * Reply-Funktionen
     */
    /**
	*  Gibt eine einzelne Antwort anhand der Reply-ID zurück
	*/
    public function showReplyAction()
    {
    	$reply_id = $this->_request->getParam("RID");
		$reply = new Group_Thread_Reply($reply_id);
		$reply = $reply->getReply();
		
		$this->view->messages = $reply->getMessages();
			
    	if($reply)
    	{
    		$this->view->success = true;
    		$this->view->reply = $this->view->printComment($reply);
    	}
    	else 
    	{
    		$this->view->success = false;
    	}
    }
    public function createReplyAction()
    {
    	$params = $this->_request->getParams();
    	$data = array("writer_id" => Zend_Registry::get('UserObject')->getUserId(), 
									"text" => $params["text"], 
									"thread_id" => $params["thread_id"]);
    	$reply = new Group_Thread_Reply();
    	$reply_create = $reply->createReply($data);
    	
    	$this->view->success = $reply_create;
		$this->view->messages = $reply->getMessages();
			
    	if($reply_create)
    	{
    		$this->view->reply = $this->view->printComment($reply);
    	}
    }
    public function editReplyAction()
    {
    	$rid = $this->_getParam("RID");
    	$this->view->success = false; //basiswert
    	//FORM erstellen
    	$form = new Zend_Form();
    	$form->setAction($this->view->url(array("language" => Zend_Registry::get('Zend_Locale')->getLanguage(), "RID" => $rid), "groups_reply_edit"));
        $form->setMethod('post');
    	
        //$options = array('disableLoadDefaultDecorators' => true);
        $options = null;
		$text = new Zend_Form_Element_Textarea("text", $options);
        $submit = new Zend_Form_Element_Submit("submit", $options);
        
        $submit->setLabel("Änderung speichern");     
        
		$form->addElements(array($text, $submit));
    	
    	if ($this->getRequest()->isPost())
		{
			//Daten einfügen
			$form->isValid($this->_getAllParams());

			$reply = new Group_Thread_Reply($rid);
			
			$reply_edit = $reply->editReply($form->getValues());

			$this->view->success = $reply_edit;
			$this->view->messages = $reply->getMessages();
			
		}
		else 
		{
			//FORM füllen
			
			$reply = new Group_Thread_Reply($rid);
			
			$form->populate(array("text" => $reply->getText()));
			
			$this->view->messages = $reply->getMessages();
		}
		
		if(!$this->_request->isXmlHttpRequest()) // Wenn nicht Ajax
		{
			//FORM in View packen
			$this->view->form = $form;
		}
    } 
    public function deleteReplyAction()
    {
    	$reply_id = $this->_request->getParam("RID");
		$reply = new Group_Thread_Reply($reply_id);
		
		$this->view->success = $reply->delete();
		
		$this->view->messages = $reply->getMessages();
    }

    public function getLastGroupsActivities()
	{
		/* 
		 * Alle Groupen des Benutzer nach Replie date_add 
		 */
	}

}
