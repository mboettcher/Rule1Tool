<?php

/**
 * RegistrationsModel
 *  
 * @author martinbottcher
 * @version 
 */

require_once 'Zend/Db/Table/Abstract.php';

class RegistrationsModel extends Zend_Db_Table_Abstract {
	/**
	 * The default table name 
	 */
	protected $_name = 'registrations';
	protected $_primary = "user_id";

}
