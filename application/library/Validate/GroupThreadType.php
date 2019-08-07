
<?php
require_once 'Zend/Validate/Abstract.php';

class Validate_GroupThreadType extends Zend_Validate_Abstract
{
    const MSG_NOTVALID = 'groupeTypeNotValid';


    protected $_messageTemplates = array(
        self::MSG_NOTVALID 	=> "Der Status muss auf y oder auf n gesetzt werden.",
    );
    /**
     * Defined by Zend_Validate_Interface
     *
     * Returns true if $value in DB
     *
     * @param  string $value
     * @return boolean
     */
    public function isValid($value)
    {
        $this->_setValue($value);

     
    		$table = new GruppenThreadTypsModel();
    		$rowset = $table->find($value);
	        $rowCount = count($rowset);
	        
	    	if($rowCount > 0)
	    	{
				//Alles OK!
				return true;
			}
			else
			{
				$this->_error(self::MSG_NOTVALID);
				return false;
			}   	


    }
}

