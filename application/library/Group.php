<?php
/*
 * Group
 */

class Group extends Abstraction
{
	//Eigenschaften
	
	protected $_id;
	protected $_title;
	protected $_description;
	protected $_open;
	protected $_founder_id;
	protected $_date_add;
	protected $_date_edit;
	protected $_picture_id;
	protected $_language;
	
	protected $_dataFetched = false;
	protected $_dataRest;
	protected $_needles = array(
								"id",
								"title",
								"description",
								"open",
								"founder_id",
								"date_add",
								"date_edit",
								"picture_id",
								"language"								
						);
	/**
	 * GruppenModel-Objekt
	 *
	 * @var GruppenModel
	 */
	protected $_groupModel;
	/**
	 * GruppenThreadModel-Objekt
	 *
	 * @var GruppenThreadModel
	 */
	protected $_groupThreadsModel;
	/**
	 * GroupMembers-Objekt
	 *
	 * @var GroupMembers
	 */
	protected $_groupMembersModel;					
						
	public function __construct($group_id = null, $data = null)
	{	
		if($group_id != null)
			$this->setGroupId($group_id);
		if($data != null)
		{
			$this->_setData($data);
		}
	}
	/**
	 * Setzt die Gruppen-ID
	 *
	 * @param INT $group_id
	 */
	public function setGroupId($group_id)
	{
		$this->_clean();
		$this->_id = $group_id;
	}
	/**
	 * Holt die Gruppen-Daten zum Suchobjekt
	 * 
	 * @param Int Group-ID
	 * 
	 * @return Object|boolean Wenn die Gruppe gefunden wurde, dann wird das Objekt zurückgegeben, ansonsten FALSE
	 */
	public function getGroup()
	{
		$group = $this->_getGroupModel()->find($this->getGroupId())->current();
		if($group)
		{
			$this->_setData($group->toArray());
			return $this;
		}
		else
		{
			//Gruppe nicht gefunden
			$this->_getMessageBox()->setMessage("MSG_GROUP_001", $needle);
			return false;
		}
	}
	/**
	 * Setzt die Objekt-Variablen
	 *
	 * @param ARRAY $data
	 */
	protected function _setData($data)
	{
		foreach ($this->_needles as $needle)
		{
			if(isset($data[$needle]))
			{
				$varname = "_".$needle;
				$this->$varname = $data[$needle];
				
				unset($data[$needle]);
			}
		}
		if(!empty($data))
			$this->_dataRest = $data;
			
		$this->_dataFetched = true;
	}
	/**
	 * Erstellt eine neue Gruppe, ohne Transaction
	 *
	 * @param ARRAY $data
	 * @param BOOLEAN Soll der Founder als Member hinzugefügt werden?
	 * @return BOOLEAN
	 */
	public function createGroup($data, $setMember = true)
	{
		$this->_getMessageBox()->clear();
    		
		if($data = $this->validateData($data, "add"))
		{	
			if(empty($data["language"]))
				$data["language"] = NULL;
			
			$data_i = array("founder_id" => $data["founder_id"],
							"title" => $data["title"],
							"description" => $data["description"],
							"open" => $data["open"],
							"language" => $data["language"]					
			);
			
			//einfügen
			$group_id = $this->_getGroupModel()->insert($data_i);
			
			if($group_id){
				
				if($setMember)
				{
					$groupmember = new Group_Member($group_id, $data_i["founder_id"]);
					$groupmember->setMember("moderator"); //Moderator
				}
				$this->_getMessageBox()->setMessage("MSG_GROUP_003", $data["title"]);
				$this->setGroupId($group_id);
				$this->getGroup();
				return true;
			}
			else
			{
				throw new Zend-Exception("Ein Fehler ist aufgetreten. Die Gruppe konnte nicht angelegt werden.");
			}
			
    			
		}
		else
			return false;			
	}
	/**
	 * Erstellt eine Gruppe, sichert es mit Transaction ab
	 *
	 * @param ARRAY $data
	 * @return BOOLEAN
	 */
	public function createGroupWithTransaction($data)
	{
		Zend_Registry::get('Zend_Db')->beginTransaction();
		
			try{
				if($group = $this->createGroup($data))
					Zend_Registry::get('Zend_Db')->commit();
				return $group;
    		}catch(Zend_Exception $e){
    			Zend_Registry::get('Zend_Db')->rollBack();
		    	throw new Zend_Exception($e->getMessage(). "\n" .  $e->getTraceAsString());
    		}
    	
	}
	/**
	 * Prüft und speicher die geänderten Daten
	 *
	 * @param ARRAY $data
	 * @return INT|BOOLEAN
	 */
	public function editGroup($data)
	{
		$this->_getMessageBox()->clear();
		
		if($data = $this->validateData($data, "edit"))
		{
			$this->_init(); //Object muss geladen sein
			
			if(!$this->_dataFetched)
				return false;
				
			$this->_setData($data);
			
			$update = $this->save();
			
			return $update;
		}
		else
			return false;			
	}
	/**
	 * Speichert die aktuellen Objektwerte
	 *
	 * @return INT|BOOLEAN
	 */
	public function save()
	{
		//Basisdaten
		$row = $this->_getGroupModel()->find($this->getGroupId())->current();	
		if(!$row)
		{
			$this->_getMessageBox()->setMessage("MSG_GROUP_001", $this->getGroupId());
			return false;
		}
		//neue Daten setzen
		foreach ($this->_needles as $needle)
		{
			if(isset($row->$needle))
			{
				$varname = "_".$needle;
				$row->$needle = $this->$varname;
			}
		}
		//updaten
		$update = $row->save();
		
		if($update)
			$this->_getMessageBox()->setMessage("MSG_GROUP_002");
		else
			$this->_getMessageBox()->setMessage("MSG_GROUP_004");
			
		return $update;
	}
	/**
	 * Löscht die aktuelle Gruppe
	 *
	 * @return BOOLEAN
	 */
	public function delete()
	{
		$row = $this->_getGroupModel()->find($this->getId())->current();
		if($row)
		{
			$row->delete();
			$this->_getMessageBox()->setMessage("MSG_GROUP_005");
			$this->_clean();
			return true;
		}
		else 
		{
			$this->_getMessageBox()->setMessage("MSG_GROUP_001", $this->getGroupId());
			return false;
		}	
	}
	/**
	 * Prüft die Eingabe-Daten
	 *
	 * @param ARRAY $data
	 * @param STRING $modus add oder edit
	 * @return ARRAY|FALSE
	 */
	protected function validateData($data, $modus)
	{

		//Filter_Input starten
		$input = new Zend_Filter_Input($this->_getInputFilters(), $this->_getInputValidators($modus));
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
	 * Erstellt die Validators
	 *
	 * @param STRING $modus add or edit
	 * @return ARRAY
	 */
	protected function _getInputValidators($modus)
	{
		//Validators
 		if($modus == "add")
 		{
 			$validators = array(
			    'title' => array(new Zend_Validate_StringLength(2,70), 'presence' => 'required'),
				'description' => array(new Zend_Validate_StringLength(2,300), 'presence' => 'required'),
				'open' => array(new Validate_GroupOpenStatus(), 'presence' => 'required'),
				'founder_id' =>  array(new Validate_UserId(), 'presence' => 'required'),
 				'language' => array(new Validate_Language(), 'presence' => 'required', 'allowEmpty' => true) 			
            );
 		}
 		else
 		{
 			$validators = array(
			    'title' => array(new Zend_Validate_StringLength(2,70), 'presence' => 'optional'),
				'description' => array(new Zend_Validate_StringLength(2,300), 'presence' => 'optional'),
				'open' => array(new Validate_GroupOpenStatus(), 'presence' => 'optional'),
				'founder_id' =>  array(new Validate_UserId(), 'presence' => 'optional'),
 			 	'language' => array(new Validate_Language(), 'presence' => 'required', 'allowEmpty' => true) 			
 			
            );	 		
 		}
 		return $validators;
	}
	/**
	 * Erstellt einen neuen Thread
	 *
	 * @param ARRAY $data
	 * @return Group_Thread
	 */
	public function createThread($data)
	{
		$thread = new Group_Thread();
		if(!isset($data["group_id"]))
			$data["group_id"] = $this->getGroupId();
			
		$thread->createThread($data);
		return $thread;

	}
	/**
	 * Erstellt die Filter
	 *
	 * @return ARRAY
	 */
	protected function _getInputFilters()
	{
		//Filters
		$filters = array('*' => array('StringTrim','StripTags')	);
		return $filters;
	}
	/**
	 * Gibt letzte Antworten zurück
	 *
	 * @param INT $anzahl
	 * @return ARRAY|FALSE
	 */
	public function getLastReplys($anzahl = 10)
	{
		//@TODO Set benutzen
		$table = new GruppenThreadRepliesModel();
		$select = $table->select()->setIntegrityCheck(false);
		
		$select			->from(array("rly" => "gruppen_thread_replies"))
						->join(array("thd" => "gruppen_thread"), "thd.thread_id = rly.thread_id")
						->where("thd.group_id = ?", $this->getGroupId())
						->order("rly.date_add DESC")
						->limit($anzahl);
		$rows = $table->fetchAll($select);
		if(count($rows) > 0)
			return $rows->toArray();
		else
			return false;
	}
	/**
	 * Gibt eine Liste der neusten Gruppenmitglieder zurück
	 * 
	 * @param INT $anzahl
	 *
	 * @return ARRAY|FALSE
	 */
	public function getLastUsers($anzahl)
	{
		//@TODO Set benutzen
		$select = $this->_getGroupMembersModel()->select()->where("group_id = ?", $this->getGroupId())
								->order("date_join DESC")
								->limit($anzahl);
		$rows = $this->_getGroupMembersModel()->fetchAll($select);
		if(count($rows) > 0)
			return $rows->toArray();
		else
			return false;
	}
	/**
	 * Holt die letzten Antworten gruppiert nach Threads
	 *
	 * @param INT $anzahl1 Anzahl der Threads
	 * @param INT $anzahl2 Anzahl der jeweiligen Antworten pro Thread
	 * @return ARRAY|FALSE
	 */
	public function getLastRepliesGroupedByThread($anzahl1 = 5, $anzahl2 = 3)
	{
		// @TODO ReplySet benutzen

		$select = $this->_getGroupThreadModel()->select()->setIntegrityCheck(false);
		
		$select	->from(array("thd" => "gruppen_thread_replies"), array("maximum" => "MAX(rly.date_add)"))
				->join(array("rly" => "gruppen_thread_replies"), "thd.thread_id = rly.thread_id")
				->where("group_id = ?", $this->getGroupId())
				->group("thread_id")
				->order("maximum DESC")
				->limit($anzahl1);

		
		$rows = $table->fetchAll($select);
		
		if(count($rows) > 0)
		{
			$rlytbl = new GruppenThreadRepliesModel();
			$returnvalue = array();
					
			foreach($rows as $row)
			{
				//Einzelne Replies holen
				$select = $rlytbl->select()->where("thread_id = ?", $row->thread_id)
									->order("date_add DESC")
									->limit($anzahl2);
				$rlyrows = $rlytbl->fetchAll($select);
				if(count($rlyrows) > 0)
				{
					$tmp = $row->toArray();
					$tmp["replies"] = $rlyrow->toArray();
					$returnvalue[] = $tmp;
				}
			}
			
			return $returnvalue;
		}
		else
			return false;
	}
	/**
	 * Gibt die zuletzt erstellten Threads zurück
	 *
	 * @param INT $anzahl
	 * @return ARRAY|FALSE
	 */
	public function getLastThreads($anzahl = 5)
	{
		// @TODO Thread_Set benutzen
		$table = new GruppenThreadModel();
		$select = $table->select()->where("group_id = ?", $this->getGroupId())
								->oder("date_add DESC")
								->limit($anzahl);
		$rows = $table->fetchAll($select);
		if(count($rows) > 0)
			return $rows->toArray();
		else
			return false;
	}
	
	public function getGroupId()
	{
		return $this->getId();
	}
	public function getId()
	{
		$this->_isInit();
		return $this->_id;
	}
	public function getTitle()
	{
		$this->_init();
		return $this->_title;
	}
	public function getDescription()
	{
		$this->_init();
		return $this->_description;
	}
	public function getDateAdd()
	{
		$this->_init();
		return $this->_date_add;
	}
	public function getDateEdit()
	{
		$this->_init();
		return $this->_date_edit;
	}	
	public function getLanguage()
	{
		$this->_init();
		return $this->_language;
	}
	public function getOpenStatus()
	{
		$this->_init();
		return $this->_open;
	}
	/**
	 * Setzt alle Objekt-Variablen auf NULL und setzt dataFetched auf false
	 *
	 */
	protected function _clean()
	{
		foreach ($this->_needles as $needle)
		{
			$varname = "_".$needle;
			$this->$varname = null;
		}
		$this->_dataFetched = false;
	}
	/**
	 * Pürft ob Objekt richtig initialisiert
	 *
	 */
	protected function _isInit()
	{
		if($this->_id === null || !($this->_id > 0))
			throw new Zend_Exception("Objekt noch nicht initialisiert");
	}
	/**
	 * Initialisiert das Objekt
	 *
	 */
	protected function _init()
	{
		if($this->_dataFetched == false)
			$this->getGroup();
	}	
	
	/**
	 * GruppenModel
	 *
	 * @return GruppenModel
	 */
	protected function _getGroupModel()
	{
		if(!($this->_groupModel instanceof GruppenModel))
			$this->_groupModel = new GruppenModel();
		return $this->_groupModel;
	}
	/**
	 * GruppenThreadModel
	 *
	 * @return GruppenThreadModel
	 */
	protected function _getGroupThreadModel()
	{
		if(!($this->_groupThreadsModel instanceof GruppenThreadModel))
			$this->_groupThreadsModel = new GruppenThreadModel();
		return $this->_groupThreadsModel;
	}
	/**
	 * GroupMembers
	 *
	 * @return GroupMembers
	 */
	protected function _getGroupMembersModel()
	{
		if(!($this->_groupMembersModel instanceof GroupMembers))
			$this->_groupMembersModel = new GroupMembers();
		return $this->_groupMembersModel;
	}
	
}