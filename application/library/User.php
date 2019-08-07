<?php
/*
 * User
 */

class User extends Abstraction
{
	protected $_user_id = null;
	protected $_nickname = "guest"; //also called username
	protected $_lastname = null;
	protected $_firstname = null;
	protected $_email = null;
	protected $_newsletter = null;
	protected $_status;
	protected $_reg_date;
	
	protected $_image_id_s;
	protected $_image_id_m;
	
	protected  $_indikator_sma = null;
	
	protected $_passwordNew;
	
	
	protected $_useRealname = false;

	protected $_role = "guest";
	
	protected $_invitations = 0;
	
	protected $_dataRest = array();
	
	protected $_dataFetched = false;
	
	protected $_UsersModel; //UsersModel Object
	/**
	 * Watchlist_Set
	 *
	 * @var Watchlist_Set
	 */
	protected $_watchlists;
	
	/**
	 * Portfolio_Set
	 *
	 * @var Portfolio_Set
	 */
	protected $_portfolios;
	
	protected $_indikators = null;
	protected $_UserIndikatorsModel;
	
	/**
	 * Neues User-Objekt erzeugen
	 *
	 * @param INT $user_id
	 * @param ARRAY $data
	 */
	public function __construct($user_id = null, $data = null)
	{
        if($user_id !== null){
        	$this->setUserId($user_id);
        	$this->getUser($this->getUserId());
        }
        if($data !== null)
        	$this->_setData($data);
	}
	/**
	 * Setzt die User-Id
	 *
	 * @param INT $user_id
	 */
	public function setUserId($user_id)
	{
		$this->_user_id = $user_id;
	}
	/**
	 * Setzt die Objekt-Werte anhand eines Datenarray
	 *
	 * @param ARRAY $data
	 * @return TRUE
	 */
	protected function _setData($data)
	{
		if(isset($data["user_id"]))
		{
			$this->_user_id = $data["user_id"];
			unset($data["user_id"]);
		}
		if(isset($data["nickname"]))
		{
			$this->_nickname = $data["nickname"];
			unset($data["nickname"]);
		}
		if(isset($data["lastname"]))
		{
			$this->_lastname = $data["lastname"];
			unset($data["lastname"]);
		}
		if(isset($data["firstname"]))
		{
			$this->_firstname = $data["firstname"];
			unset($data["firstname"]);
		}
		if(isset($data["email"]))
		{
			$this->_email = $data["email"];
			unset($data["email"]);
		}
		if(isset($data["newsletter"]))
		{
			$this->_newsletter = $data["newsletter"];
			unset($data["newsletter"]);
		}
		if(isset($data["role"]))
		{
			$this->_role = $data["role"];
			unset($data["role"]);
		}
		if(isset($data["invitations"]))
		{
			$this->_invitations = $data["invitations"];
			unset($data["invitations"]);
		}
		if(isset($data["use_realname"]))
		{
			$this->_useRealname = $data["use_realname"];
			unset($data["use_realname"]);
		}
		if(isset($data["status"]))
		{
			$this->_status = $data["status"];
			unset($data["status"]);
		}
		if(isset($data["reg_date"]))
		{
			$this->_reg_date = $data["reg_date"];
			unset($data["reg_date"]);
		}
		if(isset($data["image_id_s"]))
		{
			$this->_image_id_s = $data["image_id_s"];
			unset($data["image_id_s"]);
		}
		if(isset($data["image_id_m"]))
		{
			$this->_image_id_m = $data["image_id_m"];
			unset($data["image_id_m"]);
		}
		if(isset($data["indikator_sma"]))
		{
			$this->_indikator_sma = $data["indikator_sma"];
			unset($data["indikator_sma"]);
		}
		
		
		$this->_dataRest = $data;
		$this->_dataFetched = true;
		return true;
	}

