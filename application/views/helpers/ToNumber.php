<?php
class View_Helper_ToNumber extends Zend_View_Helper_Abstract
{
	public function toNumber($value, $precision = 2, $setPlus = false)
	{
		$_toNumberOptions = array('locale' => Zend_Registry::get("Zend_Locale"), 'precision' => $precision);
		
		$float = Zend_Locale_Format::toFloat($value, $_toNumberOptions);
		
		if($setPlus && $value > 0)
			return "+".$float;
		else
			return $float;
	}
}