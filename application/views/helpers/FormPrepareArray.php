<?php
class View_Helper_FormPrepareArray extends Zend_View_Helper_Abstract
{
    /**
     * Erstellt eine form-Options-Liste anhand eines Arrays
     *
     * @param ARRAY $array
     * @param STRING $key_name
     * @param STRING $value_name
     * @return ARRAY
     */
	public function formPrepareArray($array, $key_name, $value_name)
	{
	    $options = array();
		foreach ($array as $el)
		{
		    $options[$el[$key_name]] = $el[$value_name];
		}
		return $options;
	}
}