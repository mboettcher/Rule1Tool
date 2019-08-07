<?php

class Portfolio_Transaction extends Abstraction {

	protected $_tid;	
	protected $_portfolio_id ;	
	protected $_company_id; 	
	protected $_price; 	
	protected $_anzahl; 	
	protected $_gebuehren; 	
	protected $_date; 
	protected $_type;

	protected $_aktienWert;
	protected $_gesamtWert;
	
	protected $_ertragWert;
	protected $_ertragProzent;
	
	protected $_einstandsWert;
		
	protected $_integrity = true;
	
	public function __construct($tid = null, $values = null)
	{
		if($tid !== null)
			$this->_tid = $tid;
		if($values !== null)
			$this->getTransaction(null, $values);
	}
	public function getTransaction($tid = null, $values = null)
	{
		if($tid !== null)
			$this->_tid = $tid;
			
		if($values == null)
		{
			$model = new PortfolioTransactionsModel();
			$select = $model->select()
						->where("tid = ?", $this->_tid)
						;
			$row = $model->fetchRow($select);
			
			$this->_tid = $row->tid;	
			$this->_portfolio_id = $row->portfolio_id;
			$this->_company_id = $row->company_id;
			$this->_price = $row->price;
			$this->_anzahl = $row->anzahl;
			$this->_gebuehren = $row->gebuehren;
			$this->_date = $row->date;	
			$this->_type = $row->type;				
		}
		else 
		{
			foreach ($values as $key => $value)
			{
				$tmp = "_".$key;
				$this->$tmp = $value;
			}
		}
		
		if($this->_type == 1)
		{
			if($this->_anzahl > 0)
			{
				//BUY	
				$this->_type = "BUY";
				
				$this->_aktienWert = $this->_anzahl*$this->_price;
				$this->_gesamtWert = $this->_anzahl*$this->_price+$this->_gebuehren;
			}
			elseif($this->_anzahl < 0)
			{
				//SALE
				$this->_type = "SALE";
				
				$this->_aktienWert = -$this->_anzahl*$this->_price;
				$this->_gesamtWert = -$this->_anzahl*$this->_price-$this->_gebuehren;
							
				if($this->_getEinstandsWert())
				{
					$this->_ertragWert = $this->_gesamtWert - $this->_einstandsWert;
					$this->_ertragProzent = $this->_ertragWert / $this->_einstandsWert;			
				}
			}
		}
		elseif ($this->_type == 2)
		{
			//Dividendenzahlung
			$this->_type = "DIVIDENDE";
			
			$this->_aktienWert = $this->_anzahl*$this->_price;
			$this->_gesamtWert = $this->_anzahl*$this->_price-$this->_gebuehren;
			
			$this->_ertragWert = $this->_gesamtWert;
			$this->_ertragProzent = false;
		}
			

	}
	
