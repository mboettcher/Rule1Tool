<?php

/**
 * AnalysisFavouritsModel
 *  
 * @author martinbottcher
 * @version 
 */

require_once 'Zend/Db/Table/Abstract.php';

class AnalysisFavouritsModel extends Zend_Db_Table_Abstract {
	/**
	 * The default table name 
	 */
	protected $_name = 'analysisfavourits';
	
	public function getMostPopular($company_id)
	{
		$select = $this->select()
				->from($this,array('anzahl' => 'COUNT(*)', "analysis_id"))
				->where("company_id = ?", $company_id)
				->group("analysis_id")
				->order("anzahl ASC");
		$row = $this->fetchRow($select);
		
		if($row)
			return $row->analysis_id;
		else
			return false;
	}
	
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
}
