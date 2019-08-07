<?php

class Portfolio extends Abstraction implements SeekableIterator, Countable {

	protected $_name;
	protected $_id;
	protected $_user_id;
	protected $_currency;
	protected $_date_create;
	protected $_send_signal_mail;
	
	protected $_portfolioDaten = false;
	protected $_dataStocksFetched = false;
	
	
	/**
	 * PortfolioModel
	 *
	 * @var PortfolioModel
	 */
	protected $_PortfolioModel = null;
	/**
	 * PortfolioTransactionModel
	 *
	 * @var PortfolioTransactionModel
	 */
	protected $_PortfolioTransactionsModel = null;
	
	
	 /**
     * Iterator pointer.
     *
     * @var integer
     */
    protected $_pointer = 0;

    /**
     * How many data rows there are.
     *
     * @var integer
     */
    protected $_count;
	/**
	 * Portfolio Stocks
	 *
	 * @var Zend_Db_Table_Rowset_Abstract
	 */
	protected $_portfolioStocksData = null;
	/**
	 * PortfolioStockItems Quotes
	 *
	 * @var ARRAY
	 */
	protected $_portfolio = array();    
	
	public function __construct($portfolioId = null, $data = null)
	{
		if($portfolioId !== null)
		{
			$this->_getPortfolioBasicData($portfolioId);
		}
		elseif ($data !== null)
		{
			$this->_setId($data["id"]);
			$this->setName($data["name"]);
			$this->setCurrency($data["currency"]);
			$this->setUserId($data["user_id"]);
			$this->setSendSignalMail($data["send_signal_mail"]);
		}
	}
	protected function _getPortfolioBasicData($portfolioId)
	{
			$this->_setId($portfolioId);	
			$model = new PortfolioModel();
			$rows = $model->find($this->getId());
			if($rows->count() > 0)
			{
				$row = $rows->current();
				$this->setName($row->name);
				$this->setCurrency($row->currency);
				$this->setUserId($row->user_id);
				$this->setSendSignalMail($row->send_signal_mail);
			}
			else
			{
				$this->_id = null;
			}
	}
	
	protected function _setId($id)
	{
		$this->_id = $id;
		return true;		
	}
	public function getName()
	{
		return $this->_name;
	}
	public function setName($name)
	{
		$this->_name = $name;	
		return true;		
	}
	public function getSendSignalMail()
	{
		return $this->_send_signal_mail;
	}
	public function setSendSignalMail($val)
	{
		$this->_send_signal_mail = $val;	
		return true;		
	}
	public function getCurrency()
	{
		return $this->_currency;
	}
	public function setCurrency($currency)
	{
		$this->_currency = $currency;
		return true;			
	}
	public function setUserId($id)
	{
		$this->_user_id = $id;
    	return true;			
	}
	public function getUserId()
	{
		return $this->_user_id;
	}
	public function getId()
	{
		return $this->_id;
	}

	public function getPortfolioId()
	{
		return $this->getId();
	}
	public function sendSignalMail()
	{
		return $this->_send_signal_mail;
	}
	
	public function add($data)
	{
		if (!($data = $this->validateData($data, "add")))
			return false;
		$model = new PortfolioModel();
		$row = $model->createRow(array(
					"name" => $data["name"], 
					"user_id" => $data["user_id"],
					"currency" => $data["currency"],
					"send_signal_mail" => $data["send_signal_mail"]
		));
		if(($pid = $row->save()))
		{
			$this->_getPortfolioBasicData($pid);
			return $pid;
		}
		else 
			return false;
	}
	public function edit($data)
	{
		if (!($data = $this->validateData($data, "edit")))
			return false;
			
		$model = new PortfolioModel();
		$rows = $model->find($this->getId());
		if($rows->count() > 0)
		{
			$row = $rows->current();
			
			$row->name = $data["name"];
			$row->send_signal_mail = $data["send_signal_mail"];
					
			if($row->save())
			{
				$this->_getPortfolioBasicData($this->getId());
				return true;
			}
			else 
				return false;
		}
		else
			$save = false;	
		
			
		if($save)
		{
			$this->_getMessageBox()->setMessage("MSG_PORTFOLIO_009",$this->getName());
			return $this->getId();
		}
		else 
		{
			$this->_getMessageBox()->setMessage("MSG_PORTFOLIO_010",$this->getName());
			return false;
		}		
	}
	/**
	 * Prüft und filtert die Daten
	 *
	 * @param ARRAY $data
	 * @param STRING $modus add or edit
	 * @return ARRAY|FALSE
	 */
	protected function validateData($data, $modus)
	{
		//Filters
		$filters = array('*' => array('StripTags','StringTrim'));
 		//Validators
 		if($modus == "add")
 		{
 			$validators = array(
			    'name' => array(new Zend_Validate_StringLength(1,35), 'presence' => 'required'),
				'user_id' =>  array(new Validate_UserId(), 'presence' => 'required'),
 				'send_signal_mail' => array(new Zend_Validate_InArray(array(0,1)), 'presence' => 'required'),
 				'currency' => array(new Zend_Validate_InArray(Zend_Registry::get("config")->general->currencies->toArray()), 'presence' => 'required')
            );
 		}
 		else
 		{
 			$validators = array(
			    'name' => array(new Zend_Validate_StringLength(1,35), 'presence' => 'optional'),
				'user_id' =>  array(new Validate_UserId(), 'presence' => 'optional'),
 				'send_signal_mail' => array(new Zend_Validate_InArray(array(0,1)), 'presence' => 'optional'),
 				'currency' => array(new Zend_Validate_InArray(Zend_Registry::get("config")->general->currencies->toArray()), 'presence' => 'optional')
            );	 		
 		}

		
		//Filter_Input starten
		$input = new Zend_Filter_Input($filters, $validators, $data);
		$input->setDefaultEscapeFilter(new Filter_HtmlSpecialChars());
		if ($input->isValid())  //Prüfen
		{
			return $input->getEscaped();
		}
		else
		{
			$this->_getMessageBox()->setMessagesDirect($input->getMessages());
			return false;
		}
	}
	public function delete()
	{
		$model = new PortfolioModel();
		$rows = $model->find($this->getId());
		if($rows->count() > 0)
		{
			$row = $rows->current();
			$delete = $row->delete();
		}
		else
			$delete = false;
			
		if($delete)
		{
			$this->_getMessageBox()->setMessage("MSG_PORTFOLIO_007");
			return true;
		}
		else 
		{
			$this->_getMessageBox()->setMessage("MSG_PORTFOLIO_008");
			return false;
		}	
	}
	
