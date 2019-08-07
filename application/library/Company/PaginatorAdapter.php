<?php
class Company_PaginatorAdapter extends Zend_Paginator_Adapter_DbSelect
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
     * Returns a Company_Set of items for a page.
     *
     * @param  integer $offset Page offset
     * @param  integer $itemCountPerPage Number of items per page
     * @return Company_Set
     */
    public function getItems($offset, $itemCountPerPage)
    {
        $this->_select->limit($itemCountPerPage, $offset);
        
        return new Company_Set($this->_select->getTable()->fetchAll($this->_select)->toArray());
    }
	
	
}