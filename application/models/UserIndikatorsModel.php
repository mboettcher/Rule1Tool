<?php

/**
 * UserIndikatorsModel
 *  
 * @author boettcher
 * @version 
 */

require_once 'Zend/Db/Table/Abstract.php';

class UserIndikatorsModel extends Zend_Db_Table_Abstract {
	/**
	 * The default table name 
	 */
	protected $_name = 'user_indikators';
	protected $_primary = "id";
	


}
