
<?php
require_once 'Zend/Validate/Abstract.php';

class Validate_GroupOpenStatus extends Zend_Validate_Abstract
{
    const MSG_NOTYORN = 'statusNotYorN';


    protected $_messageTemplates = array(
        self::MSG_NOTYORN 	=> "Der Status muss gesetzt werden.",
    );
    /**
     * Defined by Zend_Validate_Interface
     *
     * Returns true if $value = y OR n
     *
     * @param  string $value
     * @return boolean
     */
    public function isValid($value)
    {
        $this->_setValue($value);

	    if($value == "1" ||  $value == "0")
		{
			return true;
		}
		else
		{
			$this->_error(self::MSG_NOTYORN);
			return false;
		}    	


    }
}

