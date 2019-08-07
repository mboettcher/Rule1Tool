<?php
class View_Helper_PrintNA extends Zend_View_Helper_Abstract
{
	public function printNA($value, $extBefore = null, $extAfter = null)
	{
		if($value == "" || $value === NULL)
			return "n/a";
		else
		{
			if($extBefore)
				$value = $extBefore.$value;
			if($extAfter)
				$value = $value.$extAfter;
			return $value;
		}	
	}
}