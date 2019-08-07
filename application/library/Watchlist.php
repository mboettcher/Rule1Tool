<?php
/*
 * Watchlist
 */

class Watchlist extends Abstraction implements SeekableIterator, Countable
{
	protected $_watchlist_id = null;
	protected $_name = null;
	protected $_owner_id = null;
	
	protected $_date_add;
	protected $_date_edit;

	protected $_date_delete;
	protected $_delete_by;
	
	protected $_send_signal_mail;
	
	/**
	 * WatchlistItems
	 *
	 * @var ARRAY
	 */
	protected $_watchlist = array();
	
	/**
	 * WatchlistRowSet
	 *
	 * @var Zend_Db_Table_Rowset_Abstract
	 */
	protected $_watchlistData = null;
	
	/**
	 * WatchlistModel
	 *
	 * @var WatchlistModel
	 */
	protected $_WatchlistModel = null;
	/**
	 * WatchlistCompaniesModel
	 *
	 * @var WatchlistCompaniesModel
	 */
	protected $_WatchlistCompaniesModel = null;
	
	protected $_dataFetched = false;
	protected $_dataStocksFetched = false;
	

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
     * Constructor
     *
     * @param INT $watchlist_id
     * @param ARRAY $data
     */
	public function __construct($watchlist_id = null, $data = null)
	{
		if($watchlist_id != null)
			$this->setWatchlist($watchlist_id);
		if($data != null)
			$this->_setObjectData($data);
	}
	/**
	 * Setzt die Watchlist-ID
	 *
	 * @param INT $watchlist_id
	 */
	public function setWatchlist($watchlist_id)
	{
		if($this->_watchlist_id !== null)
			$this->_clear();
		$this->_watchlist_id = $watchlist_id;
	}
	/**
	 * Setzt die Objekt-Werte zurück
	 *
	 */
	protected function _clear()
	{
		$this->_watchlist_id = null;
		$this->_name = null;
		$this->_owner_id = null;
		
		$this->_date_add = null;
		$this->_date_edit = null;
		
		$this->_date_delete = null;
		$this->_delete_by = null;
		
		$this->_watchlist = array();
		$this->_watchlistData = null;
	}
	/**
	 * Erstellt eine neue Watchlist
	 *
	 * @param ARRAY $data mit "name" und "owner_id"
	 * @param ARRAY $stocks
	 * @return BOOLEAN|Watchlist
	 */
	public function create($data, $stocks = array())
	{
		Zend_Registry::get('Zend_Db')->beginTransaction();

		try {
			if($data = $this->validateData($data, "add"))
			{
				$insert = $this->_getWatchlistModel()->insert($data);
				if($insert)
				{
					$this->_watchlist_id = $insert;
					
					foreach($stocks as $stock)
					{
						$this->addStock($stock["company_id"], $stock["market_id"]);
					}
										
					$this->_getMessageBox()->setMessage("MSG_WATCHLIST_003");
					
					// Wenn alle erfolgreich waren, übertrage die Transaktion und alle Änderungen werden auf einmal übermittelt
					Zend_Registry::get('Zend_Db')->commit();
			
					return $this;
				}
				else 
				{
					$this->_getMessageBox()->setMessage("MSG_WATCHLIST_004");
					return false;
				}
					
			}
			else
			{
				//Messages bereits durch Validierung gesetzt
				return false;
			}
		
					
		} catch (Zend_Exception $e) {
		    // So werden alle Änderungen auf einmal übermittelt oder keine.
		    Zend_Registry::get('Zend_Db')->rollBack();
		    throw new Zend_Exception($e->getMessage(). "\n" .  $e->getTraceAsString());
		}
	}
	/**
	 * Prüft und speichert die Daten
	 *
	 * @param ARRAY $data
	 * @return Watchlist|BOOLEAN
	 */
	public function edit($data)
	{
		if($data = $this->validateData($data, "edit"))
		{
			$row = $this->_getWatchlistModel()->find($this->getWatchlistId())->current();
			
			if(isset($data["name"]))
				$row->name = $data["name"];
			if(isset($data["owner_id"]))
				$row->owner_id = $data["owner_id"];
			if(isset($data["send_signal_mail"]))
				$row->send_signal_mail = $data["send_signal_mail"];
				
			$update = $row->save();
			
			if($update)
			{					
				$this->_getMessageBox()->setMessage("MSG_WATCHLIST_009");
				
				$this->setWatchlist($this->getWatchlistId());
				
				return $this;
			}
			else
			{
    			$this->_getMessageBox()->setMessage("MSG_WATCHLIST_011");
    			return false;
    		}	
		}
		else
		{
			//Messages bereits durch Validierung gesetzt
			return false;
		}	
	}
	/**
	 * Löscht die aktuelle Watchlist
	 *
	 * @return BOOLEAN
	 */
	public function delete()
	{
		//name holen, gleich ist er weg ;)
		$name = $this->getName();
		$delete = $this->_getWatchlistModel()->find($this->getWatchlistId())->current()->delete();
    	if($delete > 0)
    	{
    		$this->_getMessageBox()->setMessage("MSG_WATCHLIST_007", $name);
    		$this->_clear();
    		return true;
    	}
    	else
    	{
    		$this->_getMessageBox()->setMessage("MSG_WATCHLIST_008", $name);
    		return false;
    	}
	}
	/**
	 * Fügt eine Aktie der Watchlist hinzu
	 *
	 * @param INT $company_id
	 * @param INT $market_id
	 * @return BOOLEAN
	 */
	public function addStock($company_id, $market_id)
	{
		$this->_isInit();
		
		$company = new Company($company_id);
		
		//Prüfen ob nicht bereits schon auf Watchlist
		$rows = $this->_getWatchlistCompaniesModel()->find($this->getWatchlistId(), $company_id);
		if(count($rows) > 0)
		{
			//Bereits vorhanden
			$this->_getMessageBox()->setMessage("MSG_WATCHLIST_014", $company->getName());
			return false;
		}
		
		$data = array("watchlist_id" => $this->getWatchlistId(), "company_id" => $company_id, "market_id" => $market_id);
		$insert = $this->_getWatchlistCompaniesModel()->insert($data);
		if($insert)
		{
			$this->_getMessageBox()->setMessage("MSG_WATCHLIST_005", $company->getName());
			return true;
		}
		else
		{
			$this->_getMessageBox()->setMessage("MSG_WATCHLIST_006", $company->getName());
			return false;
		}
			
	}
	/**
	 * Löscht Aktie von Watchlist
	 *
	 * @param INT $company_id
	 * @return BOOLEAN
	 */
	public function removeStock($company_id)
	{
		$this->_isInit();
		
		$row = $this->_getWatchlistCompaniesModel()->find($this->getWatchlistId(), $company_id)->current();
		if($row)
			$delete = $row->delete();
		else
			$delete = false;
		
		if($delete)
		{
			$company = new Company($company_id);
			$this->_getMessageBox()->setMessage("MSG_WATCHLIST_001", $company->getName());
			return true;					
		}
		else
		{
			$this->_getMessageBox()->setMessage("MSG_WATCHLIST_002");
			return false;
		}
	}
	/**
	 * Holt die Liste der Aktien
	 *
	 * @return Watchlist
	 */
	public function getStocklist()
	{
		$this->_getStocks();
		return $this;	
	}
	/**
	 * Gibt die Basisdaten als Objekt zurück
	 *
	 * @return Object
	 */
	protected function _getBasics()
	{
		$this->_isInit();
		
		if($this->_dataFetched == true)
			return true;
		
		$rows = $this->_getWatchlistModel()->find($this->getWatchlistId());
		if(count($rows) > 0)
		{
			$row = $rows->current();
			
			$this->_setObjectData($row->toArray());
			
		}
		else
		{
			throw new Zend_Exception("Ungültige Watchlist-ID: ".$this->getWatchlistId());
		}
	}
	/**
	 * Holt die Daten der Watchlist und prüft damit ob ID gültig
	 *
	 * @return BOOLEAN
	 */
	public function isWatchlist()
	{
		try {
			$this->_getBasics();
			return true;
		}
		catch (Zend_Exception $e) {
		 
			//Zend_Registry::get('Zend_Log')->log($e->getMessage(). "\n" .  $e->getTraceAsString(), Zend_Log::NOTICE);
		    return false;
		}	
	}
	/**
	 * Setzt die Objekt-Werte
	 *
	 * @param ARRAY $data
	 */
	protected function _setObjectData($data)
	{
		$needles = array(
					"watchlist_id",
					"name",
					"owner_id",
					"date_add",
					"date_edit",
					"date_delete",
					"delete_by",
					"count",
					"send_signal_mail"
				);
		if(!isset($data["watchlist_id"]))
			throw new Zend_Exception("WatchlistId nicht angegeben");

		foreach ($needles as $needle)
		{
			if(isset($data[$needle]))
			{
				$var = "_".$needle;
				$this->$var = $data[$needle];
			}
		}
		
		$this->_dataFetched = true;
		
		return true;
		
	}
	/**
	 * Holt die Watchlist aus der DB und speichert die Rohdaten im Objekt
	 *
	 * @return BOOLEAN
	 */
	protected function _getStocks()
	{
		if($this->_dataStocksFetched == true)
			return true;
			
		$this->_isInit();

		$rows = $this->_getWatchlistCompaniesModel()->getWatchlist($this->getWatchlistId());

		$this->_watchlistData = $rows;
		
		$this->_count = count($this->_watchlistData);
		
		$this->_dataStocksFetched = true;
		
		return true;
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
				'owner_id' =>  array(new Validate_UserId(), 'presence' => 'required'),
 				'send_signal_mail' => array(new Zend_Validate_InArray(array(0,1)))
            );
 		}
 		else
 		{
 			$validators = array(
			    'name' => array(new Zend_Validate_StringLength(1,35), 'presence' => 'optional'),
				'owner_id' =>  array(new Validate_UserId(), 'presence' => 'optional'),
 				'send_signal_mail' => array(new Zend_Validate_InArray(array(0,1)), 'presence' => 'optional')
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

	
	
	/**
	 * Gibt die WatchlistId zurück
	 *
	 * @return INT
	 */
	public function getWatchlistId()
	{
		$this->_isInit();
		return $this->_watchlist_id;
	}
	/**
	 * Gibt den Namen der Watchlist zurück
	 *
	 * @return STRING
	 */
	public function getName()
	{
		$this->_getBasics();
		return $this->_name;
	}
	/**
	 * Gibt die owner_id zurück
	 *
	 * @return INT
	 */
	public function getOwnerId()
	{
		$this->_getBasics();
		return $this->_owner_id;
	}
	/**
	 * Gibt den Owner als User_Object zurück
	 *
	 * @return User
	 */
	public function getOwner()
	{
		return new User($this->getOwnerId());
	}
	/**
	 * Gibt date_add zurück
	 *
	 * @return INT
	 */
	public function getDateAdd()
	{
		$this->_getBasics();
		return $this->_date_add;
	}
	/**
	 * Gibt date_edit zurück
	 *
	 * @return INT
	 */
	public function getDateEdit()
	{
		$this->_getBasics();
		return $this->_date_edit;
	}
	/**
	 * Gibt date_delete zurück
	 *
	 * @return INT
	 */
	public function getDateDelete()
	{
		$this->_getBasics();
		return $this->_date_delete;
	}
	/**
	 * Gibt delete_by zurück
	 *
	 * @return INT
	 */
	public function getDeletorId()
	{
		$this->_getBasics();
		return $this->_delete_by;
	}
	
	/**
	 * Alias of getSendSignalMail()
	 *
	 * @return INT
	 */
	public function sendSignalMail()
	{
		$this->_getBasics();
		return $this->_send_signal_mail;
	}
	
	public function getSendSignalMail()
	{
		return $this->sendSignalMail();
	}
	
	/* IMPLEMENTS */
	
   /**
     * Rewind the Iterator to the first element.
     * Similar to the reset() function for arrays in PHP.
     * Required by interface Iterator.
     *
     * @return Watchlist Fluent interface.
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
        if (empty($this->_watchlist[$this->_pointer])) {
        	
        	$row = $this->_watchlistData->seek($this->_pointer)->current();
        	
        	$company = new Company(null, array(
        									"company_id" => $row->company_id, 
        									"name" => $row->company_name, 
        									"isin" => $row->isin, 
        									"type" => $row->type,
        									"main_market" => $row->main_market,
        									"picture_id" => $row->picture_id
        	));
			
			$market = new Market($row->market_id);
						
            $this->_watchlist[$this->_pointer] = new Quotes($company, $market);
            
            //CompanyObject das Quotes-Object überhelfen
            $company->setQuotes($this->_watchlist[$this->_pointer]);
        }

        // return the row object
        return $this->_watchlist[$this->_pointer];
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
     * @return Watchlist
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
	
    /**
     * Gibt die Watchlist als Array zurück, isin, name, close, change
     *
     * @return ARRAY
     */
    public function toArray()
    {
    	$arr = array();
    	$i = 0;
    	foreach ($this as $item)
    	{
    		$arr[$i]["isin"] = $item->getCompany()->getIsin();
    		$arr[$i]["name"] = $item->getCompany()->getName();
    		
    		if($item->getLastQuote()->getClose() !== null)
			{
				$arr[$i]["close"] = $item->getLastQuote()->getClose(true);
				$arr[$i]["change"] = $item->getLastQuote()->getChange(true);
				$arr[$i]["changeNumber"] = $item->getLastQuote()->getChange(false)*1000; //Ganzzahl draus machen				
			}		
			else
			{
				$arr[$i]["close"] = "n/a";
				$arr[$i]["change"] = "n/a";
				$arr[$i]["changeNumber"] = "n/a";
			}
			
			$i++;
    	}
    	return $arr;
    }
    

	/**
	 * Pürft ob WatchlistId bereits gesetzt
	 *
	 */
	protected function _isInit()
	{
		if($this->_watchlist_id === null || $this->_watchlist_id < 1)
			throw new Zend_Exception("Objekt noch nicht initialisiert");
	}
	/**
	 * WatchlistCompaniesModel
	 *
	 * @return WatchlistCompaniesModel
	 */
	protected function _getWatchlistCompaniesModel()
	{
		if($this->_WatchlistCompaniesModel instanceof WatchlistCompaniesModel)
			return $this->_WatchlistCompaniesModel;
		else
		{
			$this->_WatchlistCompaniesModel = new WatchlistCompaniesModel();
			return $this->_WatchlistCompaniesModel;
		}	
	}
	/**
	 * WatchlistModel
	 *
	 * @return WatchlistModel
	 */
	protected function _getWatchlistModel()
	{
		if($this->_WatchlistModel instanceof WatchlistModel)
			return $this->_WatchlistModel;
		else
		{
			$this->_WatchlistModel = new WatchlistModel();
			return $this->_WatchlistModel;
		}	
	}
}