	public function getTransactions()
	{
		
	}
	/**
	 * Holt die Portfolio aus der DB und speichert die Rohdaten im Objekt
	 *
	 * @return BOOLEAN
	 */
	public function getStocklist()
	{
		if($this->_dataStocksFetched == true)
			return true;
			
		if(!$this->getId())
			return false;

		$rows = $this->_getPortfolioTransactionModel()->getPortfolio($this->getId());

		$this->_portfolioStocksData = $rows;
		
		$this->_count = count($this->_portfolioStocksData);
		
		$this->_dataStocksFetched = true;
		
		return $this;
	}
		
	public function getCurrentPortfolio()
	{
		if($this->_portfolioDaten)
			return $this->_portfolioDaten;
		/*
		 * SELECT tid,`portfolio_id`,`company_id`, sum(anzahl) as realcount 
		 * FROM `portfolio_transactions` Group by `company_id` having realcount > 0
		 */
		if(!$this->getId())
			return false;
					
		$basisdaten = $this->_getPortfolioTransactionModel()->getPortfolio($this->getId());
		
		if($basisdaten->count() > 0)
		{
			$basisdaten = $basisdaten->toArray();
/*
			//Alle Transactions holen
			$select = $model->select()
						->where("`portfolio_id` = ?", $this->getId())
						->where("type = ?", 1)
						->order("date DESC");
			$transactions = $model->fetchAll($select);
	*/		
			$stockexm = new StockexchangesModel();
			$stockexchs = $stockexm->fetchAll();
			
			foreach ($basisdaten as $key => $stock)
			{
				//den richtigen Markt durch Währung finden
				
				$company = new Company(null, array(
        									"company_id" => $stock["company_id"], 
        									"name" => $stock["company_name"], 
        									"isin" => $stock["isin"], 
        									"type" => $stock["company_type"],
        									"main_market" => $stock["main_market"],
        									"picture_id" => $stock["picture_id"]
        		));
				
				$basisdaten[$key]["companyMainMarketId"] = $company->getMainMarketId();
				$basisdaten[$key]["companyName"] = $company->getName();
				$basisdaten[$key]["companyIsin"] = $company->getISIN();
				
				$basisdaten[$key]["schlussKurs"] = null;
				$basisdaten[$key]["schlussWert"] = null;
				$basisdaten[$key]["date"] = time();
				
				//markt suchen
				foreach ($stockexchs as $stockexch)
				{
					if($basisdaten[$key]["schlussKurs"] === null || $stockexch->market_id === $company->getMainMarketId())
					{
						if($company->getQuotes($stockexch->market_id) && 
							$company->getQuotes($stockexch->market_id)->getCurrency() == $this->getCurrency())
						{
							//Daten für Depot-Wert-Berechnung
							$basisdaten[$key]["schlussKurs"] = $company->getQuotes($stockexch->market_id)->getLastQuote()->getClose();
							$basisdaten[$key]["schlussWert"] = $company->getQuotes($stockexch->market_id)->getLastQuote()->getClose()*$stock["realcount"];
							$basisdaten[$key]["date"] = $company->getQuotes($stockexch->market_id)->getLastQuote()->getDate(true);
							
							//Daten für Indikatoren und Chart
							if($company->getMainMarketId())
								$basisdaten[$key]["quotesObject"] = $company->getQuotes($company->getMainMarketId());
						}				
					}	
				}
				
				
				$basisdaten[$key]["price"] = $basisdaten[$key]["schlussKurs"];
				$basisdaten[$key]["gebuehren"] = 0;
				$basisdaten[$key]["anzahl"] = $basisdaten[$key]["realcount"];
				
				$values = array(
				"portfolio_id" => $stock["portfolio_id"],
				"company_id" => $stock["company_id"],
				"date" => time(),
				"anzahl" => -$basisdaten[$key]["realcount"],
				"price" => $basisdaten[$key]["price"],
				"gebuehren"	=> $basisdaten[$key]["gebuehren"],
				"type" => 1					
				);
				//echo "START";
				$transaction = new Portfolio_Transaction(null, $values);
				//echo "STOP";
				$basisdaten[$key]["einstandsWert"] = $transaction->getEinstandsWert();
				$basisdaten[$key]["einstandsKurs"] = $basisdaten[$key]["einstandsWert"] / $stock["realcount"];
				
				$basisdaten[$key]["ertragWert"] = $transaction->getErtragWert();
				$basisdaten[$key]["ertragProzent"] = $transaction->getErtragProzent();
				
				$basisdaten[$key]["aktienWert"] = $transaction->getAktienWert();				
				$basisdaten[$key]["gesamtWert"] = $transaction->getGesamtWert();
				
			}
					
		}
		
		$this->_portfolioDaten = $basisdaten;
		
		return $basisdaten;
	}
	/**
	 * Gibt den Depotwert zurück
	 *
	 * @return Double
	 */
	public function getPortfolioValue()
	{
		$summe = 0;
		$portfolio = $this->getCurrentPortfolio();
		
		foreach ($portfolio as $stock)
		{
			$summe += $stock["gesamtWert"];
		}
		
		return $summe;
	}
	
	
	
	
	
