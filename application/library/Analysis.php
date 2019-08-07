<?php

class Analysis extends Abstraction
{
    public $data_fetched = false;
	//Eigenschaften
	protected $analysis_id = null;
	protected $company_id = null;
	protected $user_id = null;
	/**
	 * User-Objekt
	 *
	 * @var User
	 */
	protected $creator = null;
	
	protected $moat;
	protected $management;
	
	protected $date_add;
	protected $date_edit;
	protected $note;
	protected $date_delete;
	protected $delete_by;
	
	protected $private;
	
	protected $currency;
	
	/**
	 * Thread-Objekt
	 *
	 * @var Group_Thread
	 */
	protected $_thread;
	protected $_thread_id;
	
	public 	$rate_of_return = 15;
	
	public $my_estimated_growth_testvalue = null; //kann von jedem User gesetzt werden, um veränderungen schnell zu testen
	public $my_future_kgv_testvalue = null; //kann von jedem User gesetzt werden, um veränderungen schnell zu testen
	public $my_eps_testvalue = null; //kann von jedem User gesetzt werden, um veränderungen schnell zu testen
	
	
	public $negativ_kgv_handle = "zero"; // zero // minus // plus // ignore
	protected $data = array(
							"cashflow_av_1" => null,
							"cashflow_av_5" => null,
							"cashflow_av_9" => null, 
							"cashflow_rate" => array(),
							"cashflow" => array(),
							"eps_av_1" => null,
							"eps_av_5" => null, 							
							"eps_av_9" => null,
							"eps_rate" => array(),
							"eps" => array(),
							"equity_av_1" => null,
							"equity_av_5" => null,
							"equity_av_9" => null,
							"equity_rate" => array(), 
							"equity" => array(),
							"revenue_av_1" => null,
							"revenue_av_5" => null, 
							"revenue_av_9" => null,
							"revenue_rate" => array(),
							"revenue" => array(), 
							"depts" => array(),
							"income_after_tax" => array(),
							"kgv" => array(),
							"kgv_av_1" => null,
							"kgv_av_5" => null,
							"kgv_av_10" => null,
							"roic" => array(),
							"roic_av_1" => null,
							"roic_av_5" => null,
							"roic_av_10" => null,
							"current_eps" => null,
							"historical_growth" => null,
							"historical_kgv" => null,
							"analysts_estimated_growth" => null,
							"my_estimated_growth" => null,
							"my_future_kgv" => null,
							"rule1_growth" => null,
							"future_eps" => null,
							"future_kgv" => null,
							"future_price" => null,
							"future_stickerprice" => null,
							"mos_price" => null,
							"paybacktime_mos" => null
							); 

	/**
	 * AnalysisModel-Objekt
	 *
	 * @var AnalysisModel
	 */						
	protected $_AnalysisModel;
	/**
	 * KeydataanalysesdataModel-Objekt
	 *
	 * @var KeydataanalysesdataModel
	 */
	protected $_KeydataanalysesdataModel;
	
