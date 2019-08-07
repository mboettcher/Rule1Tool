<?php
require_once 'Zend/Validate/Abstract.php';

class Validate_Language extends Zend_Validate_Abstract
{
    const MSG_NOTSUPPORTED = 'languageNotSupported';

    protected $_messageTemplates = array(
        self::MSG_NOTSUPPORTED 	=> "Die angegebene Sprache '%value%' existiert nicht",
    );
    /**
     * Defined by Zend_Validate_Interface
     *
     * Returns true if the language is support by rule1tool
     *
     * @param  string $value language
     * @return boolean
     */
    public function isValid($value)
    {
        $this->_setValue($value);
        $languages = Zend_Registry::get('config')->general->language->toArray();
		
        if($value == NULL)
        	return true;
        
    	if(isset($languages[$value]))
    	{
			return true;
    	}
    	else
		{
			$this->_error(self::MSG_NOTSUPPORTED);
			return false;
		} 

    }
}