	/**
	 * PortfolioModel
	 *
	 * @return PortfolioModel
	 */
	protected function _getPortfolioModel()
	{
		if($this->_PortfolioModel instanceof PortfolioModel)
			return $this->_PortfolioModel;
		else
		{
			$this->_PortfolioModel = new PortfolioModel();
			return $this->_PortfolioModel;
		}	
	}
	/**
	 * PortfolioTransactionsModel
	 *
	 * @return PortfolioTransactionsModel
	 */
	protected function _getPortfolioTransactionModel()
	{
		if($this->_PortfolioTransactionsModel instanceof PortfolioTransactionsModel)
			return $this->_PortfolioTransactionsModel;
		else
		{
			$this->_PortfolioTransactionsModel = new PortfolioTransactionsModel();
			return $this->_PortfolioTransactionsModel;
		}	
	}
	
	
	
		/* IMPLEMENTS */
	
   /**
     * Rewind the Iterator to the first element.
     * Similar to the reset() function for arrays in PHP.
     * Required by interface Iterator.
     *
     * @return Portfolio Fluent interface.
     */
    public function rewind()
    {
        $this->_pointer = 0;
        return $this;
    }

    /**
     * Return the current element.
     * Similar to the current() function for arrays in PHP
     * Required by interface Iterator.
     *
     * @return Quotes current element from the collection
     */
    public function current()
    {
        if ($this->valid() === false) {
            return null;
        }

        // do we already have a row object for this position?
        if (empty($this->_portfolio[$this->_pointer])) {
        	
        	$row = $this->_portfolioStocksData->seek($this->_pointer)->current();
        	
        	$company = new Company(null, array(
        									"company_id" => $row->company_id, 
        									"name" => $row->company_name, 
        									"isin" => $row->isin, 
        									"type" => $row->company_type,
        									"main_market" => $row->main_market,
        									"picture_id" => $row->picture_id
        	));
			
			$market = new Market($row->main_market);
						
            $this->_portfolio[$this->_pointer] = new Quotes($company, $market);
        }

        // return the row object
        return $this->_portfolio[$this->_pointer];
    }

    /**
     * Return the identifying key of the current element.
     * Similar to the key() function for arrays in PHP.
     * Required by interface Iterator.
     *
     * @return int
     */
    public function key()
    {
        return $this->_pointer;
    }

    /**
     * Move forward to next element.
     * Similar to the next() function for arrays in PHP.
     * Required by interface Iterator.
     *
     * @return void
     */
    public function next()
    {
        ++$this->_pointer;
    }

    /**
     * Check if there is a current element after calls to rewind() or next().
     * Used to check if we've iterated to the end of the collection.
     * Required by interface Iterator.
     *
     * @return bool False if there's nothing more to iterate over
     */
    public function valid()
    {
        return $this->_pointer < $this->_count;
    }

    /**
     * Returns the number of elements in the collection.
     *
     * Implements Countable::count()
     *
     * @return int
     */
    public function count()
    {
    	if(!$this->_dataStocksFetched)
    		$this->_getStocks();
        return $this->_count;
    }
    
    /**
     * Take the Iterator to position $position
     * Required by interface SeekableIterator.
     *
     * @param int $position the position to seek to
     * @return Portfolio
     * @throws Zend_Exception
     */
    public function seek($position)
    {
        $position = (int) $position;
        if ($position < 0 || $position > $this->_count) {
            throw new Zend_Exception("Illegal index $position");
        }
        $this->_pointer = $position;
        return $this;        
    }	
	
	/* IMPLEMENTS END */
}

?>