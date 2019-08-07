<?php

/**
 * MailSignalLog
 *  
 * @author martinboettcher
 * @version 
 */

require_once 'Zend/Db/Table/Abstract.php';

class MailSignalLogModel extends Zend_Db_Table_Abstract {
	/**
	 * The default table name 
	 */
	protected $_name = 'mail_signal_log';
	protected $_primary = array('id');
	
	public function insertMaillog($user_id, $date)
    {
    	$data = array();
        $data['time'] = time();
        
        $data['user_id'] = $user_id;
        $data['date'] = $date;

        return parent::insert($data);
    }
    /**
     * Gibt true zurück wenn bereits in Log
     *
     * @param int $user_id
     * @param string $date
     * @return boolean
     */
    public function findUserIdAndDate($user_id, $date)
    {
    	$select = $this->select()
    					->where('user_id = ?', $user_id)
    					->where('date = ?', $date);
    	$rows = $this->fetchAll($select);
    	if(count($rows) > 0)
    		return true;
    	else 
    		return false;
    }
	public function findDate(string $date)
    {
    	$select = $this->select()
    					->where('date = ?', $date);
    	$rows = $this->fetchAll($select);
    	if(count($rows) > 0)
    		return true;
    	else 
    		return false;
    }

	

}
