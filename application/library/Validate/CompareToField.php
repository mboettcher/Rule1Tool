<?php
class Validate_CompareToField extends Zend_Validate_Abstract
{
	const EQUAL         = 'equal';
    const EQUAL_STRICT  = 'equal_strict';

    protected $_messageTemplates = array (
        self::EQUAL         => "'%value%' is not equal '%compare%'",
        self::EQUAL_STRICT  => "'%value%' is not strict equal '%compare%'.",
    );

    protected $_messageVariables = array(
        'compare' => '_compareValue'
    );

    protected $_compareValue = null;
    protected $_strict = true;

    public function __construct($strict = true) {
        $this->_strict = (bool) $strict;
    }

    public function isValid($value) {
        $keys = array_keys($value);

        if(isset($keys[0]) && isset($value[$keys[0]]))
        	$this->_setValue($value[$keys[0]]);
        else 
        	$this->_setValue(null);
        if(isset($keys[1]) && isset($value[$keys[1]]))
        	$this->_compareValue = $value[$keys[1]];
        else 
        	$this->_compareValue = null;

        if($this->_strict === true) {
            if($this->_compareValue !== $this->value) {
                $this->_error(self::EQUAL_STRICT);
                return false;
            }
        } else {
            if($this->_compareValue != $this->value) {
                $this->_error(self::EQUAL);
                return false;
            }
        }

        return true;
    }
}
?> 