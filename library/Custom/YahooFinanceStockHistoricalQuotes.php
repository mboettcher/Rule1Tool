<?php
/*
 * YahooFinanceStockHistoricalQuotes
 */
class YahooFinanceStockHistoricalQuotes
{

	private $base_url = 'http://ichart.finance.yahoo.com/table.csv';
// http://ichart.finance.yahoo.com/table.csv?s=APC.F&a=00&b=1&c=2003&d=08&e=2&f=2008&g=d&ignore=.csv
	
	/*
	 * ?s=APC.F&a=00&b=1&c=2003&d=08&e=2&f=2008&g=d&ignore=.csv
	 * s Symbol
	 * a Month -1 Begin
	 * b Day Begin
	 * c Year Begin
	 * d Month -1 End
	 * e Day End
	 * f Year End
	 * 
	 * Date,Open,High,Low,Close,Volume,Adj Close
	 * 2008-08-29,117.55,117.98,115.72,115.74,3100,115.74
	*/
	
	private $symbol; // String
	protected $timespan;
	protected $end_date;
	
	protected $response_parsed;
	
	protected $response = false;
	
	private $errorlist;
	
	/**
	 * Holt aktuelle Kursdaten von Yahoo
	 * 
	 * @param String Symbol
	 * @param Array Keys und URLs aus dem Config
	 * @param Int timestamp Enddatum (z.b. Heute)
	 * @param Int Zeitspanne in Tagen
	 * 
	 */
	public function __construct($symbol, $proxylist = null, $end_date = null, $timespan = 370)
	{
		if($end_date == null)
			$end_date = time();
		$this->symbol = $symbol;
		$this->proxylist = $proxylist;
		
		$this->end_date = $end_date;

		$this->timespan = $timespan;
		
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

			$data = false;
			$reg_ex = "#(.*),(.+),(.+),(.+),(.+),(.+),(.*)#";
			preg_match_all  ( $reg_ex, $this->response, $data );
			//print_r($data);
			unset($data[0]); //Erstes Feld ist nur Shice
			//print_r($data);
			if(isset($data[1][0]))
			{
				$anzahl_quotes = count($data[1]);
				$return_val = array();
				for($i = 1; $i<$anzahl_quotes;$i++)
				{
					//$date = explode("-",trim($data[1][$i]));
					
						$return_val[] = array(	"date" => $data[1][$i],
												"open" => trim($data[2][$i]), 
												"high" => trim($data[3][$i]), 
												"low" => trim($data[4][$i]),
												"last_price" => trim($data[5][$i]), 
												"volume" => trim($data[6][$i]) 
												);
					
				}
				$this->response_parsed = $return_val;			
			}
			else{
				$this->errorlist["symbolNotFound"] = "Hups, nix gefunden";
			}
			
			//print_r($return_val);

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
		$url = $proxys[$i]["url"]."?key=".$proxys[$i]["key"]."&url=".urlencode($this->getUrl());
				
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
		return $this->makeHttpRequest($this->getUrl(true));
	}
	private function makeHttpRequest($url)
	{
		$client = new Zend_Http_Client();
		$client->setUri($url);
		$client->setConfig(array(
	   		'maxredirects' => 2,
	    	'timeout'      => 30,
			'useragent' => "Mozilla/5.0 (Windows; U; Windows NT 6.0; de; rv:1.9.1.2) Gecko/20090729 Firefox/3.5.2"
		));
	
		try {
			$response = $client->request();
			if ($response->getStatus() == 200) {
			  //echo "The request returned the following information:<br />";
			  $this->response = $response->getBody();
			  return true;
			} 
			else {
			  //echo "An error occurred while fetching data:<br />";
			  $this->errorlist = $response->getStatus() . ": " . $response->getMessage();
			  Zend_Registry::get('Zend_Log')->log("YahooFinanceStockHistorical: URL: ".$url." : ".$this->errorlist, Zend_Log::NOTICE);		  
			  return false;
			}
		}
		catch (Zend_Exception $e)
		{
			Zend_Registry::get('Zend_Log')->log("YahooFinanceStockHistorical: URL: ".$url." : ".$e->getMessage(). "\n" .  $e->getTraceAsString(), Zend_Log::ERR);
			return false;
		}	
	}
	private function getUrl($urlencode = false)
	{
		/*
		 * 
	 * a Month -1 Begin
	 * b Day Begin
	 * c Year Begin
	 * d Month -1 End
	 * e Day End
	 * f Year End
		 */
		$symbolstring = $this->symbol;
		if($urlencode == true)
			$symbolstring = urlencode($symbolstring);	
		
		$end_date = new Zend_Date($this->end_date); 
		$begin_date = new Zend_Date($this->end_date - ($this->timespan * 24 * 60 *60)); 

		$end = $end_date->toArray();

		$begin = $begin_date->toArray();
		
		$begin["month"] = $begin["month"] -1; // A beginnt bei 0 - wieso auch immer... ungueltiges A oder B führt zur Ausgabe ALLER verfügbaren Kursdaten
		if($begin["month"] < 0)
			$begin["month"] = 0;

		//echo $this->base_url."?a=".$begin["month"]."&b=".$begin["day"]."&c=".$begin["year"]."&d=".$end["month"]."&e=".$end["day"]."&f=".$end["year"]."&s=".$symbolstring;
		return $this->base_url."?a=".$begin["month"]."&b=".$begin["day"]."&c=".$begin["year"]."&d=".$end["month"]."&e=".$end["day"]."&f=".$end["year"]."&s=".$symbolstring;
	}
	public function getResponse()
	{
		return $this->response_parsed;
	}
}
