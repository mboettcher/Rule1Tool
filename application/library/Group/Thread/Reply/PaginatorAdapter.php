<?php
class Group_Thread_Reply_PaginatorAdapter extends Zend_Paginator_Adapter_DbSelect
{

	protected $_thread_id;
	
    /**
     * Constructor.
     *
     * @param Zend_Db_Select $select The select query
     */
    public function __construct($thread_id)
    {
    	$this->_thread_id = $thread_id;
    	
    	$tbl = new GruppenThreadRepliesModel();
		$select = $tbl->getReplies($this->_thread_id, true);
		
        $this->_select = $select;
    }

    /**
     * Returns a Zend_Db_Table_Rowset_Abstract of items for a page.
     *
     * @param  integer $offset Page offset
     * @param  integer $itemCountPerPage Number of items per page
     * @return Group_Thread_Reply_Set
     */
    public function getItems($offset, $itemCountPerPage)
    {
        $this->_select->limit($itemCountPerPage, $offset);
        
        return new Group_Thread_Reply_Set($this->_select->getTable()->fetchAll($this->_select)->toArray());
    }
	
	
}