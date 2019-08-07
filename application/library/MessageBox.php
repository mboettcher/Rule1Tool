<?php

class MessageBox implements Countable
{
	protected $_messages = array();
	protected $_library;
	
	protected $_count = 0;
	
	public function __construct()
	{
		//Erstelle eine neue Box
	}
	/**
	 * Gibt alle Nachrichten als Array zurück
	 *
	 * @return ARRAY
	 */
	public function getMessages()
	{
		return $this->_messages;
	}
	/**
	 * Nicht implementiert
	 *
	 */
	public function getLastMessage()
	{
		throw new Zend_Exception("Funktion nicht implementiert");
	}
	public function setMessage($msg_key, $value = null)
	{
		$msg = $this->_getLibrary()->getMessage($msg_key, $value);
		
		$this->_messages[] = $msg;
		
		return $this;
	}
	public function setMessages($msg_keys, $values)
	{
		throw new Zend_Exception("Funktion nicht implementiert");
	}
	
	public function setMessagesDirect($msgs)
	{
		$insert_msgs = array();
		
		foreach($msgs as $msg)
		{
			//print_r($msg);
			if(is_array($msg))
			{
				if(isset($msg["msg"]))
				{
					//Aus anderer MessageBox
					$insert_msgs[] = array("msg" => $msg["msg"], "level" => $msg["level"]);
					
				}
				else
				{
					// Zend_Input/Validate etc
					foreach ($msg as $key => $value)
					{
						$insert_msgs[] = array("msg" => $value, "level" => Zend_Registry::get("config")->general->messages->levels->WARNING->value);
					}
				}
					
			}
			
			else 
				$insert_msgs[] = array("msg" => $msg, "level" => Zend_Registry::get("config")->general->messages->levels->WARNING->value);
			
		}
		$this->_messages = array_merge_recursive($this->_messages,$insert_msgs);
	}
	public function clear()
	{
		$this->_messages = array();
		return;
	}
	protected function _getLibrary()
	{
		if($this->_library instanceof MessageBox_Library)
			return $this->_library;
		else
		{
			$this->_library = new MessageBox_Library();
			return $this->_library;
		}
	}
	
   /**
     * Returns the number of elements in the collection.
     *
     * Implements Countable::count()
     *
     * @return int
     */
    public function count()
    {
    	$this->_count = count($this->_messages);
        return $this->_count;
    }
}