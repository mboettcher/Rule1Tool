<?php
class View_Helper_BaseUrlShort extends Zend_View_Helper_Abstract
{
	public function baseUrlShort()
	{
		return "http://".$_SERVER["SERVER_NAME"].Zend_Registry::get('Zend_Controller_Front')->getBaseUrl();
	}
}