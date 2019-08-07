
<?php
require_once 'Zend/Validate/Abstract.php';

class Validate_CompanyId extends Zend_Validate_Abstract
{
    const MSG_NOTFOUND = 'companyIdNotFound';

    protected $_messageTemplates = array(
        self::MSG_NOTFOUND 	=> "Die angegebene Company-ID '%value%' existiert nicht",
    );
    /**
     * Defined by Zend_Validate_Interface
     *
     * Returns true if and only if $value is a valid ID
     *
     * @param  string $value Company-ID
     * @return boolean
     */
    public function isValid($value)
    {
        $this->_setValue($value);
        
		$validator_int = new Zend_Validate_Int();
		
    	if($validator_int->isValid($value))
    	{
    		$table = new CompaniesModel();
    		$rowset = $table->find($value);
	        $rowCount = count($rowset);
	        
	    	if($rowCount > 0)
	    	{
				//Alles OK!
				return true;
			}
			else
			{
				$this->_error(self::MSG_NOTFOUND);
				return false;
			}    	
    	}
    	else
		{
			$this->_error(self::MSG_NOTFOUND);
			return false;
		} 

    }
}

