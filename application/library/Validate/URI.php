<?php
class Validate_URI extends Zend_Validate_Abstract
{
    const NO_URI = 'noURI';

    protected $_messageTemplates = array(
        self::NO_URI => "'%value%' does not appear to be a URI"
    );

    public function isValid($value)
    {
    	if(Zend_Uri::check($value))
    	{
    		return true;
    	}
    	else
    	{
    		$this->_error(self::NO_URI);
    	}
    }
}  