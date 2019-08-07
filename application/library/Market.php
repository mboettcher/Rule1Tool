<?php

class Market extends Abstraction
{
	protected $id = null;
	protected $name = null;
	protected $currency = null;
	protected $start_time = null;
	protected $end_time = null;
	protected $local = null;
	protected $symbol_extension = null;
	
	public function __construct($market_id)
	{
		$this->id = $market_id;
	}
	/**
	 * Holt Daten aus DB oder Cache
	 *
	 * @return Market|FALSE
	 */
	protected function _getData()
	{
		//Prüfen ob bereits gecachet
		$cache = Zend_Registry::get('Zend_Cache_Core'); 
		
		$cache_id = "omarket_".$this->id;
		
		// Nachsehen, ob der Cache bereits existiert:
        if($row = $cache->load($cache_id)) {
            // Cache hit! Zeile aus Cache verwenden
            $this->name = $row->name;
    		$this->local = $row->countrycode; 	
			$this->symbol_extension = $row->symbolextension; 	
			$this->start_time = $row->time_start;
			$this->end_time = $row->time_end;
			$this->currency = $row->currency;
			
			return $this;
        } else {
            //Cache miss; Zeile aus DB holen
     		//Market_id prüfen und Daten holen
    		$model = new StockexchangesModel();
    		$select = $model->select()->where("market_id = ?", $this->id);
    		$row = $model->fetchRow($select);
    		if($row)
    		{	
    			//Daten in Cache speichern!
    			$cache->save($row, $cache_id);
    			
    			$this->name = $row->name;
    			$this->local = $row->countrycode; 	
    			$this->symbol_extension = $row->symbolextension; 	
    			$this->start_time = $row->time_start;
    			$this->end_time = $row->time_end;
    			$this->currency = $row->currency;
    
    			return $this;
    		}
    		else
    			throw new Zend_Exception("Ungültige Market-ID");       
        }
	}
	public function getName()
	{
		if($this->name === null)
			$this->_getData();
		return $this->name;
	}
	public function getCurrency()
	{
		if($this->currency === null)
			$this->_getData();	
		return $this->currency;
	}
	public function getStartTime()
	{
		if($this->start_time === null)
			$this->_getData();	
		return $this->start_time;
	}
	public function getEndTime()
	{
		if($this->end_time === null)
			$this->_getData();	
		return $this->end_time;
	}
	public function getLocal()
	{
		if($this->local === null)
			$this->_getData();	
		return $this->local;
	}
	public function getId()
	{
		if($this->id === null)
			throw new Zend_Exception("Objekt nicht richtig initialisiert - Daten nicht vorhanden");
		return $this->id;
	}
        public function isInit()
        {
            if($this->id === null)
                return false;
            else
                return true;
        }
}