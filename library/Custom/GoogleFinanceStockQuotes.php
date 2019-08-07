<?php
/*
 * GoogleFinanceStockQuotes
 */
class GoogleFinanceStockQuotes
{

	private $base_url = 'http://www.google.com/ig/api';

	private $symbol; // Array oder string

	// http://www.google.com/ig/api?stock=.DJI
	
	protected $response;
	protected $response_parsed;
	
	private $errorlist;
	
	/**
	 * Holt aktuelle Kursdaten von Google
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
                
		$return_val = array();
		
		if($this->response)
		{
                    //print_r($this->response);echo"lala";exit;
			$response = simplexml_load_string($this->response);
                        
                        //print_r($response->finance[1]);
                        
                        foreach ($response->finance as $data)
                        {
                        
                            /**
                             * 
                             * what to expect:
                             * 
                             * finance =>
                             *  pretty_symbol
                             *  company
                             *  currency
                             *  last
                             *  high
                             *  low
                             *  volume
                             *  trade_date_utc
                             *  trade_time_utc
                             *  current_date_utc
                             * 
                             */
                            if(isset($data->last["data"]) && $data->last["data"] != "")
                            {
                                $symbol = trim($data->pretty_symbol["data"]);
                                
                                $date = substr($data->trade_date_utc["data"], 0, 4)."-"
                                        .substr($data->trade_date_utc["data"], 4, 2)."-"
                                        .substr($data->trade_date_utc["data"], 6, 2);
                                 
                                //Google-Symbol wieder in Yahoo-Symbol umwandeln
                                if(substr($symbol, 0, 1) == ".")
                                    $symbol = str_replace (".", "^", $symbol);

                                $return_val[] = array(	"symbol" => $symbol, 
                                                        "open" => trim($data->open["data"]), 
                                                        "last_price" => trim($data->last["data"]),
                                                        "volume" => trim($data->volume["data"]), 
                                                        "high" => trim($data->high["data"]), 
                                                        "low" => trim($data->low["data"]),
                                                        "date" => $date
                                                                                );   
                            }

                                

                        }
                        //print_r($return_val);//exit;
                        $this->response_parsed = $return_val;
                        
                        // $this->errorlist["symbolNotFound"] = "Hups, nix gefunden";

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
	//echo $url;exit;
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
		  Zend_Registry::get('Zend_Log')->log("GoogleFinanceStockQuotes : URL: ".$url." : ".$this->errorlist, Zend_Log::ERR);
		  return false;
		}
	}
	private function getUrl($urlencode = false)
	{
		if(is_array($this->symbol))
		{
                        $urlTags = "";
			//Zerlegen und mehrfache verwendung des tags
			foreach($this->symbol as $symbol)
			{
				if($urlTags != "")
					$urlTags .= "&";
				$urlTags .= $this->getSymbolTag($symbol, $urlencode);;
			}
		}
		else
                	$urlTags = $this->getSymbolTag($this->symbol, $urlencode);
                //echo $this->base_url."?".$urlTags; exit;
		return $this->base_url."?".$urlTags;
	}
        private function getSymbolTag($symbol, $urlencode)
        {
            //Symbol für Google preparieren, da in DB die Yahoo Symbole sind
            if(substr($symbol, 0, 1) == "^")
                $symbol = str_replace ("^", ".", $symbol);
            
            if($urlencode == true)
		$symbol = urlencode($symbol);
            return "stock=".$symbol;
        }
	public function getResponse()
	{
		return $this->response_parsed;
	}
}
