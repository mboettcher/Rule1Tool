<?php

/**
 * USersModel
 *  
 * @author mb
 * @version 
 */

require_once 'Zend/Db/Table/Abstract.php';

class UsersModel extends Zend_Db_Table_Abstract {
	/**
	 * The default table name 
	 */
	protected $_name = 'users';
	protected $_primary = 'user_id';
	
    public function insert(array $data)
    {
        // Einen Zeitstempel hinzufügen
        if (empty($data['reg_date'])) {
            $data['reg_date'] = time();
        }
        if (empty($data['edit_date'])) {
            $data['edit_date'] = time();
        }
        return parent::insert($data);
    }

    public function update(array $data, $where)
    {
        // Einen Zeitstempel hinzufügen
        if (empty($data['edit_date'])) {
            $data['edit_date'] = time();
        }
        return parent::update($data, $where);
    }
    /**
     * Enter description here...
     *
     * @param STRING $email
     * @return Zend_Db_Table_Row
     */
	public function findByEmail($email)
	{
		$select = $this->select()->where("email like ?", $email);
		return $this->fetchRow($select);
	}
	 /**
     * Enter description here...
     *
     * @param STRING $nickname
     * @return Zend_Db_Table_Row|NULL Zend_Db_Table_Row_Abstract The row results per the 
	 Zend_Db_Adapter fetch mode, or null if no row found.
     */
	public function findByNickname($nickname)
	{
		$select = $this->select()->where("nickname like ?", $nickname);
		return $this->fetchRow($select);
	}
	 /**
     * Enter description here...
     *
     * @param STRING $email
     * @param INT $user_id
     * @return Zend_Db_Table_Row|NULL Zend_Db_Table_Row_Abstract The row results per the 
	 Zend_Db_Adapter fetch mode, or null if no row found.
     */
	public function findByEmailWhereNotUser($email, $user_id)
	{
		$select = $this->select()->where("email like ?", $email)
								->where("user_id != ?", $user_id);
		return $this->fetchRow($select);
	}
	 /**
     * Enter description here...
     *
     * @param STRING $nickname
     * @param INT $user_id
     * @return Zend_Db_Table_Row|NULL Zend_Db_Table_Row_Abstract The row results per the 
	 Zend_Db_Adapter fetch mode, or null if no row found.
     */
	public function findByNicknameWhereNotUser($nickname, $user_id)
	{
		$select = $this->select()->where("nickname = ?", $nickname)
								->where("user_id != ?", $user_id);
		return $this->fetchRow($select);
	}
	public function getTableName()
	{
		return $this->_name;
	}
}
