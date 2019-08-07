<?php
class View_Helper_CapTop extends Zend_View_Helper_Abstract
{
	public function capTop($addClass = "")
	{
		return '<div class="cap top '.$addClass.'"><div class="left"></div><div class="right"></div></div>';
	}
}