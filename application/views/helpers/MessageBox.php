<?php
class View_Helper_MessageBox extends Zend_View_Helper_Abstract
{
	/**
	 * Gibt die HTML-Message-Box zurück
	 *
	 * @param Object|ARRAY MessageBox oder MessageArray
	 * @param STRING|NULL Type der Box
	 * @return STRING HTML
	 */
	public function messageBox($mbox, $type = "input")
	{
		if(count($mbox) == 0) //Wenn keine Nachrichten vorhanden, dann auch nichts ausgeben
			return null;
		if($mbox instanceof MessageBox)
			$messages = $mbox->getMessages();
		elseif(is_array($mbox)) 
			$messages = $mbox;
		else 
			throw new Zend_Exception("Falsches Format der Messages");

		//Text generieren
		$text = "";
		$msg_level = 1000;
		foreach ($messages as $message)
		{
			if($text != "")
				$text .= "<br/>";
			$text .= $message["msg"];
			if($message["level"] < $msg_level)
				$msg_level = $message["level"];
		}
		foreach (Zend_Registry::get("config")->general->messages->levels as $level)
		{
			if($level->value == $msg_level)
				$img = $level->img;
		}
		
		$box = '<div class="messages '.$type.'">
				 
				 <table class="box">
				 	<tr>
				 	<td class="img">
				 		<img src="'.$this->view->baseUrlShort().'/public/images/'.$img.'" alt=""/>
				 	</td>
				 	<td class="text">
						'.$text.'
				 	</td>
				 	</tr>
				 </table>
				 
				</div>';
		return $box;
	}
}