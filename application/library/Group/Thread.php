<?php
/*
 * Group_Thread
 */

class Group_Thread extends Abstraction
{
	protected $_thread_id;
	protected $_title;
	protected $_founder_id;
	protected $_type;
	protected $_date_add;
	protected $_date_edit;
	protected $_group_id;
	protected $_language;
	
	protected $_numberReplies = NULL;
	
	protected $_ThreadsModel; //object
	
	protected $_dataFetched = false;
	
	protected $_needles = array(
							"thread_id",
							"title",
							"founder_id",
							"type",
							"date_add",
							"date_edit",
							"group_id",
							"language"
	);


	public function __construct($thread_id = null, $data = null)
	{				
		if($thread_id)
			$this->setThreadId($thread_id);
		if($data)
			$this->_setData($data);
	}
	public function setThreadId($thread_id)
	{
		$this->_clean();
		$this->_thread_id = $thread_id;
	}
	protected function _clean()
	{
		foreach($this->_needles as $needle)
		{
			$varname = "_".$needle;
			$this->$varname = NULL;
		}
		$this->_dataFetched = false;	
	}
	/**
	 * Holt die Thread-Daten zum Suchobjekt
	 * 
	 * @param Int Thread-ID
	 * 
	 * @return Object|boolean Wenn der Thread gefunden wurde, dann wird das Objekt zurückgegeben, ansonsten FALSE
	 */
	public function getThread()
	{
		$this->_isInit();
		
		$thread = $this->_getThreadsModel()->find($this->getThreadId())->current();
		if($thread)
		{
			$this->_setData($thread->toArray());
			return $this;
		}
		else
		{
			//Thread nicht gefunden
			$this->_getMessageBox()->setMessage("MSG_THREAD_001", $needle);
			return false;
		}
	}
	protected function _setData($data)
	{
		foreach($this->_needles as $needle)
		{
			if(isset($data[$needle]))
			{
				$varname = "_".$needle;
				$this->$varname = $data[$needle];
			}
		}
		$this->_dataFetched = true;
	}
	/**
	 * Erstellt eine neuen Thread
	 *
	 * @param ARRAY $data
	 * @param BOOLEAN Soll der Founder als GroupMember hinzugefügt werden?
	 * @param ARRAY replyData
	 * @return Group_Thread
	 */
	public function createThread($data, $setMember=true, $replyData = array())
	{
		$this->_getMessageBox()->clear();
	
		if($data = $this->validateData($data, "add"))
		{
			if(empty($data["language"]))
				$data["language"] = NULL;
				
			$data_i = array("founder_id" => $data["founder_id"], 
							"title" => $data["title"], 
							"group_id" => $data["group_id"],
							"type" => $data["type"],
							"language" => $data["language"]
					);
			//einfügen
			$insert = $this->_getThreadsModel()->insert($data_i);

			if($insert){
				if($setMember)
				{
					$groupmember = new Group_Member($data["group_id"], $data["founder_id"]);
					$groupmember->setMember("member");
				}
				//Thread wurde erstellt
				$this->setThreadId($insert);
				
				//Erste Reply einfügen
				if(!empty($replyData))
				{
					if(!$this->createReply($replyData))
					{
						//Reply nicht erstellt
						return false;
					}
				}
				$this->getThread();
				
				$this->_getMessageBox()->setMessage("MSG_THREAD_004");
				return true;
			}
			else
			{
				throw new Zend_Exception("Das Thema konnte nicht angelegt werden.");
			}				
		}
		else{
			return false;
		}			
	}
	/**
	 * Erstellt eine Thread, sichert es mit Transaction ab
	 *
	 * @param ARRAY $data
	 * @param BOOLEAN setMember
	 * @param ARRAY replyData
	 * @return BOOLEAN
	 */
	public function createThreadWithTransaction($data, $setMember=true, $replyData = array())
	{
		Zend_Registry::get('Zend_Db')->beginTransaction();
		
			try{
				if($thread = $this->createThread($data, $setMember, $replyData))
					Zend_Registry::get('Zend_Db')->commit();
				else 
					Zend_Registry::get('Zend_Db')->rollBack();
				return $thread;
    		}catch(Zend_Exception $e){
    			Zend_Registry::get('Zend_Db')->rollBack();
		    	throw new Zend_Exception($e->getMessage(). "\n" .  $e->getTraceAsString());
    		}
    	
	}
	/**
	 * Speichert die Änderungen
	 *
	 * @param ARRAY $data
	 * @return BOOLEAN
	 */
	public function editThread($data)
	{
		if($data = $this->validateData($data, "edit"))
		{		
			//erstmal Objectdaten holen
			$this->_init();
			if(!$this->_dataFetched)
				return false;
				
			$this->_setData($data);
			
			if($this->save())
			{
				//Änderungen gespeichert
				$this->_getMessageBox()->setMessage("MSG_THREAD_002");
			}
			else 
			{
				//Keine Änderungen vorgenommen
				$this->_getMessageBox()->setMessage("MSG_THREAD_003");
			}
			
			return true;			
		}
		else{
			return false;
		}		
	}
	/**
	 * Speichert die aktuellen Daten des Objects
	 *
	 * @return BOOLEAN
	 */
	public function save()
	{
		$this->_init();
		
		$row = $this->_getThreadsModel()->find($this->getThreadId())->current();
		if(!$row)
		{
			$this->_getMessageBox()->setMessage("MSG_THREAD_001", $this->getThreadId());
			return false;
		}
		foreach($this->_needles as $needle)
		{
			if(isset($row->$needle))
			{
				$varname = "_".$needle;
				$row->$needle = $this->$varname;
			}
		}
		$save = $row->save();
		return $save;
	}
	protected function validateData($data, $modus)
	{
		//Filters
		$filters = array('*' => array('StringTrim','StripTags')	);
 		//Validators
 		if($modus == "add")
 		{
 			$validators = array(
			    'title' => array(new Zend_Validate_StringLength(2,70), 'presence' => 'required', 'allowEmpty' => true),
				'group_id' =>  array(new Validate_GroupId(), 'presence' => 'required'),
				'founder_id' =>  array(new Validate_UserId(), 'presence' => 'required'),
 				'type' =>  array(new Validate_GroupThreadType(), 'presence' => 'required'),
            	'language' =>  array(new Validate_Language(), 'presence' => 'required', 'allowEmpty' => true),
 				);
 		}
 		else
 		{
 			$validators = array(
			    'title' => array(new Zend_Validate_StringLength(2,70), 'presence' => 'optional'),
				'group_id' =>  array(new Validate_GroupId(), 'presence' => 'optional'),
				'language' =>  array(new Validate_Language(), 'presence' => 'required', 'allowEmpty' => true),
				'founder_id' =>  array(new Validate_UserId(), 'presence' => 'optional'),
 				'type' =>  array(new Validate_GroupThreadType(), 'presence' => 'optional')
            );	 		
 		}			
		//Filter_Input starten
		$input = new Zend_Filter_Input($filters, $validators);
		//Daten laden
		$input->setData($data);
		if ($input->isValid())  //Prüfen
		{
			return $input->getEscaped();
		}
		else
		{
			$this->_getMessageBox()->setMessagesDirect($input->getMessages());
			return false;
		}
		
	}
	/**
	 * Gibt die Antworten zum Thread zurück
	 *
	 * @return Group_Thread_ReplySet
	 */
	public function getReplies()
	{
		$tbl = new GruppenThreadRepliesModel();
		$rows = $tbl->getReplies($this->getThreadId());
		
		return new Group_Thread_ReplySet($rows->toArray());
	}
	/**
	 * Gibt den PaginatorAdapter zurück
	 *
	 * @return Group_Thread_ReplyPaginatorAdapter
	 */
	public function getPaginatorAdapter()
	{
		return new Group_Thread_Reply_PaginatorAdapter($this->getThreadId());	
	}
	/**
	 * Gibt die Anzahl an Antworten zurück
	 *
	 * @return INT
	 */
	public function getNumberReplies()
	{
		if($this->_numberReplies === NULL)
		{
			$tbl = new GruppenThreadRepliesModel();
			$number = $tbl->getNumberReplies($this->getThreadId());
			if($number)
				$this->_numberReplies = $number;
			else
				$this->_numberReplies =  0;
		}
		return $this->_numberReplies;
	}
	/**
	 * Löscht den aktuellen Thread
	 *
	 * @return BOOLEAN
	 */
	public function delete()
	{
		//Thread löschen
		$row = $this->_getThreadsModel()->find($this->getThreadId())->current();
		if($row)
		{
			$row->delete();
			$this->_getMessageBox()->setMessage("MSG_THREAD_005");
			$this->_clean();
			return true;
		}
		else 
			$this->_getMessageBox()->setMessage("MSG_THREAD_001", $this->getThreadId());
		return false;
	}
	
