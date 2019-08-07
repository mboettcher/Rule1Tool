
<?php
require_once 'Zend/Validate/Abstract.php';

class Validate_NicknameInput extends Zend_Validate_Abstract
{
    const MSG_NICKNAMEFOUND = 'nicknameFound';
    const MSG_NICKNAMETOOSHORTLONG = 'nicknameTooShortOrLong';

    protected $_messageTemplates = array(
        self::MSG_NICKNAMEFOUND 	=> "Der angegebene Mitgliedsname '%value%' existiert bereits.",
        self::MSG_NICKNAMETOOSHORTLONG 	=> "Der angegebene Mitgliedsname '%value%' hat nicht die vorgeschriebene Länge (Mind. 2 Zeichen und max. 20 Zeichen)."
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
     * Returns true if and only if $value not exists
     *
     * @param  string $value Nickname
     * @return boolean
     */
    public function isValid($value)
    {
        $this->_setValue($value);
        
		$validator_string = new Zend_Validate_StringLength(2,20);
		
    	if($validator_string->isValid($value))
    	{
    		$table = new UsersModel();
    		if($this->_whereNotUserId)
    			$row = $table->findByNicknameWhereNotUser($value, $this->_whereNotUserId);
    		else
	        	$row = $table->findByNickname($value);
	    	if(!$row)
			{
				//Nickname nicht gefunden
				return true;
			}
			else
			{
				$this->_error(self::MSG_NICKNAMEFOUND);
				return false;
			}    	
    	}
    	else
		{
			$this->_error(self::MSG_NICKNAMETOOSHORTLONG);
			return false;
		} 

    }
}