	//Methoden
	public function __construct($analysis_id = null)
	{
		if($analysis_id !== null)
			$this->setAnalysisId($analysis_id);

	}
	/**
	 * Setzt die AnalysisId
	 *
	 * @param INT $analysis_id
	 */
	protected function setAnalysisId($analysis_id)
	{
		$this->analysis_id = $analysis_id;
	}
	/**
	 * Holt die Analyse anhand der ID
	 *
	 * @param INT $analysis_id
	 * @return _getAnalysis
	 */
	public function getAnalysisById($analysis_id = null)
	{
		if($analysis_id !== null)
			$this->setAnalysisId($analysis_id);
			
		return $this->_getAnalysis();

	}
	/**
	 * Analyse holen initialisieren
	 *
	 * @return Analysis|BOOLEAN
	 */
	protected function _getAnalysis()
	{
		if($return = $this->getRawData())
		{
			return $this;
		}
		else
			return false;		
	}
	/**
	 * Holt die Roh-Daten aus der DB
	 *
	 * @return BOOLEAN
	 */
	protected function getRawData()
	{
		$this->_isInit();
		
		$kd_basics_result = $this->_getKeydataanalysesModel()->getAllDataByAnalysisId($this->getId());	
		if($kd_basics_result)
		{
			//Daten ins Objekt schreiben
			$this->company_id = $kd_basics_result->company_id;
			$this->user_id = $kd_basics_result->user_id;
			$this->date_add = $kd_basics_result->date_add;
			$this->date_edit = $kd_basics_result->date_edit;
			$this->note = $kd_basics_result->note;
			$this->date_delete = $kd_basics_result->date_delete;
			$this->delete_by = $kd_basics_result->delete_by;
			$this->_thread_id = $kd_basics_result->thread_id;
			$this->private = $kd_basics_result->private;
			$this->currency = $kd_basics_result->currency;
			
			$this->moat = $kd_basics_result->moat;
			$this->management = $kd_basics_result->management;
			
			$this->data["analysts_estimated_growth"] = $kd_basics_result->analysts_estimated_growth;
			$this->data["current_eps"] = $kd_basics_result->current_eps;
						
		    $this->data["my_estimated_growth"] = $kd_basics_result->my_estimated_growth;
		    $this->data["my_future_kgv"] = $kd_basics_result->my_future_kgv;
		
			//Kennzahlen holen	
			$results = $this->_getKeydataanalysesdataModel()->getAllDataByAnalysisId($this->getId());
			$data = false;
			foreach($results as $result)
			{
	    		$data["roic"][] = $result->roic;
	    		$data["equity"][] = $result->equity;
	    		$data["eps"][] = $result->eps;
    			$data["depts"][] = $result->depts;
	    		$data["revenue"][] = $result->revenue;
	    		$data["income_after_tax"][] = $result->income_after_tax;
	    		$data["cashflow"][] = $result->cashflow;
	    		$data["kgv"][] = $result->kgv;
	    		$data["year"][] = $result->year;
				/*if($result->kgv != null)
	    		{
	    			if($this->negativ_kgv_handle == "zero")
	    			{
	    				if($result->kgv < 0)
	    					$data["kgv"][] = 0;
	    				else
	    					$data["kgv"][] = $result->kgv;
	    			}
	    			else if($this->negativ_kgv_handle == "plus")
	    			{
	    				if($result->kgv < 0)
	    					$data["kgv"][] = -$result->kgv;
	    				else
	    					$data["kgv"][] = $result->kgv;
	    			}
	    			else if($this->negativ_kgv_handle == "ignore")
	    			{	
	    				if($result->kgv >= 0)
	    					$data["kgv"][] = $result->kgv;	
	    			}
	    			else //minus //btw: ein minus kgv gibt es NICHT!!!
	    				$data["kgv"][] = $result->kgv;
	    		}*/
			}
			
			//$this->data["roic"] = $data["roic"];
	    	$this->data["equity"] = $data["equity"];
	    	$this->data["eps"] = $data["eps"];
	    	$this->data["depts"] = $data["depts"];
	    	$this->data["revenue"] = $data["revenue"];
	    	$this->data["income_after_tax"] = $data["income_after_tax"];
	    	$this->data["cashflow"] = $data["cashflow"];
	    	$this->data["kgv"] = $data["kgv"];
	    	$this->data["year"] = $data["year"];
			if($data)
			{
			    $this->data_fetched = true;
				return true;
			}
			else
				return false;		
		}
		else
		{
			$this->_getMessageBox()->setMessage("MSG_ANALYSIS_001", $this->getId());
			return false;
		}
	}
	/**
	 * Gibt einen bestimmten Jahres-Wert zurück
	 *
	 * @param STRING $part
	 * @param INT $year
	 * @param BOOLEAN $localit
	 * @return DOUBLE|STRING|NULL
	 */
	public function getDataNumber($part, $year, $localit = true)
	{
	    if(isset($this->data[$part][$year]))
	        if($localit)
	            return $this->toNumber($this->data[$part][$year]);
	        else 
	            return $this->data[$part][$year];
	    else
	        return null;
	}
    /**
     * Gibt einen Array zurück, der passend für das Form ist
     *
     * @return ARRAY
     */
	public function getFormArray()
	{
	    if($this->data_fetched !== true)
	    	$this->_getAnalysis();
	    
		$array = array();
		$array["basicdata"] = array();
		$array["keydata"] = array();
		
		$array["basicdata"]["analysis_id"] = $this->analysis_id;
		$array["basicdata"]["company_id"] = $this->company_id;
		$array["basicdata"]["note"] = $this->note;
		$array["basicdata"]["analysts_estimated_growth"] = $this->toNumber($this->data["analysts_estimated_growth"]);
		$array["basicdata"]["current_eps"] = $this->toNumber($this->data["current_eps"]);
		$array["basicdata"]["my_estimated_growth"] = $this->toNumber($this->data["my_estimated_growth"]);
		$array["basicdata"]["my_future_kgv"] = $this->toNumber($this->data["my_future_kgv"]);

		$array["basicdata"]["moat"] = $this->moat;
		$array["basicdata"]["management"] = $this->management;
		
		$array["basicdata"]["private"] = $this->isPrivate();
		
		$array["basicdata"]["currency"] = $this->currency;
		
		$i = 1;
		foreach($this->data["equity"] as $key => $equity)
		{
    		$array["keydata"][$i]["year"] = $this->data["year"][$key];
		    $array["keydata"][$i]["equity"] = $this->toNumber($this->data["equity"][$key]);
    		$array["keydata"][$i]["eps"] = $this->toNumber($this->data["eps"][$key]);
    		$array["keydata"][$i]["revenue"] = $this->toNumber($this->data["revenue"][$key]);
    		$array["keydata"][$i]["depts"] = $this->toNumber($this->data["depts"][$key]);
    		$array["keydata"][$i]["income_after_tax"] = $this->toNumber($this->data["income_after_tax"][$key]);
    		$array["keydata"][$i]["cashflow"] = $this->toNumber($this->data["cashflow"][$key]);
    		$array["keydata"][$i]["kgv"] = $this->toNumber($this->data["kgv"][$key]);
    		$i++;
		}
		//print_r($array);
		return $array;
		
	}
	/**
	 * Wandelt eine PC-Zahl in eine Menschen-Zahl um, gibt bei NULL auch NULL wieder zurück
	 *
	 * @param FLOAT $value
	 * @param INT $precision
	 * @return FLOAT|NULL
	 */
	protected function toNumber($value, $precision = 2, $printZero = false)
	{
		$_toNumberOptions = array('locale' => Zend_Registry::get("Zend_Locale"), 'precision' => $precision);
		
		if($printZero && ($value === NULL || $value === false))
        	$value = 0;
		
	    if($value !== null && $value !== false)
	        return Zend_Locale_Format::toFloat($value, $_toNumberOptions);
	    else
	        return $value;
	}
	
	
	/**
	 * Eine neue Analyse speichern
	 *
	 * @param ARRAY $newData
	 * @return Analysis
	 */
	public function setNewAnalysis($newData)
	{
		//Daten sollte bereits durch Form validiert sein
		if($data = $this->validateData($newData))
		{
			//Transaktion beginnen
			Zend_Registry::get('Zend_Db')->beginTransaction();

			try{
				//Wenn keine User-ID angegeben, dann aktuellen Nutzer verwenden
				if(!isset($data["basicdata"]["user_id"]) || !$data["basicdata"]["user_id"])
					$data["basicdata"]["user_id"] = Zend_Registry::get("UserObject")->getId();
					
				//Thread erstellen
				$data["basicdata"]["thread_id"] = $this->_createAnalysisThread($data["basicdata"]["company_id"]);
				
				//Basisdaten einfügen und die ID in den Daten-Array packen
			  	$data["basicdata"]["analysis_id"] = $this->_insertBasicData($data);	
				$this->analysis_id = $data["basicdata"]["analysis_id"];
			  	
			  	//Einfügen der einzelnen Datensätze
			  	$this->_insertKeydatas($data);
				
			  	//Transaktion erfolgreich abschließen - Daten speichern
		  		Zend_Registry::get('Zend_Db')->commit();	
			  
			} catch(Zend_Exception $e)
			{
				//Transaktion rückgängig machen
				Zend_Registry::get('Zend_Db')->rollBack();
				throw new Zend_Exception($e->getMessage(). "\n" .  $e->getTraceAsString());
			}
			  			  
		}
		//fluent Interface
		return $this;
	}
	/**
	 * Eine bestehende Analyse bearbeiten
	 *
	 * @param ARRAY $newData
	 * @return Analysis
	 */
	public function editAnalysis($newData)
	{
		if($data = $this->validateData($newData))
		{
			//Transaktion beginnen
			Zend_Registry::get('Zend_Db')->beginTransaction();
			
			try{
				//Stammdaten updaten
				$this->_updateBasicData($data);
				
				//Einfügen der einzelnen Datensätze
				$this->_insertKeydatas($data);
				
				//Transaktion erfolgreich abschließen - Daten speichern
				Zend_Registry::get('Zend_Db')->commit();	
			} catch(Zend_Exception $e)
			{
				//Transaktion rückgängig machen
				Zend_Registry::get('Zend_Db')->rollBack();
				throw new Zend_Exception($e->getMessage(). "\n" .  $e->getTraceAsString());
			}
		}
		
		//fluent Interface
		return $this;
	}
	/**
	 * Prüft und filtert die Daten
	 *
	 * @param ARRAY $data
	 * @return ARRAY
	 */
	protected function validateData($data)
	{
		//Input sollte durch Form bereits gefiltert und geprüft sein
		
		$numberfilter = new Filter_LocaleFloat();
	
		//print_r($data);
		//Jahreszahlen auf durchgängigkeit überprüfen
		$i = 0;
		foreach($data["keydata"] as $key => $keydata)
		{
			if($i == 0)
			{
				$year = $data["keydata"][$key]["year"];
				if($year+2 < date("Y")) //führt dazu, dass Analysedaten maximal zwei Jahre alt sein dürfen
					$year = date("Y");
			}
			//Jahreszahl setzen
			$data["keydata"][$key]["year"] = $year-$i;
			//Zahlenwerte normalisieren
			$data["keydata"][$key]["income_after_tax"] = $numberfilter->filter($data["keydata"][$key]["income_after_tax"]);
                        $data["keydata"][$key]["revenue"] = $numberfilter->filter($data["keydata"][$key]["revenue"]);
                        $data["keydata"][$key]["equity"] = $numberfilter->filter($data["keydata"][$key]["equity"]);
                        $data["keydata"][$key]["eps"] = $numberfilter->filter($data["keydata"][$key]["eps"]);
                        $data["keydata"][$key]["cashflow"] = $numberfilter->filter($data["keydata"][$key]["cashflow"]);
                        $data["keydata"][$key]["depts"] = $numberfilter->filter($data["keydata"][$key]["depts"]);
                        $data["keydata"][$key]["kgv"] = $numberfilter->filter($data["keydata"][$key]["kgv"]);
			
			$i++;
		}
		//Zahlenwerte normalisieren
		$data["basicdata"]["analysts_estimated_growth"] = $numberfilter->filter($data["basicdata"]["analysts_estimated_growth"]);
		$data["basicdata"]["current_eps"] = $numberfilter->filter($data["basicdata"]["current_eps"]);
		$data["basicdata"]["my_estimated_growth"] = $numberfilter->filter($data["basicdata"]["my_estimated_growth"]);
		$data["basicdata"]["my_future_kgv"] = $numberfilter->filter($data["basicdata"]["my_future_kgv"]);	
		
		return $data;	
	}
	/*
	protected function _getInputFilter()
	{
		$numberformatfilter = new Filter_LocaleFloat();
		//Filters
		$filters = array(
				    '*' => array('StringTrim','StripTags')
				);
		return $filters;	
	}
	protected function _getInputValidators()
	{
		$localFloat = new Validate_LocaleFloat;
			//Validators
		$validators = array(
			'analysis_id' => array('Int','presence' => 'optional'),
			'company_id' => array(new Validate_CompanyId(), 'presence' => 'required'),
			'user_id' => array(new Validate_UserId(), 'presence' => 'optional'),
			'note' => array(new Zend_Validate_StringLength(0,255),'allowEmpty' => true, 'presence' => 'required')
		);
		return $validators;
	}
*/
	/**
	 * Fügt die Jahres-Daten in die DB ein
	 *
	 * @param ARRAY $data
	 * @return BOOLEAN
	 */
	protected function _insertKeydatas($data)
	{
		//erstmal löschen um dann wieder geänderte hinzuzufügen
		$this->_getKeydataanalysesdataModel()->delete(
			$this->_getKeydataanalysesdataModel()
					->getAdapter()
					->quoteInto("analysis_id = ?", $data["basicdata"]["analysis_id"])); 
		
		foreach($data["keydata"] as $keydata)
		{
		    if(empty($keydata["equity"]) && !is_numeric($keydata["equity"]))
		        $keydata["equity"] = NULL;
		    if(empty($keydata["depts"]) && !is_numeric($keydata["depts"]))
		        $keydata["depts"] = NULL;
		    if(empty($keydata["revenue"]) && !is_numeric($keydata["revenue"]))
		        $keydata["revenue"] = NULL;
		    if(empty($keydata["eps"]) && !is_numeric($keydata["eps"]))
		        $keydata["eps"] = NULL;
		    if(empty($keydata["income_after_tax"]) && !is_numeric($keydata["income_after_tax"]))
		        $keydata["income_after_tax"] = NULL;
		    if(empty($keydata["cashflow"]) && !is_numeric($keydata["cashflow"]))
		        $keydata["cashflow"] = NULL;
		    if(empty($keydata["kgv"]) && !is_numeric($keydata["kgv"]))
		        $keydata["kgv"] = NULL;
		        
	  		$insert = $this->_getKeydataanalysesdataModel()->insert(array(
	  										"analysis_id" => $data["basicdata"]["analysis_id"], 
	  										"year" => $keydata["year"], 
	  										"equity" => $keydata["equity"], 
			    							"depts" => $keydata["depts"], 
			    							"revenue" => $keydata["revenue"], 
			    							"eps" => $keydata["eps"], 
			    							"income_after_tax" => $keydata["income_after_tax"], 
			    							"cashflow" => $keydata["cashflow"], 
			    							"kgv" => $keydata["kgv"]
	  										));	
	  		
	  		if(!$insert)
	  			throw new Exception('Konnte Daten nicht speichern.');
		
		}
	  	return true;
	
	}
	/**
	 * Updated die Basis-Daten
	 *
	 * @param ARRAY $data
	 * @return INT
	 */
	protected function _updateBasicData($data)
	{
		if(empty($data["basicdata"]["my_estimated_growth"]) && !is_numeric($data["basicdata"]["my_estimated_growth"]))
		        $data["basicdata"]["my_estimated_growth"] = NULL;
		if(empty($data["basicdata"]["my_future_kgv"]) && !is_numeric($data["basicdata"]["my_future_kgv"]))
		        $data["basicdata"]["my_future_kgv"] = NULL;
		if(empty($data["basicdata"]["analysts_estimated_growth"]) && !is_numeric($data["basicdata"]["analysts_estimated_growth"]))
		        $data["basicdata"]["analysts_estimated_growth"] = NULL;
		        
		$needles = array(
						//"user_id",
						"note",
						"moat",
						"management",
						"analysts_estimated_growth",
						"current_eps",
						"my_estimated_growth",
						"my_future_kgv",
						"private",
						"currency"
					);        
		$row = $this->_getKeydataanalysesModel()->find($this->getId())->current();
					
		foreach ($needles as $needle)
		{
			$row->$needle = $data["basicdata"][$needle]; 
		}
 
		$update = $row->save();
	  	return $update;
	}
	/**
	 * Fügt Basis-Daten in die Db ein
	 *
	 * @param ARRAY $data
	 * @return INT
	 */
	protected function _insertBasicData($data)
	{
		if(empty($data["basicdata"]["my_estimated_growth"]) && !is_numeric($data["basicdata"]["my_estimated_growth"]))
		        $data["basicdata"]["my_estimated_growth"] = NULL;
		if(empty($data["basicdata"]["my_future_kgv"]) && !is_numeric($data["basicdata"]["my_future_kgv"]))
		        $data["basicdata"]["my_future_kgv"] = NULL;
		if(empty($data["basicdata"]["analysts_estimated_growth"]) && !is_numeric($data["basicdata"]["analysts_estimated_growth"]))
		        $data["basicdata"]["analysts_estimated_growth"] = NULL;
		        
		$analysis_id = $this->_getKeydataanalysesModel()->insert(array(
  			"company_id" => $data["basicdata"]["company_id"], 
  			"user_id" => $data["basicdata"]["user_id"], 
			"note" => $data["basicdata"]["note"], 
			"moat" => $data["basicdata"]["moat"], 
			"management" => $data["basicdata"]["management"], 
			"analysts_estimated_growth" => $data["basicdata"]["analysts_estimated_growth"], 
			"current_eps" => $data["basicdata"]["current_eps"],
  			'my_estimated_growth' => $data["basicdata"]["my_estimated_growth"],
			'my_future_kgv' => $data["basicdata"]["my_future_kgv"],
			'thread_id' => $data["basicdata"]["thread_id"],
			'private' => $data["basicdata"]["private"],
			'currency' => $data["basicdata"]["currency"]
  			));
  		if($analysis_id)
  			return $analysis_id;
  		else
			throw new Exception('Konnte neue Analyse nicht speichern.'); 
	}
	
