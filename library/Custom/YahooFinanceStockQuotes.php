<?php
/*
 * YahooFinanceStockQuotes
 */
class YahooFinanceStockQuotes
{

	private $base_url = 'http://finance.yahoo.com/d/quotes.csv';
	/*
	 * 	s  	 Symbol 
	 *  o  	 Open 
	 * 	l1 	 Last Trade (Price Only) 
	 *  d1	 Last Trade Date 
	 *  v  	 Volume 
	 *  h  	 Day's High 
	 *  g  	 Day's Low 
	 * 
	 *  e0	 Earnings/Share (NEW)
	 * 	j1	 Market Capitalization (NEW)
	 *  t1 	 Last Trade Time (NEW)
	*/
	private $tags = "sol1d1vhg"; 	// f=sol1t1vhg
	private $symbol; // Array oder string
	//?f=sl1d1t1c1ohgv
	//&s=
	// http://finance.yahoo.com/d/quotes.csv?f=sol1d1vhg&s=
	
	protected $response;
	protected $response_parsed;
	
	private $errorlist;
	
	/**
	 * Holt aktuelle Kursdaten von Yahoo
	 * 
	 * @param Array|String Symbol(e)
	 * @param Array Keys und URLs aus dem Config
	 * 
	 */
	public function __construct($symbol, $proxylist = null)
	{
		$this->symbol = $symbol;
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
			//echo $this->response;
			$data = false;
			$reg_ex = "#\"(.+)\",(.+),(.+),\"(.+)\",(.+),(.+),(.+)#";
			preg_match_all  ( $reg_ex, $this->response, $data );
			//print_r($data);
			unset($data[0]); //Erstes Feld ist nur Shice
			//print_r($data);
			if(isset($data[1][0]))
			{
				$anzahl_quotes = count($data[1]);
				$return_val = array();
				for($i = 0; $i<$anzahl_quotes;$i++)
				{
					$date = explode("/", trim($data[4][$i]));
					if(isset($date[2]) && isset($date[1]) && isset($date[0]))
						$date = $date[2]."-".$date[0]."-".$date[1];
					else
						$date = "0000-00-00";
					$return_val[] = array(	"symbol" => trim($data[1][$i]), 
											"open" => trim($data[2][$i]), 
											"last_price" => trim($data[3][$i]),
											"volume" => trim($data[5][$i]), 
											"high" => trim($data[6][$i]), 
											"low" => trim($data[7][$i]),
											"date" => $date
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
	
		$response = $client->request();

		if ($response->getStatus() == 200) {
		  //echo "The request returned the following information:<br />";
		  $this->response = $response->getBody();
		  //print_r($this->response);
		  return true;
		} 
		else {
		  //echo "An error occurred while fetching data:<br />";
		  $this->errorlist = $response->getStatus() . ": " . $response->getMessage();
		  Zend_Registry::get('Zend_Log')->log("YahooFinanceStockQuotes : URL: ".$url." : ".$this->errorlist, Zend_Log::ERR);
		  return false;
		}
	}
	private function getUrl($urlencode = false)
	{
		if(is_array($this->symbol))
		{
			//Zerlegen und mit + Verbinden
			$symbolstring = "";
			foreach($this->symbol as $symbol)
			{
				if($symbolstring != "")
					$symbolstring .= "+";
				$symbolstring .= $symbol;
			}
		}
		else
			$symbolstring = $this->symbol;
		
		if($urlencode == true)
			$symbolstring = urlencode($symbolstring);	
		//echo $this->base_url."?f=".$this->tags."&s=".$symbolstring;
		return $this->base_url."?f=".$this->tags."&s=".$symbolstring;
	}
	public function getResponse()
	{
		return $this->response_parsed;
	}
}