	/**
	 * Holt die Userdaten zum Suchobjekt
	 * 
	 * @param Int|String User-ID, E-Mail oder Username
	 * 
	 * @return User|boolean Wenn der Nutzer gefunden wurde, dann wird das Object zurückgegeben, ansonsten FALSE
	 */
	public function getUser($needle)
	{
		$rows = $this->_getUsersModel()->find($needle);
		if(count($rows) == 0)
		{
			$row = $this->_getUsersModel()->findByNickname($needle);
			if(!$row)
			{
				$row = $this->_getUsersModel()->findByEmail($needle);
			}
		}
		else 
			$row = $rows->current();
		
		if($row)
		{
			$this->_setData($row->toArray());
			
			return $this;
		}
		else
		{
			//keinen Nutzer gefunden
			$this->_getMessageBox()->setMessage('MSG_USER_001', $needle);
			return false;
		}
	}
	/**
	 * Erstellt einen neuen Nutzer
	 *
	 * @param ARRAY $data
	 * @return BOOLEAN
	 */
	public function newUser($data)
	{
		$this->_getMessageBox()->clear();
		
		if($data_org = $this->validateData($data, "add"))
		{
			//Transaktion starten
			Zend_Registry::get('Zend_Db')->beginTransaction();
	
			try
			{
			
				$data = array("nickname" => $data_org["nickname"], 
								"password" => md5($data_org["password"]), 
								"email" => $data_org["email"], 
								"newsletter" => $data_org["newsletter"],
								"invitations" => 5							
								);
				//einfügen
				$insert = $this->_getUsersModel()->insert($data);
				if($insert){
					
					//Einladungen prüfen und entfernen
				    if(isset($data_org["invitation"]))
       				{
       					$invite_tbl = new InvitationsModel();
       					$where[] = $invite_tbl->getAdapter()->quoteInto("`key` = ?", $data_org["invitation"]);
       					$where[] = $invite_tbl->getAdapter()->quoteInto("date_reg is NULL", NULL);
       													
       					$update = $invite_tbl->update(array("date_reg" => time(), "invited" => $insert), $where);
       					if($update == 0)
       						throw new Zend_Exception("Einladung nicht mehr gültig. Anmeldung abgebrochen!");
       				}
       				
       				//UserId an Mail übergeben
       				$data["user_id"] = $insert;
       				
       				//Registrierungsdaten speichern
       				//Aktivierungs-Key erstellen
       				$reg_key = md5(time()."xXx".rand());
       				$reg_tbl = new RegistrationsModel();
       				$reg_tbl->insert(array("user_id" => $insert, "ip" => $_SERVER["REMOTE_ADDR"], "activationkey" => $reg_key));

       				//Aktivierungskey an Mail übergeben
       				$data["activationkey"] = $reg_key;
       				
					$mail = new Mail($data["email"], null, Zend_Registry::get("config")->general->mail->from->default->email);
					$mail->sendRegistrationMail($data);
			
					$this->getUser($insert);

					// Wenn alle erfolgreich waren, übertrage die Transaktion und alle Änderungen werden auf einmal übermittelt
	    			Zend_Registry::get('Zend_Db')->commit();		
       		  		
	    			$this->_getMessageBox()->setMessage("MSG_USER_006", $data["email"]);
	    			
					return true;
				}
				else
				{
					throw new Zend_Exception("Ein Fehler ist aufgetreten. Der Nutzer konnte nicht angelegt werden.");
				}

			
			
			} catch (Zend_Exception $e) {
		    	// Rollback!
		    	Zend_Registry::get('Zend_Db')->rollBack();
		    	throw new Zend_Exception($e->getMessage(). "\n" .  $e->getTraceAsString());
			}
		
								
		}
		else
			return false;

	}
	/**
	 * Prüft und speichert die geänderten Werte
	 *
	 * @param ARRAY $data
	 * @return BOOLEAN
	 */
	public function edit($data)
	{
		$this->_getMessageBox()->clear();

		//Leere Elemente rausfiltern
		foreach ($data as $key => $value)
		{
			if($value == "")
				unset($data[$key]);
		}
		
		//Validierte Daten holen
		$data = $this->validateData($data, "edit");

		if($data)
		{
			//Passwort verschlüssel
			if(isset($data["password"]))
				$data["password"] = md5($data["password"]);
			
			//Auf aktualisierte Daten filtern
			$newData = array();
			//Alte Daten holen
			$tbl = new UsersModel();
			$row = $tbl->find($this->getUserId())->current()->toArray();
			//Alte und neue Abgleichen
			foreach ($row as $key => $value)
			{
				if(isset($data[$key]) && ($data[$key] != ""))
				{
					if($value != $data[$key])
						$newData[$key] = $data[$key];
				}
			}
		
			//Gibts überhaupt was zum Updaten?
			if(!count($newData))
			{
				$this->_getMessageBox()->setMessage("MSG_USER_009");
				return true; //Nothing left to do here
			}
			
			//Transaktion starten
			Zend_Registry::get('Zend_Db')->beginTransaction();
			try
			{
       			//updaten
       			$where = $this->_getUsersModel()->getAdapter()->quoteInto("user_id = ?", $this->getUserId());
       			$update = $this->_getUsersModel()->update($newData, $where);
       			if($update){
       				
       				$this->getUser($this->getUserId());
       				
       				$this->_getMessageBox()->setMessage("MSG_USER_007");
       				
       				
       				// Wenn alle erfolgreich waren, übertrage die Transaktion und alle Änderungen werden auf einmal übermittelt
	    			Zend_Registry::get('Zend_Db')->commit();		
       			
       				if(Zend_Registry::get('Zend_Auth')->hasIdentity() && $this->getUserId() == Zend_Registry::get("UserObject")->getUserId())
       				{
       					//UserObject erneuern
						Zend_Registry::get("UserObject")->getUser(Zend_Registry::get("UserObject")->getUserId());						
       				}
	    			
	    			return true;
       			}
       			else
       			{
       				throw new Zend_Exception("Ein Fehler ist aufgetreten. Der Nutzer konnte nicht bearbeitet werden.");
       			}
        		
			
			} catch (Zend_Exception $e) {
		    	// Rollback!
		    	Zend_Registry::get('Zend_Db')->rollBack();
		    	throw new Zend_Exception($e->getMessage(). "\n" .  $e->getTraceAsString());
			}	
			
        	        	
		
		}
		else
			return false;

	}
	/**
	 * Prüft die Daten und gibt die gefilterten Daten zurück
	 *
	 * @param ARRAY $data
	 * @param STRING $modus add or edit
	 * @return ARRAY|FALSE
	 */
	protected function validateData($data, $modus)
	{
		//Filters
		$filters = array('*' => array('StringTrim','StripTags')	);
 		//Validators
 		if($modus == "add")
 		{
 			$agb_vali = new Validate_IsTrue();
 			$agb_vali->setMessage("Bitte bestätigen Sie die Allgemeinen Geschäftsbedingungen.", Validate_IsTrue::NOT_TRUE);
 			$validators = array(
			    'nickname' => array(new Validate_NicknameInput(), 'presence' => 'required'),
				'password' => array(new Zend_Validate_StringLength(6,25), 'presence' => 'required'),
				'password_confirm' => array(new Validate_CompareToField(),
				Zend_Filter_Input::FIELDS => array('password', 'password_confirm'),
 			 'presence' => 'required'),

				'email' => array(new Validate_EmailAddressInput(), 'presence' => 'required'),
	 			//'agb' => array($agb_vali, 'presence' => 'required') 	,
				'newsletter' => array(new Zend_Validate_NotEmpty(),  'presence' => 'required')		
            );
            if(Zend_Registry::get("config")->general->invitations->active == true)
        	{
        		$validators["invitation"] = array(new Validate_Invitation(), 'presence' => 'required');
        	}
 		}
 		else
 		{
 			$validators = array(
			
 				'nickname' => array(new Validate_NicknameInput($this->getUserId()), 'presence' => 'optional'),
 			
				'password' => array(new Zend_Validate_StringLength(6,25), 'presence' => 'optional'),
				'password_confirm' => array(new Validate_CompareToField(),
											Zend_Filter_Input::FIELDS => array('password', 'password_confirm'),
 																		 'presence' => 'optional'),

				'email' => array(new Validate_EmailAddressInput($this->getUserId()), 'presence' => 'optional'),
			
				'firstname' => array(new Zend_Validate_Alnum(true), array('StringLength', 2, 50), 'presence' => 'optional'),
				'lastname' => array(new Zend_Validate_Alnum(true), array('StringLength', 2, 50), 'presence' => 'optional'),
 				
 				'use_realname' => array(new Zend_Validate_NotEmpty(), 'presence' => 'optional'),
				
				'newsletter' => array(new Zend_Validate_NotEmpty(), 'presence' => 'optional'),
 			
 				'invitations' => array(new Zend_Validate_Int(), 'presence' => 'optional'),
 			
 				'status' => array(new Zend_Validate_Int(), 'presence' => 'optional'),
 				'image_id_s' => array(new Zend_Validate_Int(), 'presence' => 'optional'),
 				'image_id_m' => array(new Zend_Validate_Int(), 'presence' => 'optional'),
				'indikator_sma' => array(new Zend_Validate_InArray(array(10,30,50)))
            );	 		
 		}

		
		//Filter_Input starten
		$input = new Zend_Filter_Input($filters, $validators);
		$input->setDefaultEscapeFilter(new Filter_HtmlSpecialChars());
		//Daten laden
		$input->setData($data);
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
	 * Setzt ein neues Kennwort und sendet es per Mail
	 *
	 * @return USER|BOOLEAN 
	 */
	public function resetPassword($email)
	{
		$this->_isInit();
		//Transaktion starten
		Zend_Registry::get('Zend_Db')->beginTransaction();
	
		try
		{
			//Neues PW generieren
			$newpw = substr(md5(rand()."xXx".time().$this->_user_id), 0, 10); //
			
			$row = $this->_getUsersModel()->find($this->getId())->current();
			$row->password = md5($newpw); // PW setzen
			$update = $row->save();	//Speichern
			
        	if($update)
        	{
        		//E-Mail senden
     			$mail = new Mail($email);
				$sendmail = $mail->sendResetPasswordMail(array("password" => $newpw, "username" => $this->getNickname()));
        		if($sendmail)
           			$this->_getMessageBox()->setMessage("MSG_USER_008", $this->getEmail());
           		else 
           		{
           			$this->_getMessageBox()->setMessagesDirect($mail->getMessages());
           			Zend_Registry::get('Zend_Db')->rollBack();
           			return false;
           		}				
				// Wenn alle erfolgreich waren, übertrage die Transaktion und alle Änderungen werden auf einmal übermittelt
    			Zend_Registry::get('Zend_Db')->commit();		
				
    			return $this;   	
        	}
        	else
        	{
        		$this->_getMessageBox()->setMessage("MSG_USER_001", $this->getUserId());
        		return false;
        	}	
		            				
		} catch (Zend_Exception $e) {
		   	// Rollback!
		   	Zend_Registry::get('Zend_Db')->rollBack();
		   	throw new Zend_Exception($e->getMessage(). "\n" .  $e->getTraceAsString());
		}
		
	}
	/**
	 * Löscht den Nutzer
	 *
	 */
	public function deleteUser()
	{
		$this->_isInit();
		// @TODO delete user
		//send confirm mail
	}
	/**
	 * Sendet eine Einladung an eine E-Mail-Adresse
	 *
	 * @param STRING $email
	 * @return User|FALSE
	 */
	public function sendInvitation($email)
	{
		$this->_isInit();
		
		//Prüfen ob noch Invites vorhanden
		if($this->_invitations > 0)
		{
			//Transaktion starten
			Zend_Registry::get('Zend_Db')->beginTransaction();
			try
			{
							
				//Invite abziehen
				$this->_invitations = $this->_invitations-1;
				$row = $this->_getUsersModel()->find($this->getUserId())->current();
	    		$row->invitations = $this->_invitations;
	    		$update = $row->save();
	    		
	    		if($update > 0)
	    		{
	    			//Invitation erstellen
	    			$invite_tbl = new InvitationsModel();
	    			$invite_data = array("key" => md5(time().rand()."x"), "invitor" => $this->getUserId());
	    			if($invite_tbl->insert($invite_data))
	        		{
	        			$data = array("email" => $email, 
	        						"absender_name" => $this->getDisplayName(),
	        						"invitation_key" => $invite_data["key"]
	        			);
	        			//Invitation senden
	            		$mail = new Mail($email);
	            		$sendmail = $mail->sendInvitation($data);
	            		if($sendmail)
	            			$this->_getMessageBox()->setMessage("MSG_USER_005", $email);
	            		else 
	            		{
	            			$this->_getMessageBox()->setMessagesDirect($mail->getMessages());
	            			Zend_Registry::get('Zend_Db')->rollBack();
	            			return false;
	            		}
	            		
	            		// Wenn alle erfolgreich waren, übertrage die Transaktion und alle Änderungen werden auf einmal übermittelt
    					Zend_Registry::get('Zend_Db')->commit();	
	            		
    					return $this;
	    			}
	    			else
	    				throw new Zend_Exception("Konnte Einladung nicht anlegen!");
	    		}
	    		else
	    			throw new Zend_Exception("Konnte Einladung nicht vom Benutzerkonto abziehen!");	

			} catch (Zend_Exception $e) {
			   	// Rollback!
			   	Zend_Registry::get('Zend_Db')->rollBack();
			   	throw new Zend_Exception($e->getMessage(). "\n" .  $e->getTraceAsString());
			}
		}
		else
		{
			//keine Invites mehr vorhanden
			$this->_getMessageBox()->setMessage("MSG_USER_004");
			return false;
		}	
	}
	/**
	 * Prüft den Key und aktiviert den Nutzeraccount
	 *
	 * @param STRING $key
	 * @return BOOLEAN
	 */
	public function activateUser($key)
	{
		if($this->getStatus() != 0)
		{
			$this->getMessageBox()->setMessage("MSG_USER_ACTIVATE_003");
			return false;
		}
		
		$reg_tbl = new RegistrationsModel();
		$select = $reg_tbl->select()->where("user_id = ?", $this->getUserId())
						->where("activationkey = ?", $key);
		$row = $reg_tbl->fetchRow($select);

		if($row && $this->getStatus() == 0)
		{
			//Nutzerstatus auf Aktiv setzen	
			
			$this->setStatus(1); //Nutzer Aktive
			if($this->save())
			{
				$this->getMessageBox()->setMessage("MSG_USER_ACTIVATE_002");
				return true;
			}
			else
			{
				return false; //Messages sollten durch save gesetzt sein
			}
			 
		}
		else
		{
			$this->getMessageBox()->setMessage("MSG_USER_ACTIVATE_001");
			return false;
		}
		
	}
	/**
	 * Gibt ein Watchlist_Set zurück
	 *
	 * @return Watchlist_Set
	 */
	public function getWatchlists()
	{
		if($this->_watchlists instanceof Watchlist_Set)
			return $this->_watchlists;
		
		$model = new WatchlistModel();
		$select = $model->select()
								->where("owner_id = ?", $this->getUserId());
		$data = $model->fetchAll($select)->toArray();
		$this->_watchlists = new Watchlist_Set($data);
		return $this->_watchlists;
	}
	/**
	 * Gibt Portfolios zurück
	 *
	 * @return Portfolio_Set
	 */
	public function getPortfolios()
	{
		if($this->_portfolios instanceof Portfolio_Set)
			return $this->_portfolios;
		
		$m = new PortfolioModel();
		$data = $m->fetchAll($m->select()->where("user_id = ?", $this->getUserId()))->toArray();	
		$this->_portfolios = new Portfolio_Set($data);
		return $this->_portfolios;
	}
	

	public function hasIndikators()
	{
		if($this->_indikator_sma !== null)
		{
			return true;
		}
		else
			return false;
	}
	/*
	protected function _getIndikators()
	{
		$m = $this->_getUserIndikatorsModel();
		$rows = $m->find($this->getUserId());
		$this->_indikators = $rows->toArray();
	}
*/
	
	/***************************************************************************************************************************************************************
	 * Group-Functions
	 */
	public function getRecentChangesThreads()
	{
		//@TODO Neue Beiträge in Thread in deren Gruppe der User ist
	}
	public function getRecentChangesAnalysis()
	{
		//@TODO Neue Analysen bei Unternehmen, in deren Gruppe der User ist
	}
	
	/***************************************************************************************************************************************************************
	 * Helper-Functions
	 */	
	/**
	 * Gibt das Model zurück
	 *
	 * @return UsersModel
	 */
	protected function _getUsersModel()
	{
		if($this->_UsersModel instanceof UsersModel)
			return $this->_UsersModel;
		else
		{
			$this->_UsersModel = new UsersModel();
			return $this->_UsersModel;
		}
	}
	/**
	 * Gibt das Model zurück
	 *
	 * @return UserIndikatorsModel
	 */
	/*
	protected function _getUserIndikatorsModel()
	{
		if($this->_UserIndikatorsModel instanceof UserIndikatorsModel)
			return $this->_UserIndikatorsModel;
		else
		{
			$this->_UserIndikatorsModel = new UserIndikatorsModel();
			return $this->_UserIndikatorsModel;
		}
	}
*/
	/**
	 * Prüft ob Objekt initiiert ist
	 *
	 * @return unknown
	 */
	protected function _isInit()
	{
		if($this->_user_id === null)
			throw new Zend_Exception("Objekt nicht richtig initialisiert!");
		else
		{
			if($this->_dataFetched == false)
				$this->getUser($this->getUserId());
			return true;
		}

	}
	/**
	 * Gibt die UserId zurück
	 *
	 * @return INT
	 */
	public function getUserId()
	{
		if($this->_user_id !== null)
			return $this->_user_id;
		else
			throw new Zend_Exception("Objekt nicht richtig initialisiert!");
	}
	/**
	 * Gibt die User-Id zurück
	 *
	 * @return INT
	 */
	public function getId()
	{
		return $this->getUserId();
	}
	/**
	 * Prüft ob useRealname gesetzt ist
	 *
	 * @return BOOLEAN
	 */
	public function isUseRealname()
	{
		$this->_isInit();
		return $this->_useRealname;
	}
	/**
	 * Gibt den Anzeigenamen zurück, Nickname oder Realname, je nach dem ob useRealname gesetzt ist
	 *
	 * @return STRING
	 */
	public function getDisplayName()
	{
		$firstname = $this->getFirstname();
		$lastname = $this->getLastname();
		if($this->_useRealname == true && !empty($firstname) && !empty($lastname))
			return $firstname." ".$lastname;
		else
			return $this->getNickname();
	}
	/**
	 * Gibt den Nickname zurück
	 *
	 * @return STRING
	 */
	public function getNickname()
	{
		if($this->_nickname !== null)
			return $this->_nickname;
		else
		{
			$this->getUser($this->getUserId());
			return $this->_nickname;
		}
	}
	/**
	 * Gibt den Nachnamen zurück
	 *
	 * @return STRING
	 */
	public function getLastname()
	{
		$this->_isInit();
		return $this->_lastname;
	}
	/**
	 * Gibt den Vornamen zurück
	 *
	 * @return STRING
	 */
	public function getFirstname()
	{
		$this->_isInit();
		return $this->_firstname;
	}
	/**
	 * Gibt die E-Mail-Adresse zurück
	 *
	 * @return STRING
	 */
	public function getEmail()
	{
		$this->_isInit();
		return $this->_email;
	}
	/**
	 * Gibt den Wert zurück, ob Newsletter gewünscht oder nicht
	 *
	 * @return unknown
	 */
	public function getNewsletter()
	{
		$this->_isInit();
		return $this->_newsletter;
	}
	/**
	 * Gibt die Nutzerrolle zurück
	 *
	 * @return STRING
	 */
	public function getRole()
	{
		return $this->_role;
	}
	/**
	 * Gibt das Registrierungdatum zurück
	 *
	 * @return unknown
	 */
	public function getRegDate()
	{
		$this->_isInit();
		return $this->_reg_date;
	}
	/**
	 * Gibt die Anzahl der Tage für den SMA zurück
	 *
	 * @return INT
	 */
	public function getIndikatorSMA()
	{
		$this->_isInit();
		return $this->_indikator_sma;
	}

	/**
	 * Gibt ein PictureObjekt zurück
	 *
	 * @param STRING $size s|m
	 * @return Image
	 */
	public function getPicture($size)
	{
		if($size == "s")
			$id = $this->_image_id_s;
		elseif ($size == "m")
			$id = $this->_image_id_m;
		else
			throw new Zend_Exception("Nicht unterstütze Bildgröße angegeben");
		
		return new Image($id);
	}
	/**
	 * Gibt die Anzahl an freien Einladungen zurück
	 *
	 * @return INT
	 */
	public function getInvitations()
	{
		if($this->_invitations !== null)
			return $this->_invitations;
		else
		{
			$this->getUser($this->getUserId());
			return $this->_invitations;
		}
	}
	/**
	 * Gibt den Nutzerstatus zurück
	 *
	 * @return INT
	 */
	public function getStatus()
	{
		return $this->_status;
	}
	/**
	 * Gibt die ImageId des Nutzerbildes in Größe S zurück
	 *
	 * @return INT
	 */
	public function getImageIdS()
	{
		return $this->_image_id_s;
	}
	/**
	 * Gibt die ImageId des Nutzerbildes in Größe M zurück
	 *
	 * @return INT
	 */
	public function getImageIdM()
	{
		return $this->_image_id_m;
	}
	
	/* Set-Methoden */
	public function setStatus($value)
	{
		$this->_status = $value;
	}
	public function setImageIdS($value)
	{
		$this->_image_id_s = $value;
	}
	public function setImageIdM($value)
	{
		$this->_image_id_m = $value;
	}
	public function setIndikatorSMS($value)
	{
		$this->_indikator_sma = $value;
	}
	/**
	 * Speichert aktuellen Inhalt des Objektes in DB
	 *
	 * @return BOOLEAN
	 */
	public function save()
	{
		//Alles in einen Array packen
		$values = array();
		$values["password"] = $this->_passwordNew;
		$values["nickname"] = $this->_nickname;
		$values["email"] = $this->_email;
		$values["firstname"] = $this->_firstname;
		$values["lastname"] = $this->_lastname;
		$values["newsletter"] = $this->_newsletter;
		$values["status"] = $this->_status;
		$values["use_realname"] = $this->_useRealname;
		$values["role"] = $this->_role;
		$values["invitations"] = $this->_invitations;
		$values["image_id_m"] = $this->_image_id_m;
		$values["image_id_s"] = $this->_image_id_s;
		$values["indikator_sma"] = $this->_indikator_sma;
		
		return $this->edit($values);
	}
	
	public function getSignalIndikators()
	{
		//persönliche Einstellungen holen
		$indikator_sma = $this->getIndikatorSMA();

		$indikators = array(
								"SMA" => array(array("period" => $indikator_sma)),
								"MACD" => array(array("fastEMA" => 8, "slowEMA" => 17, "signalEMA" => 9)),
								"STO" => array(array("k" => 14, "d" => 5, "type" => "slow"))
								);
		return $indikators;	
	}
}