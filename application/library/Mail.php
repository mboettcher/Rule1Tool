<?php
/*
 * Mail
 */

class Mail extends Abstraction 
{
	/**
	 * Zend_Mail Object
	 *
	 * @var Zend_Mail
	 */
	protected $_Zend_Mail = null;
	
	
	//Eigenschaften
	protected $_empfaenger;
	protected $_cc;
	protected $_bcc;

		//Helpers
		protected $layout;
		protected $view;
		protected $config;
		
		//Methoden
	
		public function __construct($empfaenger, $cc = null, $bcc = null)
		{
			$this->_empfaenger = $empfaenger;	
			$this->_cc = $cc;	
			$this->_bcc = $bcc;		
			
			$this->config = Zend_Registry::get('config');	
			
			$options = array(
			    'layout'     => $this->config->general->layout->mail->layout,
			    'layoutPath' => $this->config->general->layout->layoutPath
			);

			$this->layout = new Zend_Layout();
			// Einen Layout Skript Pfad setzen:
			$this->layout->setLayoutPath($this->config->general->layout->layoutPath);
			// Ein unterschiedliches Layout Skript auswählen:
			$this->layout->setLayout($this->config->general->layout->mail->layout);
			
			$this->view = new Zend_View();
			$this->view->setScriptPath($this->config->general->view->ScriptPath->mail);
			$this->view->setEscape('htmlentities');
			$this->view->setEncoding("UTF-8");
			$this->view->addHelperPath('../application/views/helpers', 'View_Helper_');
			
			$this->layout->setView($this->view);
			
		}
		/**
		 * Prüft die E-Mail-Adresse
		 *
		 * @param STRING $email
		 * @return BOOLEAN
		 */
		protected function _validateEmail($email)
		{
			$validator_mail = new Zend_Validate_EmailAddress();
			if(!$validator_mail->isValid($email))
			{
				$this->_getMessageBox()->setMessagesDirect($validator_mail->getMessages());
				
				return false;
			}
			return true;
		}

