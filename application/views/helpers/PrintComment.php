<?php
class View_Helper_PrintComment extends Zend_View_Helper_Abstract
{
	/**
	 * Gibt eine KommentarBox zurück
	 *
	 * @param Group_Thread_Reply Reply-Objekt
	 * @return STRING
	 */
	public function printComment(Group_Thread_Reply $reply)
	{
		$return = "";
		$return .= "<div class='stockCommentsCommentBox' id='stockComments_".$reply->getReplyId()."'>";
		$return .= "<h4>".new Zend_Date($reply->getDateAdd(), null, Zend_Registry::get('Zend_Locale'))." | "
				.$this->view->link(
					$this->view->url(array("language" => Zend_Registry::get('Zend_Locale')->getLanguage(), 
									"username" => $reply->getWriter()->getNickname()), "user_profile"),  
					$reply->getWriter()->getNickname())					
				."</h4>";
		
		$return .= $this->view->printUserPicture($reply->getWriter(), "s");
			
		$return .= "<p>".$reply->getText()."</p>";	
		$return .="</div>";
		
		return $return;
	
	}
}