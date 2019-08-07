<?php

/**
 * InvitationReg
 *  
 * @author boettcher
 * @version 
 */

require_once 'Zend/Db/Table/Abstract.php';

class InvitationReg extends Zend_Db_Table_Abstract {
	/**
	 * The default table name 
	 */
	protected $_name = 'invitation_reg';
    protected $_primary = "mail";

	
    public function insert(array $data)
    {
        // Einen Zeitstempel hinzufügen
        if (empty($data['date_add'])) {
            $data['date_add'] = time();
        }
        return parent::insert($data);
    }

}
