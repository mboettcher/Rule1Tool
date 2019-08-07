<?php
class Watchlist_PaginatorAdapter extends Zend_Paginator_Adapter_DbSelect
{

	/**
     * Constructor.
     *
     * @param Zend_Db_Select $select The select query
     */
    public function __construct(Zend_Db_Select $select)
    {
        $this->_select = $select;
    }

    /**
     * Returns a Zend_Db_Table_Rowset_Abstract of items for a page.
     *
     * @param  integer $offset Page offset
     * @param  integer $itemCountPerPage Number of items per page
     * @return Group_Thread_ReplySet
     */
    public function getItems($offset, $itemCountPerPage)
    {
        $this->_select->limit($itemCountPerPage, $offset);
        
        return new Watchlist_Set($this->_select->getTable()->fetchAll($this->_select)->toArray());
    }
	
	
}