<?php
class View_Helper_MessagesToString extends Zend_View_Helper_Abstract
{
	/**
	 * Gibt alles Messages als ein String zurück
	 *
	 * @param ARRAY Messages
	 * @return STRING
	 */
	public function messagesToString($msgs)
	{
		$messages = "";
   		foreach ($msgs  as $msg)
   		{
   			if($messages != "")
   				$messages .= " ";
   			$messages .= $msg["msg"]; 
   		}
   		
   		return $messages;
	}
}