<?php
class Validate_IsTrue extends Zend_Validate_Abstract
{
    const NOT_TRUE = 'notTrue';

    protected $_messageTemplates = array(
        self::NOT_TRUE => "Ist nicht gültig"
    );

    public function isValid($value)
    {
    	if($value)
    	{
    		return true;
    	}
    	else
    	{
    		$this->_error(self::NOT_TRUE);
    	}
    }
}  