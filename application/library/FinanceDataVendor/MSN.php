<?php

class FinanceDataVendor_MSN {

	protected $_data = array(
							"cashflow" => array(),
							"eps" => array(), 
							"equity" => array(),
							"revenue" => array(), 
							"depts" => array(),
							"income_after_tax" => array(),
							"kgv" => array(),
							"current_eps" => null,
							"analysts_estimated_growth" => null
							); 
	protected $_dataFetched = false;
	
	/*

	 * Cashflow / Net Cash - Ending Balance 
	 * http://moneycentral.msn.com/investor/invsub/results/statemnt.aspx?lstStatement=CashFlow&stmtView=Ann&Symbol=["Ticker","Symbol"]
	 * 
	 * Avg P/E/ KGV 
	 * http://moneycentral.msn.com/investor/invsub/results/compare.asp?Page=TenYearSummary&Symbol=["Ticker","Symbol"]
	 * 
	 * Total Equity
	 * http://moneycentral.msn.com/investor/invsub/results/statemnt.aspx?lstStatement=Balance&stmtView=Ann&Symbol=US0378331005
	 * 
	 * EPS, Total Net Income (Ertrag nach Steuern), Sales/Umsatz, Long Term Debt/Depts 
	 * http://moneycentral.msn.com/investor/invsub/results/statemnt.aspx?lstStatement=10YearSummary&stmtView=Ann&Symbol=["Ticker","Symbol"]
	 * 
	 * Earnings/Share (current_eps)
	 * http://moneycentral.msn.com/detail/stock_quote?Symbol=US0378331005 
	 * 
	 * Company->Next 5 Years
	 * http://moneycentral.msn.com/investor/invsub/analyst/earnest.asp?Page=EarningsGrowthRates&Symbol=US0378331005
	 * 
	 * 
	 */
	
	protected $_urls = array(
							"http://moneycentral.msn.com/investor/invsub/results/statemnt.aspx?lstStatement=CashFlow&stmtView=Ann&Symbol=",
							"http://moneycentral.msn.com/investor/invsub/results/compare.asp?Page=TenYearSummary&Symbol=",
							"http://moneycentral.msn.com/investor/invsub/results/statemnt.aspx?lstStatement=Balance&stmtView=Ann&Symbol=",
							"http://moneycentral.msn.com/investor/invsub/results/statemnt.aspx?lstStatement=10YearSummary&stmtView=Ann&Symbol=",
							"http://moneycentral.msn.com/detail/stock_quote?Symbol=",
							"http://moneycentral.msn.com/investor/invsub/analyst/earnest.asp?Page=EarningsGrowthRates&Symbol="
	);
	protected $_responses = array();
	
	
	public function getData()
	{
		if(!$this->_dataFetched)
			$this->_fetchData();
		return $this->_data;
	}
	protected function _fetchData()
	{
		//requests machen
		foreach ($this->_urls as $url)
			$this->_responses[] = $this->_getRequest($url);

		//Daten parsen
			
		$this->_dataFetched = true;
	}
	protected function _getRequest($url)
	{
		$client = new Zend_Http_Client();
		$client->setUri($url);
		$client->setConfig(array(
	   		'maxredirects' => 1,
	    	'timeout'      => 30,
			'useragent' => "Mozilla/5.0 (Windows; U; Windows NT 6.0; de; rv:1.9.1.2) Gecko/20090729 Firefox/3.5.2"
		));
	
		return $client->request();
	}
	protected function _parseCashflow()
	{
		
		$dom = new Zend_Dom_Query($this->_responses[0]);
		$results = $dom->query('.table.ftable tr');
		
		$count = count($results); // get number of matches: 4
		foreach ($results as $result) {
		    // $result is a DOMElement
		}
	}
}

?>