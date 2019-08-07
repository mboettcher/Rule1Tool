
<?php
require_once 'Zend/Validate/Abstract.php';

class Validate_Isin extends Zend_Validate_Abstract
{
    const MSG_LENGTH = 'isinNotRightLength';
    const MSG_FORMAT = 'isinWrongFormat';

    protected $_messageTemplates = array(
        self::MSG_LENGTH 	=> "Die ISIN '%value%' hat nicht 12 Zeichen",
        self::MSG_FORMAT 	=> "'%value%' ist keine ISIN"
    );
    /**
     * Defined by Zend_Validate_Interface
     *
     * Returns true if and only if $value is a valid ISIN
     *
     * @param  string $value ISIN
     * @return boolean
     */
    public function isValid($value)
    {
        $this->_setValue($value);
		$validator_length = new Zend_Validate_StringLength(12, 12); //Insgesamtlänge
		$validator_alpha = new Zend_Validate_Alpha(); // Ersten beiden Zeichen
		$validator_alphanum = new Zend_Validate_Alnum(); // der Rest müss Zahlen oder Buchstaben sein
		
    	if(!$validator_length->isValid($value))
		{
			$this->_error(self::MSG_LENGTH);
			
			return false;
		}
		else
		{
			if(!$validator_alpha->isValid(substr($value, 0, 2)) || !$validator_alphanum->isValid(substr($value, 2)))
			{
				$this->_error(self::MSG_FORMAT);
			
				return false;
			}
			
			//Alles OK!
			return true;
		}
    }
}

