<?php

/**
 * AnalysisModel
 *  
 * @author mb
 * @version 
 */


class AnalysisModel extends Zend_Db_Table_Abstract {
	/**
	 * The default table name 
	 */
	protected $_name = 'analysis';
	protected $_primary = "analysis_id";
	
	protected $_dependentTables = array('KeydataanalysesdataModel');
	
	protected $_referenceMap  = array(
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
	public function getListOfAnalysesByCompanyId($company_id, $user_id)
	{
		$select = $this->select()->from($this, array("analysis_id", "date_edit", "user_id"))
								->where("company_id = ?", $company_id)
								->where("(private = 0 OR user_id = ?)", $user_id)
								->order("date_edit DESC");
								
		return $this->fetchAll($select)->toArray();
	}
	public function getAllDataByAnalysisId($analysis_id){
		$select = $this->select()->from($this, array("analysis_id", "company_id", "user_id", 
														"date_add", "date_edit", 
														"note", "date_delete","moat", "management", 
														"delete_by", "analysts_estimated_growth", 
														"current_eps", "my_estimated_growth",
		                                                "my_future_kgv", "thread_id", "private", "currency"))
								->where("analysis_id = ?", $analysis_id);
								
		return $this->fetchRow($select);	
	}
	public function getTableName()
	{
		return $this->_name;
	}
}