	/****************************************************************************************************************************************
	 * Group-Function
	 */
	/**
	 * Gibt den passenden Thread zurück (Multilingua)
	 *
	 * @return Group_Thread
	 */
	public function getThread()
	{
   		if(!($this->_thread instanceof Group_Thread))
   			$this->_thread = new Group_Thread($this->getThreadId());
    	return $this->_thread;
	}
	
	/**
	 * Erstellt einen Thread in der UnternehmensGruppe
	 *
	 * @param INT $company_id
	 * @return INT ThreadId
	 */
	protected function _createAnalysisThread($company_id)
	{
		//GRUPPE suchen
		$company = new Company($company_id);
		$group_id = $company->getGroupId();

		//Thread erstellen (Kommentar-Thread)
		$thread_data = array("founder_id" => 3, //System 
					"title" => NULL, 
		//$this->_getTranslate()->translate("Analyse von %1\$s vom %2\$s", $username, $date->get(Zend_Date::DATES, $language["locale"]), $language["short"])
					"type" => 3, //Analysis-Comment-Thread
		            "language" => NULL //multi-lingua
		);
		
		$group = new Group($group_id);
		$thread_id = $group->createThread($thread_data)->getThreadId();
	
		return $thread_id;
	}
	/**
	 * Prüft ob Objekt initiiert wurde
	 *
	 */
	protected function _isInit()
	{
		if($this->analysis_id === null)
			throw new Zend_Exception("Objekt nicht richtig initialisiert");
	}
	/**
	 * Holt daten...
	 *
	 */
	protected function _init()
	{
		$this->_isInit();
		
		if(!$this->data_fetched)
			$this->getAnalysisById();
	}
	/**
	 * Gibt die Analysis-ID zurück
	 *
	 * @return INT
	 */
	public function getId()
	{
		$this->_isInit();
		return $this->analysis_id;
	}
	/**
	 * Gibt das Add-Date zurück
	 *
	 * @return INT
	 */
	public function getDateAdd()
	{
		$this->_init();
	    return $this->date_add;
	}
	/**
	 * Gibt das Edit-Date zurück
	 *
	 * @return INT
	 */
    public function getDateEdit()
	{
		$this->_init();
	    return $this->date_edit;
	}
	/**
	 * Gibt ein User-Objekt vom Ersteller zurück
	 *
	 * @return User
	 */
	public function getCreator()
	{
		$this->_init();
	    if(!($this->creator instanceof User))
			$this->creator = new User($this->user_id);
	    return $this->creator;
	}
	/**
	 * Setzt das eigene EstimatedGrowth für Testzwecke
	 *
	 * @param DOUBLE|STRING $value
	 */
	public function setMyEstimatedGrowthTestvalue($value)
	{
		 $numberfilter = new Filter_LocaleFloat();
		 $value = $numberfilter->filter($value);
		 $this->my_estimated_growth_testvalue = $value;
	}
	/**
	 * Setzt das eigene Future-Kgv für Testzwecke
	 *
	 * @param DOUBLE|STRING $value
	 */
	public function setMyFutureKgvTestvalue($value)
	{
		 $numberfilter = new Filter_LocaleFloat();
		 $value = $numberfilter->filter($value);
		 $this->my_future_kgv_testvalue = $value;
	}	
	/**
	 * Setzt das eigene EPS für Testzwecke
	 *
	 * @param DOUBLE|STRING $value
	 */
	public function setMyEpsTestvalue($value)
	{
		 $numberfilter = new Filter_LocaleFloat();
		 $value = $numberfilter->filter($value);
		 $this->my_eps_testvalue = $value;
	}	
	/**
	 * Gibt die Notiz zurück
	 *
	 * @return STRING
	 */
	public function getNote()
	{
		$this->_init();
		return $this->note;
	}
	/**
	 * Gibt den Burggraben zurück
	 *
	 * @return STRING
	 */
	public function getMoat()
	{
		$this->_init();
		return $this->moat;
	}
	/**
	 * Gibt das Management-Feld zurück
	 *
	 * @return STRING
	 */
	public function getManagement()
	{
		$this->_init();
		return $this->management;
	}
	/**
	 * Gibt die CompanyId zurück
	 *
	 * @return INT
	 */
	public function getCompanyId()
	{
		$this->_init();
		return $this->company_id;
	}
	/**
	 * Gibt die ThreadId zurück
	 *
	 * @return INT
	 */
	public function getThreadId()
	{
		$this->_init();
		return $this->_thread_id;
	}
	
