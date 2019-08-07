<?php
class View_Helper_Link extends Zend_View_Helper_Abstract
{
	/**
	 * Gibt ein HTML-Link zurück
	 *
	 * @param unknown_type $url
	 * @param unknown_type $value
	 * @param unknown_type $class
	 * @param unknown_type $title
	 * @param unknown_type $target
	 * @return STRING
	 */
	public function link($url, $value, $class = null, $title = null, $target = null, $id = null)
	{
		if(!strstr($url, "http://"))
			$base = $this->view->baseUrlServer();
		else 
			$base = "";
		if($target == null)
			$target = "_self";
		return '<a href="'.$base.$url.'" id="'.$id.'" class="'.$class.'" title="'.$title.'" target="'.$target.'">'.$value.'</a>';		
	}
}