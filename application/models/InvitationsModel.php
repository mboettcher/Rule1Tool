<?php

/**
 * InvitationsModel
 *  
 * @author martinbottcher
 * @version 
 */

require_once 'Zend/Db/Table/Abstract.php';

class InvitationsModel extends Zend_Db_Table_Abstract {
	/**
	 * The default table name 
	 */
	protected $_name = 'invitations';
	protected $_primary = "invitation_id";
	
	public function insert(array $data)
    {
        // Einen Zeitstempel hinzufügen
        if (empty($data['date_send'])) {
            $data['date_send'] = time();
        }

        return parent::insert($data);
    }

	

}
