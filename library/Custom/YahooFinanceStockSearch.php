<?php
/*
 * YahooFinanceStockSearch
 */

class YahooFinanceStockSearch
{
	private $response;
	private $response_parsed = array();
	private $status = 0;
	private $searchsting;
	private $proxylist; //array (0 => Array(key=> "", url=>""), 1=> Array(), ...)
	
	private $errorlist;
	
	private $base_url = "http://de.finance.yahoo.com/lookup?t=S&m=ALL&s=";
	
	// http://de.finance.yahoo.com/lookup?t=S&m=ALL&s=US0378331005
	
	//OLD http://de.finsearch.yahoo.com/de/index.php?s=&tp=S&r=*&nm=
	
	public function __construct($searchsting, $proxylist = null)
	{
		$this->searchstring = $searchsting;
		$this->proxylist = $proxylist;
		
		//Do It Baby!
		
		//Erst per Proxy...
		if(!$this->proxyRequest())
		{
			//Ansonsten direkt
			$this->directRequest();
		}
		
		if($this->response)
		{
			//ok, wenns geklappt hat, dann parsen wir mal jetzt...
		
			//@TODO was ist wenns mehrere Seiten sind?
			
			
		
			$reg_ex = "#<tr class=.*>.*<td.*>.*<a href=.*>(.*)</a>.*</td>.*<td.*>(.*)</td>.*<td.*>(.*)</td>.*<td.*>(.*)</td>.*<td.*>(.*)</td>.*<td.*>(.*)</td>.*</tr>#isU";
			$data = false;
			preg_match_all  ( $reg_ex, $this->response, $data );
			unset($data[0]); //Erstes Feld ist nur HTML-Shice
			//print_r($data);exit;
			if(isset($data[1][0]))
			{
				$return_val = array();
				for($i = 0; $i<count($data[1]);$i++)
				{
					$return_val[] = array(
											"symbol" => trim($data[1][$i]), 
											"name" => trim($data[2][$i]), 
											"isin" => trim($data[3][$i]), 
											//"wkn" => trim($data[4][$i]), 
											"market" => trim($data[6][$i]),
											//"volume" => trim($data[9][$i])
					);
				}
				$this->response_parsed = $return_val;			
			}
			else{
				$this->errorlist["isinNotFound"] = "Hups, nix gefunden";
			//@TODO es könnte aber auch ein redirect zur Unternehmensseite erfolgt sein, wenn ein Symbol (z.b. sbux oder APC.F) eingegeben wurde
			}

			//print_r($this->response_parsed);exit;

		}
		
	}
	private function proxyRequest()
	{
		$count_proxys = count($this->proxylist);
		if($count_proxys > 0)
		{
			//Alle Proxys durchprobieren ... aber zufällig ;)
			$request = $this->proxyRequestExecute($this->proxylist);
			return $request; // TRUE OR FALSE
		}
		else
			return false;
	}
	private function proxyRequestExecute($proxys)
	{
		$i = array_rand($proxys); //Zufällig Auswahl
		$url = $proxys[$i]["url"]."?key=".$proxys[$i]["key"]."&url=".urlencode($this->base_url.$this->searchstring);

		if(!$this->makeHttpRequest($url))
		{
			unset($proxys[$i]); //Aktuellen Proxy löschen
			if(count($proxys) > 0)
			{
				return $this->proxyRequestExecute($proxys);			
			}
			else
				return false;
		}
		else
			return true;
	}
	private function directRequest()
	{
		return $this->makeHttpRequest($this->base_url.urlencode($this->searchstring));
	}
	private function makeHttpRequest($url)
	{
		$client = new Zend_Http_Client();
		$client->setUri($url);
		$client->setConfig(array(
	   		'maxredirects' => 3,
	    	'timeout'      => 30,
			'useragent' => "Mozilla/5.0 (Windows; U; Windows NT 6.0; de; rv:1.9.1.2) Gecko/20090729 Firefox/3.5.2"
		));
	
		$response = $client->request();

		if ($response->getStatus() == 200) {
		  //echo "The request returned the following information:<br />";
		  $this->response = $response->getBody();
		  return true;
		} 
		else {
		  //echo "An error occurred while fetching data:<br />";
		  $this->errorlist = $response->getStatus() . ": " . $response->getMessage();
		  Zend_Registry::get('Zend_Log')->log("YahooFinanceStockSearch : URL: ".$url." : ".$this->errorlist, Zend_Log::ERR);		  
		  return false;
		}
	}
	
	/**
	 * Gibt die Resultate als YahooFinanceStock_Company_Set zurück
	 *
	 * @return YahooFinanceStock_Company_Set
	 */
	public function getResponseParsedGroupByISIN()
	{
		//if(!$this->response_parsed)
			//return false;
		
		$validator_isin = new Validate_Isin(); //ISIN validieren
		
		$arr = array();
		$arr_tmp = array();
		
		foreach($this->response_parsed as $row)
		{
			if(!in_array($row["isin"], $arr_tmp) && $validator_isin->isValid($row["isin"]))
			{
				$arr_tmp[] = $row["isin"];
				$arr[] = array("isin" => $row["isin"], "name" => $row["name"]);
			}
		}
		
		$object = new YahooFinanceStock_Company_Set($arr);
		
		return $object;
	}
	
	public function getResponse(){
		return $this->response;
	}
	public function getResponseParsed(){
		return $this->response_parsed;
	}
	public function getErrorlist(){
		return $this->errorlist;
	}
	
}