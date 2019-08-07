<?php
class View_Helper_Image extends Zend_View_Helper_Abstract
{
	/**
	 * Gibt die HTML-Message-Box zurück
	 *
	 * @param STRING Bild Url
	 * @param STRING Alternativ Text
	 * @param STRING Title
	 * @param STRING Klasse
	 * 
	 * @return STRING HTML
	 */
	public function image($src, $alt = null, $title = null, $class = null, $border = 0, $width = null, $height = null)
	{
		if(!strstr($src, "http://"))
			$base = $this->view->baseUrlShort().'/public/images/';
		else 
			$base = "";
			
		if($width !== null)
			$cwidth = 'width="'.$width.'"';
		else
			$cwidth = "";
		if($height !== null)
			$cheight = 'height="'.$height.'"';	
		else
			$cheight = "";
			
		$box = '<img '.$cwidth.' '.$cheight.' src="'.$base.$src.'" alt="'.$alt.'" title="'.$title.'" class="'.$class.'" border="'.$border.'"/>';
		return $box;
	}
}