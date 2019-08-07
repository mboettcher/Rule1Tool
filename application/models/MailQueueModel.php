<?php

/**
 * MailQueueModel
 *  
 * @author martinbottcher
 * @version 
 */

require_once 'Zend/Db/Table/Abstract.php';

class MailQueueModel extends Zend_Db_Table_Abstract {
	/**
	 * The default table name 
	 */
	protected $_name = 'mail_queue';
	protected $_primary = "id";

    public function insert(array $data)
    {
        // Einen Zeitstempel hinzufügen
        if (empty($data['date'])) {
            $data['date'] = time();
        }
        return parent::insert($data);
    }
}
