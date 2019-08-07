<?php
class View_Helper_BaseUrlServer extends Zend_View_Helper_Abstract
{
	public function baseUrlServer()
	{
		return "http://".$_SERVER["SERVER_NAME"];
	}
}