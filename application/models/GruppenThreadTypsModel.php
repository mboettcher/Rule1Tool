<?php

/**
 * GruppenThreadTypsModel
 *  
 * @author mb
 * @version 
 */
	
require_once 'Zend/Db/Table/Abstract.php';

class GruppenThreadTypsModel extends Zend_Db_Table_Abstract
{
	/**
	 * The default table name 
	 */
    protected $_name = 'gruppen_thread_typs';
    protected $_primary = "type_id";

     protected $_dependentTables = array('GruppenThreadsModel');
}
