<?php

/**
 * PortfolioTransactionsModel
 *  
 * @author boettcher
 * @version 
 */

require_once 'Zend/Db/Table/Abstract.php';

class PortfolioTransactionsModel extends Zend_Db_Table_Abstract {
	/**
	 * The default table name 
	 */
	protected $_name = 'portfolio_transactions';
    
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
     * Gibt RowSet einer Portfolio zurück
     *
     * @param integer $portfolio_id
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function getPortfolio($portfolio_id)
    {
    	$select = $this->select()->setIntegrityCheck(false);
    	$select			->from(array("pt" => "portfolio_transactions"), "`tid`,`portfolio_id`, sum(anzahl) as realcount")
						->join(array("c" => "companies"), "pt.company_id = c.company_id", array("company_name" => "name", "isin", "company_id", "company_type" => "type", "main_market", "picture_id"))   
    					->where("pt.portfolio_id = ?", $portfolio_id)
    					//->order("c.name ASC")
    					->where("pt.type = ?", 1) //nur Sells und Buys
						->having("realcount > 0")
						->group('pt.company_id')
					;
    										
    	$rows = $this->fetchAll($select);

    	return $rows;

    }
}
