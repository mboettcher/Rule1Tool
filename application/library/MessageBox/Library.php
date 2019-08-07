<?php
class MessageBox_Library
{
	protected $WARNING;
	protected $NOTICE;
	protected $INFO;
	protected $SUCCESS;
	
	protected $_messageTemplates = array();
    
    public function __construct()
    {
    	$this->WARNING = Zend_Registry::get("config")->general->messages->levels->WARNING->value;
		$this->NOTICE = Zend_Registry::get("config")->general->messages->levels->NOTICE->value;
		$this->INFO = Zend_Registry::get("config")->general->messages->levels->INFO->value;
		$this->SUCCESS = Zend_Registry::get("config")->general->messages->levels->SUCCESS->value;
		
		$this->_messageTemplates = array(
	        "MSG_xx" => array("msg" => 'test Message.', "level" => $this->WARNING),
		
			"MSG_INDEX_INVITE_REG_001" => array("msg" => 'Ihre E-Mail-Adresse wurde auf der Warteliste eingetragen. Unter Umständen kann es eine Weile dauern, bis Sie einen Einladungsschlüssel erhalten. Stay tuned.', "level" => $this->SUCCESS),
			"MSG_INDEX_INVITE_REG_002" => array("msg" => 'Ihre E-Mail-Adresse steht bereits auf der Warteliste. Sie werden schon bald einen Einladungsschlüssel von uns bekommen.', "level" => $this->INFO),
		
			"MSG_INDEX_FEEDBACK" => array("msg" => 'Vielen Dank für Ihre Nachricht.', "level" => $this->SUCCESS),
			
			"MSG_AUTH_001" => array("msg" => "Bitte geben Sie Ihre E-Mail-Adresse ein.", "level" => $this->WARNING),
			"MSG_AUTH_002" => array("msg" => "Ungültige E-Mail-Adresse bzw. Kennwort.", "level" => $this->WARNING),
			"MSG_AUTH_003" => array("msg" => "Dieser Account wurde noch nicht aktiviert. Bitte klicken Sie auf den Link in der Registrierungs-Mail um den Account zu aktivieren.", "level" => $this->WARNING),
	
			"MSG_ANALYSIS_001" => array("msg" => "Konnte keine Analyse mit der ID %value% finden.", "level" => $this->WARNING),
			"MSG_ANALYSIS_002" => array("msg" => "Nicht genügend Daten vorhanden.", "level" => $this->WARNING),
			"MSG_ANALYSIS_003" => array("msg" => "Ihre Analyse wurde angelegt. <br/><br/> Sie werden jetzt zur Analyse weitergeleitet...", "level" => $this->SUCCESS),
			"MSG_ANALYSIS_004" => array("msg" => "Ihre Analyse wurde erfolgreich bearbeitet. Bitte überprüfen Sie noch einmal die Daten.", "level" => $this->SUCCESS),
			"MSG_ANALYSIS_005" => array("msg" => "Ihre Analyse wurde noch nicht gespeichert. Tragen Sie alle benötigten Daten (rot markiert) ein, um die Analyse zu speichern.", "level" => $this->NOTICE),
			"MSG_ANALYSIS_006" => array("msg" => "Ihre Änderungen wurde noch nicht gespeichert. Tragen Sie alle benötigten Daten (rot markiert) ein, um die Analyse zu speichern.", "level" => $this->NOTICE),
		
			"MSG_USER_001" => array("msg" => "Leider wurde kein Nutzer mit der angegebenen Mitglieds-ID, E-Mail-Adresse oder Mitgliedsnamen '%value%' gefunden.", "level" => $this->WARNING),
			"MSG_USER_002" => array("msg" => "Der Mitgliedsname existiert bereits.", "level" => $this->NOTICE),
			"MSG_USER_003" => array("msg" => "Ein Nutzer mit der selben E-Mail-Adresse existiert bereits.", "level" => $this->NOTICE),	
			"MSG_USER_004" => array("msg" => "Es sind leider keine Einladungen mehr vorhanden.", "level" => $this->WARNING),	
			"MSG_USER_005" => array("msg" => "Einladungen wurde an '%value%' versandt.", "level" => $this->SUCCESS),
			"MSG_USER_006" => array("msg" => "Herzlich Willkomen bei Rule1Tool. Wir haben Ihnen eine E-Mail an %value% geschickt, über die Sie Ihren Account aktivieren können.", "level" => $this->SUCCESS),
			"MSG_USER_007" => array("msg" => "Änderungen gespeichert.", "level" => $this->SUCCESS),	
			"MSG_USER_008" => array("msg" => "Ihr neues Kennwort wurde an %value% versandt.", "level" => $this->SUCCESS),
			"MSG_USER_009" => array("msg" => "Keine Änderungen vorgenommen.", "level" => $this->SUCCESS),	
		
		
			"MSG_USER_ACTIVATE_001" => array("msg" => "Der angegebene Aktivierungsschlüssel ist ungültig.", "level" => $this->WARNING),
			"MSG_USER_ACTIVATE_002" => array("msg" => "Ihr Account wurde erfolgreich aktiviert. Sie werden nun automatisch zur Anmelde-Seite weitergeleitet...", "level" => $this->SUCCESS),
			"MSG_USER_ACTIVATE_003" => array("msg" => "Ihr Account wurde bereits aktiviert.", "level" => $this->WARNING),
		
			"MSG_COMPANY_001" => array("msg" => "Die ISIN %value% wurde nicht gefunden.", "level" => $this->WARNING),
			"MSG_COMPANY_002" => array("msg" => "Keine Analyse zum Unternehmen vorhanden. Lege jetzt eine an!", "level" => $this->WARNING),
			"MSG_COMPANY_003" => array("msg" => "Die Analyse wurde als Ihr Favorit gespeichert.", "level" => $this->SUCCESS),
			"MSG_COMPANY_004" => array("msg" => "Favorit konnte nicht gesetzt werden.", "level" => $this->WARNING),
		
			"MSG_GROUP_001" => array("msg" => "Es konnte keine Gruppe mit der ID %value% gefunden werden.", "level" => $this->WARNING)	,
			"MSG_GROUP_002" => array("msg" => "Änderungen wurden gespeichert", "level" => $this->SUCCESS)	,
			"MSG_GROUP_003" => array("msg" => "Die Gruppe \"%value%\" wurde angelegt.", "level" => $this->SUCCESS)	,
			"MSG_GROUP_004" => array("msg" => "Es wurden keine Änderungen vorgenommen", "level" => $this->INFO)	,
			"MSG_GROUP_005" => array("msg" => "Die Gruppe \"%value%\" wurde gelöscht.", "level" => $this->SUCCESS)	,
			
			
			"MSG_THREAD_001" => array("msg" => 'Es konnte kein Thema mit der ID %value% gefunden werden.', "level" => $this->WARNING),

			"MSG_THREAD_002" => array("msg" => 'Die Änderungen wurden gespeichert.', "level" => $this->SUCCESS),
			"MSG_THREAD_003" => array("msg" => 'Es wurden keine Änderungen vorgenommen.', "level" => $this->INFO),
			"MSG_THREAD_004" => array("msg" => 'Das Thema wurde erfolgreich angelegt.', "level" => $this->SUCCESS),
			"MSG_THREAD_005" => array("msg" => 'Das Thema wurde erfolgreich gelöscht.', "level" => $this->SUCCESS),
			"MSG_THREAD_006" => array("msg" => 'Das Thema wurde nicht gelöscht.', "level" => $this->WARNING),
		
			
			"MSG_REPLY_001" => array("msg" => 'Es konnte kein Beitrag mit der ID %value% gefunden werden.', "level" => $this->WARNING),
			"MSG_REPLY_002" => array("msg" => 'Ihre Antwort wurde gespeichert.', "level" => $this->SUCCESS),
			"MSG_REPLY_003" => array("msg" => 'Keine Änderungen vorgenommen.', "level" => $this->INFO),
			"MSG_REPLY_004" => array("msg" => 'Die Antwort wurde gelöscht.', "level" => $this->SUCCESS),
		
			"MSG_GROUPMEMBER_001" => array("msg" => 'Gruppen-Mitglied nicht gefunden.', "level" => $this->WARNING),
			"MSG_GROUPMEMBER_002" => array("msg" => 'Konnte nicht in Gruppe eintreten.', "level" => $this->WARNING),		
			"MSG_GROUPMEMBER_003" => array("msg" => 'Sie sind jetzt Mitglied in der Gruppe.', "level" => $this->WARNING),		
			"MSG_GROUPMEMBER_004" => array("msg" => 'Sie sind jetzt nicht mehr Mitglied in der Gruppe.', "level" => $this->WARNING),		
		
			"MSG_WATCHLIST_001" => array("msg" => '%value% wurde von der Watchlist entfernt.', "level" => $this->SUCCESS),
			"MSG_WATCHLIST_002" => array("msg" => 'Unternehmen konnte nicht von der Watchlist entfernt werden.', "level" => $this->WARNING),
			"MSG_WATCHLIST_003" => array("msg" => 'Watchlist wurde erfolgreich angelegt', "level" => $this->SUCCESS),
			"MSG_WATCHLIST_004" => array("msg" => 'Watchlist wurde nicht angelegt.', "level" => $this->WARNING),
			"MSG_WATCHLIST_005" => array("msg" => '%value% wurde zur Watchlist hinzugefügt.', "level" => $this->SUCCESS),
			"MSG_WATCHLIST_006" => array("msg" => '%value% wurde nicht zur Watchlist hinzugefügt.', "level" => $this->WARNING),
			"MSG_WATCHLIST_007" => array("msg" => 'Watchlist %value% wurde erfolgreich gelöscht.' , "level" => $this->SUCCESS),
			"MSG_WATCHLIST_008" => array("msg" => 'Watchlist %value% konte nicht gelöscht werden.', "level" => $this->WARNING),
			"MSG_WATCHLIST_009" => array("msg" => 'Änderungen an der Watchlist wurden gespeichert.' , "level" => $this->SUCCESS),
			"MSG_WATCHLIST_010" => array("msg" => 'Änderungen konnten nicht gespeichert werden.' , "level" => $this->WARNING),
			"MSG_WATCHLIST_011" => array("msg" => 'Es wurden keine Änderungen vorgenommen.' , "level" => $this->WARNING),
			"MSG_WATCHLIST_012" => array("msg" => 'Sie haben noch keine Watchlist angelegt. Klicken Sie <a href="%value%">hier</a> um eine neue Watchlist anzulegen' , "level" => $this->NOTICE),
			"MSG_WATCHLIST_013" => array("msg" => 'Auf der Watchlist sind noch keine Aktien enthalten. Um Aktien hinzuzufügen gehen Sie auf die Unternehmens-Seite und klicken in der rechten Navigation auf "zur Watchlist hinzufügen".' , "level" => $this->NOTICE),
			"MSG_WATCHLIST_014" => array("msg" => '%value% befindet sich bereits auf Ihrer Watchlist.', "level" => $this->NOTICE),
		
			"MSG_PORTFOLIO_001" => array("msg" => 'Transaktion wurde erfolgreich hinzugefügt. Unternehmen: %value0%, Kurs: %value1%, Anzahl: %value2%, Gebühren: %value3%, Datum: %value4%. ', "level" => $this->SUCCESS),
			"MSG_PORTFOLIO_002" => array("msg" => 'Die Transaktion wurde gelöscht.', "level" => $this->SUCCESS),
			"MSG_PORTFOLIO_003" => array("msg" => 'Transaktion konnte nicht gelöscht werden.', "level" => $this->WARNING),
			"MSG_PORTFOLIO_007" => array("msg" => 'Portfolio %value% wurde erfolgreich gelöscht.' , "level" => $this->SUCCESS),
			"MSG_PORTFOLIO_008" => array("msg" => 'Portfolio %value% konte nicht gelöscht werden.', "level" => $this->WARNING),
			"MSG_PORTFOLIO_009" => array("msg" => 'Änderungen am Portfolio wurden gespeichert.' , "level" => $this->SUCCESS),
			"MSG_PORTFOLIO_010" => array("msg" => 'Änderungen konnten nicht gespeichert werden.' , "level" => $this->WARNING),
		
		
			"MSG_MAIL_001" => array("msg" => 'Es ist ein Fehler aufgetreten. Konnte E-Mail nicht versenden.', "level" => $this->WARNING),
			"MSG_PICTURE_INPUT_001" => array("msg" => 'Die Datei konnte nicht empfangen werden.', "level"=>$this->WARNING),
			"MSG_STOCKSEARCH_001" => array("msg" => 'Zum Suchbegriff "%value%" konnten wir leider kein Unternehmen finden. Überprüfen Sie bitte noch einmal Ihre Eingabe.', "level" => $this->NOTICE),
			"MSG_STOCKSEARCH_002" => array("msg" => 'Bitte beim Suchbegriff etwas präziser sein.', "level" => $this->NOTICE)
		
    	);
		
    }
    
	public function getMessage($msg_key, $value = null)
	{
		if(isset($this->_messageTemplates[$msg_key]))
			return $this->_createMessage($msg_key, $value);
		else
			throw new Zend_Exception("Unbekannter Message-Key $msg_key");
	}
    protected function _createMessage($message_key, $value)
    {
    	$message = $this->_messageTemplates[$message_key];
    	if($message)
    	{
   			   			
    		$translator = Zend_Registry::get("Zend_Translate");
    		if ($translator->isTranslated($message_key)) {
                $message["msg"] = $translator->translate($message_key);
            }
            if(is_array($value))
            {
            	for ($i=0; $i < count($value); $i++)
            		$search[$i] = '%value'.$i.'%';
            }
            else 
            	$search = '%value%';
            $message["msg"] = str_replace($search, $value, $message["msg"]);
            
    		//Warnungen mit loggen
    		if($message["level"] == 0)
    			Zend_Registry::get('Zend_Log')->info($message_key." ".$message["msg"]);
            
           
    		return $message;
    	}
    	else 
    	    throw new Zend_Exception("Unbekannter Fehler!");
    }
}