	/**
	 * Erstellt eine Antwort zum Thread
	 *
	 * @param ARRAY $data
	 * @return BOOLEAN
	 */
	public function createReply($data)
	{
		//THREAD-ID setzen
		$data["thread_id"] = $this->getThreadId();
		
		$reply = new Group_Thread_Reply();
		$create = $reply->createReply($data);
		
		$this->_getMessageBox()->setMessagesDirect($reply->getMessages());
		
		return $create;
	}
	

	/**
	 * GruppenThreadsModel
	 *
	 * @return GruppenThreadsModel
	 */
	protected function _getThreadsModel()
	{
		if(!($this->_ThreadsModel instanceof GruppenThreadsModel))
			$this->_ThreadsModel = new GruppenThreadsModel();
		return $this->_ThreadsModel;
	}
	protected function _isInit()
	{
		if($this->_thread_id === null || !($this->_thread_id > 0))
			throw new Zend_Exception("Objekt noch nicht initialisiert");
	}
	protected function _init()
	{
		if($this->_dataFetched == false)
			$this->getThread($this->getThreadId());
	}
	public function starrThread()
	{
		//@TODO Beobachtungsfunktion
	}
	public function unstarrThread()
	{
	
	}
	
	/* ******************************************** */	
	
	/**
	 * Gibt die ThreadId zurück
	 *
	 */
	public function getThreadId()
	{
		$this->_isInit();
		return $this->_thread_id;
	}
	/**
	 * Gibt die zugehörige GroupId zurück
	 *
	 * @return INT
	 */
	public function getGroupId()
	{
		$this->_init();
		return $this->_group_id;
	}
/**
	 * Gibt den Titel zurück
	 *
	 * @return STRING
	 */
	public function getTitle()
	{
		$this->_init();
		return $this->_title;
	}
	/**
	 * Gibt die FounderId zurück
	 *
	 * @return INT
	 */
	public function getFounderId()
	{
		$this->_init();
		return $this->_founder_id;
	}
	/**
	 * 
	 *
	 * @return INT
	 */
	public function getDateAdd()
	{
		$this->_init();
		return $this->_date_add;
	}
	/**
	 *
	 * @return INT
	 */
	public function getDateEdit()
	{
		$this->_init();
		return $this->_date_edit;
	}

	/**
	 * 
	 * @return STRING Language
	 */
	public function getLanguage()
	{
		$this->_init();
		return $this->_language;
	}

}