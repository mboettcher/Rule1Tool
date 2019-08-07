<?php
class Group_Thread_PaginatorAdapter extends Zend_Paginator_Adapter_DbSelect
{

	protected $_group_id;
	
    /**
     * Constructor.
     *
     * @param Zend_Db_Select $select The select query
     */
    public function __construct($group_id)
    {
    	$this->_group_id = $group_id;
    	
    	$tbl = new GruppenThreadsModel();
		$select = $tbl->getThreads($this->_group_id, true);
		
        $this->_select = $select;
    }

    /**
     * Returns a Zend_Db_Table_Rowset_Abstract of items for a page.
     *
     * @param  integer $offset Page offset
     * @param  integer $itemCountPerPage Number of items per page
     * @return Group_Thread_Set
     */
    public function getItems($offset, $itemCountPerPage)
    {
        $this->_select->limit($itemCountPerPage, $offset);
        
        return new Group_Thread_Set($this->_select->getTable()->fetchAll($this->_select)->toArray());
    }
	
	
}