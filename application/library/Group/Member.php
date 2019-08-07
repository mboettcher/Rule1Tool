<?php
/*
 * Group_Member
 */

class Group_Member extends Abstraction 
{

	protected $_group_id;
	protected $_user_id;
	protected $_mtype_id;
	
	protected $_date_join;
	
	protected $_date_delete;
	protected $_delete_by;
	
	
	protected $_user;
	protected $_group;
	
	protected $_dataFetched = false;
	
	protected $_needles = array(
							"group_id",
							"user_id",
							"mtype_id",
							"date_join",
							"date_delete",
							"delete_by"
						);
	protected $_dataRest;
	
	public function __construct($group_id, $user_id)
	{
		$this->setGroupId($group_id);
		$this->setUserId($user_id);
	}
	public function setGroupId($group_id)
	{
		$this->_group_id = $group_id;
	}
	public function setUserId($user_id)
	{
		$this->_user_id = $user_id;
	}
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
	public function getMember()
	{
		$tbl = new GruppenMembersModel();
		$row = $tbl->find($this->getGroupId(), $this->getUserId())->current();
		if($row)
		{
			$this->_setData($row->toArray());

			return $this;
		}
		else
		{
			$this->_getMessageBox()->setMessage("MSG_GROUPMEMBER_001");
			return false;
		}
	}
	
	public function setMember($mtype = "member")
	{
		if($mtype == "moderator")
			$mtype_id = 1;
		else 
			$mtype_id = 2;
			
		$tbl = new GruppenMembersModel();
		
		//ACHTUNG: Im Model wird geprüft, ob User bereits Member bzw. ob deletedMember
		
		$data = array("group_id" => $this->getGroupId(),
					"user_id" => $this->getUserId(),
					"mtype_id" => $mtype_id
					);
					
		$insert = $tbl->insert($data);
		
		if($insert)
		{
			$this->_getMessageBox()->setMessage("MSG_GROUPMEMBER_003");
			return true;
		}
		else
		{
			$this->_getMessageBox()->setMessage("MSG_GROUPMEMBER_002");
			return false;
		}
	}
	/**
	 * Entfernt Nutzer aus Gruppe
	 *
	 * @return BOOLEAN
	 */
	public function delete()
	{
		$tbl = new GruppenMembersModel();
		$row = $tbl->find($this->getGroupId(), $this->getUserId())->current();
		if($row)
		{
			$row->delete();
			$this->_clean();
			$this->_getMessageBox()->setMessage("MSG_GROUPMEMBER_004");
			return true;
		}
		else 
		{
			$this->_getMessageBox()->setMessage("MSG_GROUPMEMBER_001");
			return false;
		}
		
	}
	
	/**
	 *
	 * @return INT
	 */
	public function getUserId()
	{
		$this->_isInit();
		return $this->_user_id;
	}
	/**
	 *
	 * @return INT
	 */
	public function getGroupId()
	{
		$this->_isInit();
		return $this->_group_id;
	}
	/**
	 * Gibt ein UserObject zurück
	 *
	 * @return User
	 */
	public function getUser()
	{
		if(!($this->_user instanceof User))
			$this->_user = new User($this->getUserId());
		return $this->_user;
	}
	/**
	 * Gibt ein GroupObject zurück
	 *
	 * @return Group
	 */
	public function getGroup()
	{
		if(!($this->_group instanceof Group))
			$this->_group = new Group($this->getGroupId());
		return $this->_group;
	}
	protected function _clean()
	{
		foreach ($this->_needles as $needle)
		{
			$varname = "_".$needle;
			$this->$varname = null;
		}
		$this->_dataFetched = false;
	}
	
	protected function _isInit()
	{
		if(!($this->_group_id > 0))
			throw new Zend_Exception("Objekt noch nicht initialisiert");
		if(!($this->_user_id > 0))
			throw new Zend_Exception("Objekt noch nicht initialisiert");
	}
	protected function _init()
	{
		if($this->_dataFetched == false)
			$this->getMember();
	}
}