	public function add($values)
	{
		if($values["type"] == "sell")
			$values["anzahl"] = -$values["anzahl"];
		
		if($values["type"] == "sell" || $values["type"] == "buy")
			$type = 1;
		elseif($values["type"] == "dividende")
			$type = 2;
		else 
			$type = 1;
					
		if($values = $this->_validate($values, "add"))
		{
			//print_r($values);exit;
			$model = new PortfolioTransactionsModel();
			$row = $model->createRow();
			$row->company_id = $values["company_id"];
			$row->portfolio_id = $values["portfolio_id"];
			$row->price = $values["price"];

			$zdate = new Zend_Date($values["date"]." ".$values["time"],"yyyy-MM-dd HH:mm");
			$date = $zdate->getTimestamp();

			$m = new PortfolioTransactionsModel();
			$daterow = false;
			
			do
			{
				$select = $m->select()->where("company_id = ?", $row->company_id)
							->where("portfolio_id = ?", $row->portfolio_id)
							->where("date = ?", $date);
				if($daterow = $m->fetchRow($select))
					$date = $date + 60; //Gleiche Datums verhindern, daher plus 1 Minute
			}while($daterow);

			$row->date = $date;

			$row->anzahl = $values["anzahl"];
			$row->gebuehren = $values["gebuehren"];				
				
			$row->type = $type;	

			return $row->save();
		}
		else 
			return false;

	}
	public function edit($values)
	{
		if($values["type"] == "sell")
			$values["anzahl"] = -$values["anzahl"];
			
		if($values = $this->_validate($values, "edit"))
		{
			$model = new PortfolioTransactionsModel();
			$rows = $model->find($this->_tid);
			
			
			if($rows->count() > 0)
			{
				$row = $rows->current();
				
				if(isset($values["date"]) || isset($values["time"]))
				{
					$zdate = new Zend_Date($values["date"]." ".$values["time"],"yyyy-MM-dd HH:mm");
					$date = $zdate->getTimestamp();
		
					$m = new PortfolioTransactionsModel();
					$daterow = false;
					
					do
					{
						$select = $m->select()->where("company_id = ?", $values["company_id"])
									->where("portfolio_id = ?", $values["portfolio_id"])
									->where("date = ?", $date)
									->where("tid != ?", $this->_tid);
						if($daterow = $m->fetchRow($select))
							$date = $date + 60; //Gleiche Datums verhindern, daher plus 1 Minute
					}while($daterow);
		
					$values["date"] = $date;
			
					unset($values["time"]);
				}
							
				foreach ($values as $key => $value)
					$row->$key = $value;
				
				return $row->save();
			}
			else
				return false;			
		}
		else 
			return false;		
	}
	public function delete()
	{
		$model = new PortfolioTransactionsModel();
		$rows = $model->find($this->_tid);
		if($rows->count() > 0)
		{
			$row = $rows->current();
			$this->_getMessageBox()->setMessage("MSG_PORTFOLIO_002");
			return $row->delete();
		}
		else
		{
			$this->_getMessageBox()->setMessage("MSG_PORTFOLIO_003");
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
	protected function _validate($data, $modus)
	{
		/*
		 * 			$row->company_id = $values["company_id"];
			$row->portfolio_id = $values["portfolio_id"];
			$row->price = $values["price"];
			$row->date = $values["date"];
			$row->anzahl = $values["anzahl"];
			$row->gebuehren = $values["gebuehren"];	
		 */
		//Filters
		$filters = array(
			'*' => array('StringTrim','StripTags'),
			'price' => array(new Filter_LocaleFloat()),
			'gebuehren' => array(new Filter_LocaleFloat())
		);
 		//Validators
 		if($modus == "add")
 		{
 			$validators = array(
 				'company_id' =>  array(new Validate_CompanyId(), 'presence' => 'required'),
 				'portfolio_id' =>  array(new Validate_PortfolioId(), 'presence' => 'required'),
			    'price' => array(new Validate_LocaleFloat(), 'presence' => 'required'),
 				'date' => array(new Zend_Validate_Date("yyyy-MM-dd"), 'presence' => 'required'),
 				'time' => array(new Zend_Validate_Date("HH:mm"), 'presence' => 'required'),
 				'anzahl' => array(new Zend_Validate_Int(), 'presence' => 'required'),
 				'gebuehren' => array(new Validate_LocaleFloat(), 'presence' => 'required')
            );
 		}
 		else
 		{
 			$validators = array(
			    'company_id' =>  array(new Validate_CompanyId(), 'presence' => 'required'),
 				'portfolio_id' =>  array(new Validate_PortfolioId(), 'presence' => 'required'),
 			    'price' => array(new Validate_LocaleFloat(), 'presence' => 'required'),
 				'date' => array(new Zend_Validate_Date("yyyy-MM-dd"), 'presence' => 'required'),
 				'time' => array(new Zend_Validate_Date("HH:mm"), 'presence' => 'required'),
 				'anzahl' => array(new Zend_Validate_Int(), 'presence' => 'required'),
 				'gebuehren' => array(new Validate_LocaleFloat(), 'presence' => 'required')
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
	
	protected function _getEinstandsWert()
	{
		//Muss ein SALE sein
		if($this->_anzahl > 0)
			return false;
			
		//Anzahl weiteren Aktien im Depot holen
		$model = new PortfolioTransactionsModel();
		$select = $model->select()
					->from($model)
					->where("`portfolio_id` = ?", $this->_portfolio_id)
					->where("`company_id` = ?", $this->_company_id)
					->where("date < ?", $this->_date)
					->where("type = ?", 1)
					->columns("sum(anzahl) as realcount")
					->group('company_id')
					;
		$row = $model->fetchRow($select);
		
		if($row)
		{
			// $numberToIgnor Aktientrades müssen wegen FIFO ignoriert werden
			$numberToIgnor = $row->realcount+$this->_anzahl;
			//echo "<br>".$row->realcount." ".$this->_anzahl;
			if($numberToIgnor < 0)
			{
				//Verkauft mehr als er hat ;)
				$this->_integrity = false;
			}
			
			//echo "numtoignor: $numberToIgnor";
			$select = $model->select()
					->where("`portfolio_id` = ?", $this->_portfolio_id)
					->where("`company_id` = ?", $this->_company_id)
					->where("type = ?", 1)
					->where("anzahl > 0") //Nur BUYs
					->where("date < ?", $this->_date)
					->order("date DESC")
					;
			$rows = $model->fetchAll($select);
			
			$anzahlAktienSoll = -$this->_anzahl; // ACHTUNG POSITIV wirds
			$anzahlAktienHaben = 0;
			
			$wertEinstand = 0;
			
			foreach ($rows as $transaction)
			{
				if($anzahlAktienHaben < $anzahlAktienSoll)
				{
					if($numberToIgnor > 0)
					{
						//erst ignorieren, dann nehmen
						//1. Trade komplett ignorieren
						if($numberToIgnor >= $transaction->anzahl)
						{
							//$numberToIgnor wird später entsprechend verringert und ist dann >= 0
						}
						else //2. Trade teilweise ignorieren
						{						
							if($transaction->anzahl-$numberToIgnor >= $anzahlAktienSoll - $anzahlAktienHaben)
							{
								//nur einen Teil des Trades nehmen, nämlich genau den noch benötigten Teil
								$stocksToTake = $anzahlAktienSoll - $anzahlAktienHaben;
							}
							else 
							{
								//Trade reicht nicht um Verkauf plausibel zu machen (ggf kommt mit sicherheit noch ein Buy hier nach)
								//kompletten Trade abzüglich NumberToIgnor, quasi alles was da ist
								$stocksToTake = $transaction->anzahl-$numberToIgnor;
							}
							
							$divAnzahlCount = $stocksToTake / $transaction->anzahl; //nur 0,x-Prozent sind übrig
							$partiellegebuehren = $divAnzahlCount*$transaction->gebuehren;
							$wertEinstand += $stocksToTake  * $transaction->price + $partiellegebuehren;
							
							$anzahlAktienHaben += $stocksToTake;
							//$numberToIgnor wird anschließend negativ
						}
						$numberToIgnor = $numberToIgnor-$transaction->anzahl;
						
					}
					else 
					{
						//Nehmen was da ist
						
						//1. Trade komplett zählen
						if($transaction->anzahl <= $anzahlAktienSoll - $anzahlAktienHaben)
						{
							//Wenn alle Aktien aus Trade verwendet werden sollen
							$wertEinstand += $transaction->anzahl*$transaction->price+$transaction->gebuehren;
							
							$anzahlAktienHaben += $transaction->anzahl;
						}
						else //2. Trade teilweise zählen
						{
							//nur ein Teil der Aktien wird verwendet
							$divAnzahlCount = ($anzahlAktienSoll - $anzahlAktienHaben) / $transaction->anzahl; //nur 0,x-Prozent sind übrig
							$partiellegebuehren = $divAnzahlCount*$transaction->gebuehren;
							$wertEinstand += ($anzahlAktienSoll - $anzahlAktienHaben)  * $transaction->price + $partiellegebuehren;
							
							$anzahlAktienHaben += $anzahlAktienSoll - $anzahlAktienHaben; // $anzahlAktienHaben = $anzahlAktienSoll
						}
					}
				}
				else 
				{
					break;
				}	
			}
			//echo "e: ".$wertEinstand."; ";
			//$ertragWert = $wertSchluss - $wertEinstand;
			//$ertragProzent = ($wertSchluss - $wertEinstand) / $wertEinstand;
			
			$this->_einstandsWert = $wertEinstand;
			
			if($anzahlAktienHaben != $anzahlAktienSoll)
				$this->_integrity = false;
			return true;
		}
		else 
		{
			//kann passieren wenn alle Transaktion auf gleichen Timestamp fallen
			// oder Transaktion zeitlich in der Zukunft bezogen auf den letzten Kurs liegen
			$this->_integrity = false;
			return false;
		}	
		
	}

	
	public function getGebuehren()
	{
		return $this->_gebuehren;
	}
	public function getAktienWert()
	{
		return $this->_aktienWert;
	}
	public function getGesamtWert()
	{
		return $this->_gesamtWert;
	}
	public function getErtragWert()
	{
		return $this->_ertragWert;
	}
	public function getErtragProzent()
	{
		return $this->_ertragProzent;
	}
	public function getType()
	{
		return $this->_type;
	}
	public function getAnzahl()
	{
		if($this->_anzahl < 0)
			return -$this->_anzahl;
		else 
			return $this->_anzahl;
	}
	public function getEinstandsWert()
	{
		return $this->_einstandsWert;
	}
	public function getIntegrity()
	{
		return $this->_integrity;
	}
	
}

?>