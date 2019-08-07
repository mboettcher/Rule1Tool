<?php

/**
 * KeydataanalysesdataModel
 *  
 * @author mb
 * @version 
 */

class KeydataanalysesdataModel extends Zend_Db_Table_Abstract {
	/**
	 * The default table name 
	 */
	protected $_name = 'keydataanalyses_data';
	protected $_primary = array("analysis_id", "year");
	
	protected $_referenceMap    = array(
        'Analysis' => array(
            'columns'           => 'analysis_id',
            'refTableClass'     => 'AnalysesModel',
            'refColumns'        => 'analysis_id',
	        'onDelete'          => self::CASCADE,
            'onUpdate'          => self::CASCADE
        )
    );

    public function getAllDataByAnalysisId($analysis_id){
    	$select = $this->select()->from($this, array(
    									"analysis_id", "year", "roic", "equity", "equity_rate", 
    									"depts", "revenue", "revenue_rate", "eps", "eps_rate", 
    									"income_after_tax", "cashflow", "cashflow_rate", "kgv"
    									)
    						)
    				->where("analysis_id = ?", $analysis_id)
    				->order("year DESC");
    	
    	return $this->fetchAll($select);
    }
}