	/**
	 * Wenn Analyse Private dann TRUE
	 *
	 * @return BOOLEAN
	 */
	public function isPrivate()
	{
		$this->_init();
		return $this->private;
	}
	/**
	 * Gibt die Währung zurück
	 *
	 * @return STRING
	 */
	public function getCurrency()
	{
		$this->_init();
		return $this->currency;
	}
	/* ************* Models **************** */
	
	/**
	 * AnalysisModel
	 *
	 * @return AnalysisModel
	 */
	protected function _getAnalysisModel()
	{
		if(!($this->_AnalysisModel instanceof AnalysisModel))
			$this->_AnalysisModel = new AnalysisModel();

		return $this->_AnalysisModel;
	}
	
	/**
	 * AnalysisModel
	 *
	 * @return AnalysisModel
	 */
	protected function _getKeydataanalysesModel()
	{
		return $this->_getAnalysisModel();
	}
	/**
	 * KeydataanalysesdataModel
	 *
	 * @return KeydataanalysesdataModel
	 */
	protected function _getKeydataanalysesdataModel()
	{
		if(!($this->_KeydataanalysesdataModel instanceof KeydataanalysesdataModel))
			$this->_KeydataanalysesdataModel = new KeydataanalysesdataModel();

		return $this->_KeydataanalysesdataModel;		
	}
}