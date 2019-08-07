<?php
/*
 * Group_Thread_Reply
 */

class Group_Thread_Reply extends Abstraction
{
	//Eigenschaften
	
	protected $_reply_id;
	protected $_thread_id;
	protected $_thread;
	protected $_writer_id;
	protected $_writer = null;
	protected $_date_add;
	protected $_date_edit;
	protected $_text;
	
	protected $_dataRest = array();
	protected $_dataFetched = false;
	
	protected $_needles = array(
							"reply_id",
							"thread_id",
							"writer_id",
							"date_add",
							"date_edit",
							"text"
						);


	
	protected $_GruppenThreadRepliesModel; // GruppenThreadRepliesModel Object
					
	public function __construct($reply_id = null, $data = null)
	{				
		if($reply_id != null)
			$this->setReplyId($reply_id);
		if($data !== null)
			$this->_setData($data);
	}
	public function setReplyId($reply_id)
	{
		$this->_clean();
		$this->_reply_id = $reply_id;
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
	protected function _setData($data)
	{
		foreach($this->_needles as $needle)
		{
			if(isset($data[$needle]))
			{
				$varname = "_".$needle;
				$this->$varname = $data[$needle];
				unset($data[$needle]);
			}
		}
		// Alles andere wird einfach gespeichert
		$this->_dataRest = $data;
		$this->_dataFetched = true;
	}
	/**
	 * Holt die Antwort-Daten
	 * 
	 * @return Object|boolean Wenn die Antwort gefunden wurde, dann wird das Objekt zurückgegeben, ansonsten FALSE
	 */
	public function getReply()
	{
		$reply = $this->_getRepliesModel()->find($this->getReplyId())->current();
		if($reply)
		{
			$this->_setData($reply->toArray());
			return $this;
		}
		else
		{
			//Reply nicht gefunden
			$this->_getMessageBox()->setMessage("MSG_REPLY_001", $this->getReplyId());
			return false;
		}
	}
	/**
	 * Erstellt eine neue Antwort
	 *
	 * @param ARRAY $data
	 * @param BOOLEAN Soll der Writer als GroupMember hinzugefügt werden?
	 * @return BOOLEAN
	 */
	public function createReply($data, $setMember=true)
	{
		$this->_getMessageBox()->clear();

		if($data = $this->validateData($data, "add"))
		{
    		$data = array("writer_id" => $data["writer_id"], 
    						"text" => $data["text"], 
    						"thread_id" => $data["thread_id"]
    				);
    		//einfügen
    		$insert = $this->_getRepliesModel()->insert($data);
    		if($insert){
    			$this->setReplyId($insert);
    			$this->getReply();
    			
    			if($setMember)
				{
					$groupmember = new Group_Member($this->getThread()->getGroupId(), $data["writer_id"]);
					$groupmember->setMember("member");
				}
				
				$this->_getMessageBox()->setMessage("MSG_REPLY_002");
				
    			return true;
    		}
    		else
    		{
    			throw new Zend_Exception("Der Beitrag konnte nicht angelegt werden.");
    		}	
		}
		else
			return false;	

	}
	/**
	 * Speichert die Änderungen
	 *
	 * @param ARRAY $data
	 * @return BOOLEAN
	 */
	public function editReply($data)
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
				$this->_getMessageBox()->setMessage("MSG_REPLY_002");
			}
			else 
			{
				//Keine Änderungen vorgenommen
				$this->_getMessageBox()->setMessage("MSG_REPLY_003");
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
		
		$row = $this->_getRepliesModel()->find($this->getReplyId())->current();
		if(!$row)
		{
			$this->_getMessageBox()->setMessage("MSG_REPLY_001", $this->getReplyId());
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
			    'text' => array(new Zend_Validate_StringLength(2,1000), 'presence' => 'required'),
				'thread_id' =>  array(new Validate_ThreadId(), 'presence' => 'required'),
				'writer_id' =>  array(new Validate_UserId(), 'presence' => 'required')
            );
 		}
 		else
 		{
 			$validators = array(
			    'text' => array(new Zend_Validate_StringLength(2,1000), 'presence' => 'optional'),
				'thread_id' =>  array(new Validate_ThreadId(), 'presence' => 'optional'),
				'writer_id' =>  array(new Validate_UserId(), 'presence' => 'optional')
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
	 * Löscht eine Antwort, ggf. auch den Thread dazu
	 *
	 */
	public function delete()
	{
		Zend_Registry::get('Zend_Db')->beginTransaction();

		try {
			//reply holen
			$reply = $this->_getRepliesModel()->find($this->getReplyId())->current();
			if(!$reply)
			{
				$this->_getMessageBox()->setMessage("MSG_REPLY_001", $this->getReplyId());
				return false;
			}
			
			//erstmal threadID holen, weil sonst ist sie weg
			$thread_id = $reply->thread_id;
			
			 //Reply löschen
			 $reply->delete();
			 
			//ggf. Thread löschen
			$thread = new Group_Thread($thread_id);
			if($thread->getNumberReplies() == 0)
				$thread->delete();
			
			$this->_clean(); //sauber machen ;)
			
			$this->_getMessageBox()->setMessage("MSG_REPLY_004");
			$this->_getMessageBox()->setMessagesDirect($thread->getMessages());
				
			Zend_Registry::get('Zend_Db')->commit();
			return true;
    	} catch (Exception $e) {
    	    // Wenn irgendeine der Abfragen fehlgeschlagen ist, wirf eine Ausnahme, wir wollen die komplette Transaktion
    	    // zurücknehmen, alle durch die Transaktion gemachten Änderungen wieder entfernen auch die erfolgreichen.
    	    // So werden alle Änderungen auf einmal übermittelt oder keine.
    	    Zend_Registry::get('Zend_Db')->rollBack();
    	    throw new Zend_Exception($e->getMessage(). "\n" .  $e->getTraceAsString());
    	}	
		
	}
	/**
	 * Gibt GruppenThreadRepliesModel zurück
	 *
	 * @return GruppenThreadRepliesModel
	 */
	protected function _getRepliesModel()
	{
		if(!($this->_GruppenThreadRepliesModel instanceof GruppenThreadRepliesModel))
			$this->_GruppenThreadRepliesModel = new GruppenThreadRepliesModel();
		return $this->_GruppenThreadRepliesModel;

	}
	protected function _isInit()
	{
		if($this->_reply_id === null || !($this->_reply_id > 0))
			throw new Zend_Exception("Objekt noch nicht initialisiert");
	}
	protected function _init()
	{
		if($this->_dataFetched == false)
			$this->getReply();
	}
	/**
	 *
	 * @return INT
	 */
	public function getReplyId()
	{
		$this->_isInit();
		return $this->_reply_id;
	}
	/**
	 *
	 * @return INT
	 */
	public function getThreadId()
	{
		$this->_init();
		return $this->_thread_id;
	}
	/**
	 * Gibt das ThreadObjekt zurück
	 *
	 * @return Group_Thread
	 */
	public function getThread()
	{
		if(!($this->_thread instanceof Group_Thread))
			$this->_thread = new Group_Thread($this->getThreadId());
		
		return $this->_thread;
	}
	/**
	 *
	 * @return INT
	 */
	public function getWriterId()
	{
		$this->_init();
		return $this->_writer_id;
	}
	/**
	 * Gibt das User-Objekt zurück
	 *
	 * @return User
	 */
	public function getWriter()
	{
		if(!($this->_writer instanceof User))
			$this->_writer = new User($this->getWriterId(), $this->_dataRest);
		
		return $this->_writer;
	}
	/**
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
	 * @return STRING
	 */
	public function getText()
	{
		$this->_init();
		return $this->_text;
	}
}