		/**
		 * Versendet die E-Mail
		 *
		 * @param STRING $typ - view
		 * @param ARRAY $data
		 * @param ARRAY attachments
		 * @return BOOLEAN|Mail
		 */
		protected function send($typ, $data, $attachments = null, $sendMailReally = true)
		{
			//Mail
			if(!$this->_validateEmail($this->_empfaenger))
				return false;
				
			//Array mit allen Maildaten anlegen, damit dieses später in DB gespeichert werden kann	
			$mailData = array();

			$mailData["bodyHtml"] = $this->createMail($typ, $data);
			$mailData["from"]["email"] = $this->config->general->mail->from->default->email;
			$mailData["from"]["name"] = $this->config->general->mail->from->default->name;
			$mailData["to"] = $this->_empfaenger;
			$mailData["subject"] = $this->config->general->mail->subject.' - '.$data["subject"];
			$mailData["cc"] = $this->_cc;
			$mailData["bcc"] = $this->_bcc;
			$mailData["attachments"] = $attachments;
			
			$this->sendRenderedMail($mailData, $sendMailReally);
				
			return $this;

		}
		static public function sendRenderedMail($mailData, $sendMailReally = true)
		{
			
			$Zend_Mail = new Zend_Mail('UTF-8');
			$Zend_Mail->setBodyHtml($mailData["bodyHtml"])
				->setFrom($mailData["from"]["email"], $mailData["from"]["name"])
				->addTo($mailData["to"])
				->setSubject($mailData["subject"]);
			if($mailData["cc"] != null)
			{
				$Zend_Mail->addCc($mailData["cc"]);
			}
			if($mailData["bcc"] != null)
			{
				$Zend_Mail->addBcc($mailData["bcc"]);
			}
				
			if($mailData["attachments"])
			{
				//Anhänge anfügen
				if(is_array($mailData["attachments"]))
				{
					foreach ($mailData["attachments"] as $attachment)
					{
						$at = $Zend_Mail->createAttachment($attachment["content"]);
						$at->filename = $attachment["filename"];
					}		
				}			
			}
			
			if($sendMailReally)
			{
				//send Mail
				try {
					//throw new Zend_Exception("lala");
					$Zend_Mail->send();
					
					return true;
				} catch (Zend_Exception $e)
				{
				
					Zend_Registry::get('Zend_Log')
						->log($e->getMessage(). "\n" .  $e->getTraceAsString(), Zend_Log::NOTICE);
					
					$mailSerialize = false;
					//Für später speichern, serializieren und in DB speichern	
									
					$mailSerialize = serialize($mailData);
					
					if($mailSerialize)
					{
						$table = new MailQueueModel();
						$table->insert(array("mail" => $mailSerialize));						
					}
					return false;
				}
			}
			else
			{
				//Queue Mail
				$mailSerialize = false;
				//Für später speichern, serializieren und in DB speichern	
								
				$mailSerialize = serialize($mailData);
				
				if($mailSerialize)
				{
					$table = new MailQueueModel();
					$table->insert(array("mail" => $mailSerialize, 'info' => $mailData["to"]));						
				}
				return false;
			}
			

		}
		
		
		protected function createMail($typ, $data)
		{
			//assign
			$this->view->data = array();
			$this->view->data = $data;
			
			$content = $this->view->render($typ.'.phtml');
						
			// Einige Variablen setzen:
			$this->layout->content = $content;

			// Letztendlich das Layout darstellen
			return $this->layout->render();

		}
		/**
		 * Sendet die RegistrationMail
		 *
		 * @param ARRAY $input
		 * @return BOOLEAN|Mail
		 */
		public function sendRegistrationMail($input)
		{
			$data = $this->filterInput($input);
	
			$data["subject"] = $this->view->translate('Ihre Registrierung');
			
			return $this->send("register-new-user", $data);
		}
		/**
		 * Sendet die SignalMail
		 *
		 * @param ARRAY $input
		 * @return BOOLEAN|Mail
		 */
		public function sendSignalMail($input)
		{
			$data = $input;
			
			if(!isset($data["zdate"]))
				$data["zdate"] = new Zend_Date();
	
			$data["subject"] = $this->view->translate('Signale vom')." ".$data["zdate"]->get(Zend_Date::DATES);
			
			return $this->send("send-signal-mails", $data, null, false);
		}
		/**
		 * Filtert Daten und sendet die Mail zum PW-Reset raus
		 *
		 * @param ARRAY $input
		 * @return BOOLEAN|Mail
		 */
		public function sendResetPasswordMail($input)
		{
			$data = $this->filterInput($input);
	
			$data["subject"] = $this->view->translate('Ihr Passwort wurde zurückgesetzt');
			
			return $this->send("reset-user-password", $data);
		}
/**
		 * Filtert Daten und sendet die Mail das jemand eine Invitation will raus
		 *
		 * @param ARRAY $input
		 * @return BOOLEAN|Mail
		 */
		public function sendAdminGetInvitationMail($input)
		{
			$data = $this->filterInput($input);
	
			$data["subject"] = $this->view->translate('Jemand möchte einen Einladungsschlüssel haben');
			
			return $this->send("admin-get-invitation", $data);
		}
		/**
		 * Filtert Daten und sendet die Mail mit den Logdaten raus
		 *
		 * @param ARRAY|STRING $files
		 * @return BOOLEAN|Mail
		 */
		public function sendApplicationLogsMail($files)
		{	
			$zdate = new Zend_Date();
			
			$data["subject"] = $this->view->translate("Logs - %1\$s", $zdate->get(Zend_Date::DATE_MEDIUM));
			
			return $this->send("application-logs", $data, $files);
		}
		
		/**
		 * Filtert Daten und sendet die Feedback-Mail raus
		 *
		 * @param ARRAY $input
		 * @return BOOLEAN|Mail
		 */
		public function sendAdminFeedback($input)
		{
			$data = $this->filterInput($input);
	
			$data["subject"] = $this->view->translate("Neue Feedback-Nachricht");
			
			return $this->send("admin-feedback", $data);		
		}
		
		/**
		 * Filtert Daten und sendet die Einladungs-Mail raus
		 *
		 * @param ARRAY $input
		 * @return BOOLEAN|Mail
		 */
		public function sendInvitation($input)
		{
			$data = $this->filterInput($input);
	
			$data["subject"] = $this->view->translate("%1\$s läd Sie ein!", $data["absender_name"]);
			
			return $this->send("invitation", $data);		
		}
		protected function filterInput($data)
		{
			//Filters
			$filters = array('*' => array('StringTrim','StripTags')	);
			$validators = array();
			//Filter_Input starten
			$input = new Zend_Filter_Input($filters, $validators);
			$input->setDefaultEscapeFilter(new Filter_HtmlSpecialChars());
			//Daten laden
			$input->setData($data);
			//gefilterte Daten holen
			return $input->getEscaped(); 
		}
}