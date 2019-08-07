<?php
class Validate_LocaleFloat extends Zend_Validate_Abstract
{
    const NOT_FLOAT = 'notFloat';

    protected $_messageTemplates = array(
        self::NOT_FLOAT => "'%value%' does not appear to be a float"
    );

    public function isValid($value)
    {
        $valueString = (string) $value;

        $this->_setValue($valueString);

        $locale = Zend_Registry::get('Zend_Locale');

        if(!Zend_Locale_Format::isFloat($value, array('locale' => $locale))) {
            $this->_error();
            return false;
        }

        return true;
    }
}  