
<?php
require_once 'Zend/Validate/Abstract.php';

class Validate_Invitation extends Zend_Validate_Abstract
{
    const MSG_NOTFOUND = 'invitationNotFound';

    protected $_messageTemplates = array(
        self::MSG_NOTFOUND 	=> "Der angegebene Einladungsschlüssel '%value%' ist nicht gültig"
    );
    /**
     * Defined by Zend_Validate_Interface
     *
     * Returns true if and only if $value is a valid ID
     *
     * @param  string $value User-ID
     * @return boolean
     */
    public function isValid($value)
    {
        $this->_setValue($value);
        
   		$invite_tbl = new InvitationsModel();
       	$select = $invite_tbl->select()->where("`key` = ?", $value)
       													->where("date_reg IS NULL");
       	$rows = $invite_tbl->fetchAll($select);
        
    	if(count($rows) > 0)
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
}

