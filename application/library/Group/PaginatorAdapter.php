<?php
class Group_PaginatorAdapter extends Zend_Paginator_Adapter_DbSelect
{
    /**
     * Constructor.
     *
     * @param Zend_Db_Select $select The select query
     */
    public function __construct($select)
    {
        $this->_select = $select;
    }

    /**
     * Returns a Zend_Db_Table_Rowset_Abstract of items for a page.
     *
     * @param  integer $offset Page offset
     * @param  integer $itemCountPerPage Number of items per page
     * @return Group_Set
     */
    public function getItems($offset, $itemCountPerPage)
    {
        $this->_select->limit($itemCountPerPage, $offset);
        
        return new Group_Set($this->_select->getTable()->fetchAll($this->_select)->toArray());
    }
	
	
}