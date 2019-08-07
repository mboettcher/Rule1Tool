<?php
/*
 * GoogleFinanceStockSearch
 */

class GoogleFinanceStockSearch
{
	private $response;
	private $response_parsed = false;
	private $status = 0;
	private $searchsting;
	private $proxylist; //array (0 => Array(key=> "", url=>""), 1=> Array(), ...)
	
	private $errorlist;
	
	private $base_url = "http://www.google.com/finance?q=";
	
	// http://www.google.com/finance?q=US0378331005
	
	/**
	 * Hol zu einem bestimmten US-Unternehmen den US-Markt und Symbol
	 *
	 * @param STRING $isin
	 * @param ARRAY $proxylist
	 */
	public function __construct($isin, $proxylist = null)
	{
		$this->searchstring = $isin;
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
			
			$reg_ex = '#<div class="[^>]*" id=companyheader>.*<h3>(.*)&nbsp;</h3>\(Public, (.*):(.*)\)#siU';
			
			$data = false;
			//print_r($this->response);exit;
			preg_match_all  ( $reg_ex, $this->response, $data );
			unset($data[0]); //Erstes Feld ist nur HTML-Shice
			//print_r($data);exit;
			if(isset($data[1][0]))
			{
				
				$return_val = array(
										"symbol" => trim($data[3][0]), 
										"name" => trim($data[1][0]), 
										"isin" => $this->searchstring, 
										"market" => trim($data[2][0])
				);
				
				$this->response_parsed = $return_val;			
			}
			else{
				$this->errorlist["isinNotFound"] = "Hups, nix gefunden";
			//@TODO es könnte aber auch ein redirect zur Unternehmensseite erfolgt sein, wenn ein Symbol (z.b. sbux oder APC.F) eingegeben wurde
			}

			//print_r($return_val);exit;

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
		  Zend_Registry::get('Zend_Log')->log("GoogleFinanceStockSearch : URL: ".$url." : ".$this->errorlist, Zend_Log::ERR);		  
		  return false;
		}
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