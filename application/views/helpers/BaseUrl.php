<?php
class View_Helper_BaseUrl extends Zend_View_Helper_Abstract
{
	public function baseUrl()
	{
		return "http://".$_SERVER["SERVER_NAME"].Zend_Registry::get('Zend_Controller_Front')->getBaseUrl()."/".Zend_Registry::get('Zend_Locale')->getLanguage();
	}
}