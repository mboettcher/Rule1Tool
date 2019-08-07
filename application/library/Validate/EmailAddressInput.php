
<?php
require_once 'Zend/Validate/Abstract.php';

class Validate_EmailAddressInput extends Zend_Validate_Abstract
{
    const MSG_FOUND = 'emailAdressFound';
    const MSG_NOTVALID = 'emailAdressNotValid';

    protected $_messageTemplates = array(
        self::MSG_FOUND 	=> "Die angegebene E-Mail-Adresse '%value%' existiert bereits.",
        self::MSG_NOTVALID 	=> "Die angegebene E-Mail-Adresse '%value%' ist keine gültige Adresse."
    );
    
    protected $_whereNotUserId = false;
    
    public function __construct($_whereNot = false)
    {
    	if($_whereNot)
    		$this->setWhereNot($_whereNot);
    }
    public function setWhereNot($_whereNot)
    {
    	$this->_whereNotUserId = $_whereNot;
    }
    /**
     * Defined by Zend_Validate_Interface
     *
     * Returns true if $value not exists
     *
     * @param  string $value User-ID
     * @return boolean
     */
    public function isValid($value)
    {
        $this->_setValue($value);
        
		$validator_mail = new Zend_Validate_EmailAddress();
		
    	if($validator_mail->isValid($value))
    	{
    		$table = new UsersModel();
	        if($this->_whereNotUserId)
    			$row = $table->findByEmailWhereNotUser($value, $this->_whereNotUserId);
    		else
	        	$row = $table->findByEmail($value);
	        	
	    	if(!$row)
			{
				//E-Mail-Adresse nicht gefunden
				return true;
			}
			else
			{
				$this->_error(self::MSG_FOUND);
				return false;
			}    	
    	}
    	else
		{
			$this->_error(self::MSG_NOTVALID);
			return false;
		} 

    }
}

