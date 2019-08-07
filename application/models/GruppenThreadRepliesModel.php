<?php

/**
 * GruppenThreadRepliesModel
 *  
 * @author mb
 * @version 
 */
	
require_once 'Zend/Db/Table/Abstract.php';

class GruppenThreadRepliesModel extends Zend_Db_Table_Abstract
{
	/**
	 * The default table name 
	 */
    protected $_name = 'gruppen_thread_replies';
    protected $_primary = "reply_id";
    
    protected $_referenceMap    = array(
        'Thread' => array(
            'columns'           => 'thread_id',
            'refTableClass'     => 'GruppenThreadsModel',
            'refColumns'        => 'thread_id'
        )
    );
    public function insert(array $data)
    {
        // Einen Zeitstempel hinzufügen
        if (empty($data['date_add'])) {
            $data['date_add'] = time();
        }
        if (empty($data['date_edit'])) {
            $data['date_edit'] = time();
        }
        return parent::insert($data);
    }

    public function update(array $data, $where)
    {
        // Einen Zeitstempel hinzufügen
        if (empty($data['date_edit'])) {
            $data['date_edit'] = time();
        }
        return parent::update($data, $where);
    }
   

    /**
     * Holte die Antworten zu einem Thread
     *
     * @param INT $thread_id
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function getReplies($thread_id, $returnSelect = false)
    {
    	$select = $this->select()->setIntegrityCheck(false)
    					->from(array("r" => "gruppen_thread_replies"), array("*"))
						->join(array("u" => "users"), "u.user_id = r.writer_id", array("firstname", "lastname", "nickname", "image_id_m", "image_id_s")) 
    					->where("r.thread_id = ?", $thread_id)
    					->order("r.date_add DESC");
    	if($returnSelect)
    		return $select;
    	return $this->fetchAll($select);
    }
    /**
     * Gibt Anzahl an Antworten zurück
     *
     * @param INT $thread_id
     * @return INT
     */
    public function getNumberReplies($thread_id)
    {
    	$select = $this->select()
    					->from(array("r" => "gruppen_thread_replies"), array('replies' => 'COUNT(*)'))
    					->where("r.thread_id = ?", $thread_id);
    	return $this->fetchRow($select)->replies;
    					
    }
}
