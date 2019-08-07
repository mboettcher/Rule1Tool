<?php
/**
 * IndexController
 * 
 * @author
 * @version 
 */

class IndexController extends Zend_Controller_Action
{
	function preDispatch() 
	{ 
	
	}
	function init()
	{
		$this->view->params = $this->_request->getParams();
		
		$this->view->noganalytics = $this->_getParam("deactivateGoogleAnalytics");
		
		$contextSwitch = $this->_helper->getHelper('SwitchContext');
		$contextSwitch	->addActionContext('index', 'mobile')
						->addActionContext('index-auth', 'mobile')
		 				//->addActionContext('index', 'json')	
		 				->addActionContext('feedback', 'mobile')
		 				->addActionContext('imprint', 'mobile')
		 				->addActionContext('get-invitation', 'mobile')
						->initContext();		
		
		
	}
	function indexAction()
	{
		//$this->view->headTitle('Das Werkzeug für alle Regel1-Investoren');
		//$this->view->headMeta()->appendName('description', "Basierend auf den hervorragenden Grundlagen des Buches Regel Nummer 1 von Phil Town, bietet Rule1Tool die Werkzeuge für erfolgreiche Anleger.");

		
		
		if(Zend_Registry::get("Zend_Auth")->hasIdentity())
		{
			//Nutzer angemeldet
			$this->_forward("index-auth");
		}
		else
		{
			//Nutzer nicht angemeldet
			//$this->render("index-no-auth");
		}
		
		
	}
	function indexAuthAction()
	{		
	
		
		//Indiz und Watchlists holen
		
		//Indiz
		/*
		 * DAX DE0008469008
		 * TECDAX DE0007203275
		 * MDAX DE0008467416
		 * DOW US2605661048
		 * NASDAQ-100 US6311011026
		 * NIKKEI-225 XC0009692440
		 */
		
		$this->view->dax = new Company();
		$this->view->dax->getCompanyByISIN("DE0008469008");	

		$this->view->mdax = new Company();
		$this->view->mdax->getCompanyByISIN("DE0008467416");		
		
		$this->view->tecdax = new Company();
		$this->view->tecdax->getCompanyByISIN("DE0007203275");		
		
		$this->view->dow = new Company();
		$this->view->dow->getCompanyByISIN("US2605661048");		
		
		$this->view->nasdaq = new Company();
		$this->view->nasdaq->getCompanyByISIN("US6311011026");		
		
		$this->view->nikkei = new Company();
		$this->view->nikkei->getCompanyByISIN("XC0009692440");	
		
		//Watchlists
		$model = new WatchlistModel();
		$select = $model->select()
						->where("owner_id = ?", Zend_Registry::get("UserObject")->getUserId());
				
		$this->view->watchlists = $model->fetchAll($select)->toArray();
		
	}
	function aboutAction()
	{
		$this->view->headTitle('About');
	}
	function imprintAction()
	{
		$this->view->headTitle('Imprint');
	}
	public function feedbackAction()
	{
		$this->view->isFramed = $this->_request->getParam("FRAMED");
		
        //Layout deaktivieren
        if($this->view->isFramed)
        	$this->_helper->layout->setLayout('framed');
        else 
        	$this->view->isFramed = false;
        	
		$this->view->headTitle('Feedback');
		
		$form = new Zend_Form();
		$form->setAction($this->view->url(array("language" => Zend_Registry::get('Zend_Locale')->getLanguage()), "feedback"));
		
		$name = new Zend_Form_Element_Text("name");
		$name->setLabel('Name');
		$name->addFilters(array('StripTags', 'StringTrim'));
		$name->setRequired(false);
		
		$email = new Zend_Form_Element_Text("email");
		$email->setLabel('E-Mail-Adresse');
		$email->addValidator(new Zend_Validate_EmailAddress());
		$email->setRequired(false);
		$email->addFilters(array('StripTags', 'StringTrim'));
		
		$text = new Zend_Form_Element_Textarea("text");
		$text->setLabel('Nachricht')
              //->setRequired(true)
              //->addValidator('NotEmpty', true)
              ->addValidator(new Zend_Validate_StringLength(2,1000), true)
              ->setAttrib("rows", 8)
              ->setAttrib("cols", 35);
		$text->setRequired(true);
		$text->addFilters(array('StripTags', 'StringTrim'));
		
		$captcha = new Zend_Form_Element_Captcha('captcha', array(
    'label' => "Sicherheitscode",
    'captcha' => 'ReCaptcha',
    'captchaOptions' => array(
        'captcha' => 'ReCaptcha',
        'privKey' => Zend_Registry::get("config")->general->recaptcha->privkey,
        'pubKey' => Zend_Registry::get("config")->general->recaptcha->pubkey,
    ),
));
		
		$submit = new Zend_Form_Element_Submit("submit");
		$submit->setLabel("» Abschicken");
		
		
		$form->addElements(array($name, $email, $text,$captcha, $submit));
		
    	
    	if ($this->getRequest()->isPost())
		{
			$mbox = new MessageBox();
			//Daten einfügen
			if($form->isValid($this->_getAllParams()))
			{		
				$feedback = new FeedbackModel();
				$data = array(
										"text" => $form->getValue("text"),
										"email" => $form->getValue("email"),
										"name" => $form->getValue("name")
				);
				$insert = $feedback->insert($data);
				
				$mail = new Mail(Zend_Registry::get("config")->general->mail->from->default->email);
				$mail->sendAdminFeedback($data);
				
				$this->view->success = $insert;
				$mbox->setMessage("MSG_INDEX_FEEDBACK");
				$this->view->messages = $mbox->getMessages();				
			}
			else 
			{
				$this->view->success = false;
				$mbox->setMessagesDirect($form->getMessages());
				$this->view->messages = $mbox->getMessages();	
				$this->view->form = $form;	
			}

		}
		else 
			$this->view->form = $form;
		
		
	}
	
	public function getInvitationAction()
	{
		$form = new Zend_Form();
		$form->setAction($this->view->url(array("language" => Zend_Registry::get('Zend_Locale')->getLanguage()), "get_invitation"));
        $form->setMethod('post');
 
        $options = array('disableLoadDefaultDecorators' => true);
		$email = new Zend_Form_Element_Text("mail", $options);
		$email->addValidator(new Zend_Validate_EmailAddress());
		$email->setRequired(true);
		$email->addFilters(array('StripTags', 'StringTrim'));
        $submit = new Zend_Form_Element_Submit("submit", $options);
   
        //Prepend an opening div tag before "one" element:
		$email->addDecorator('HtmlTag', array(
		    'tag' => 'div',
		    'openOnly' => true,
		    'placement' => Zend_Form_Decorator_Abstract::PREPEND
		));
		$email->addDecorators(array(
            'ViewHelper', 'Label'
        ));
		$email->setLabel("E-Mail-Adresse"); 
		 
		
		$submit->addDecorators(array(
            'ViewHelper'
        ));
        //Append a closing div tag after "two" element:
		$submit->addDecorator('HtmlTag', array(
		    'tag' => 'div',
		    'closeOnly' => true,
		    'placement' => Zend_Form_Decorator_Abstract::APPEND
		));
 
        $submit->setLabel("Für Einladung bewerben");     
        
		$form->addElements(array($email, $submit));
		//Set the decorators we need:
        						
		if ($this->getRequest()->isPost())
		{
			$mbox = new MessageBox();
			if($form->isValid($this->_getAllParams()))
			{
				$empfaenger = $form->getValue("mail");
				
				$model = new InvitationReg();
				
				//check if allready in
				$find = $model->find($empfaenger)->current();
				if(!$find)
				{
					Zend_Registry::get('Zend_Db')->beginTransaction();
					
					try {
						//add
						$model->insert(array("mail" => $empfaenger));
							
						$mbox->setMessage("MSG_INDEX_INVITE_REG_001");
						$this->view->mbox = $mbox->getMessages();
						
						$mail = new Mail(Zend_Registry::get("config")->general->mail->from->default->email);
						$mail = $mail->sendAdminGetInvitationMail(array("mail" => $empfaenger));
						
						Zend_Registry::get('Zend_Db')->commit();
												
					} catch (Zend_Exception $e) {
						
					    Zend_Registry::get('Zend_Db')->rollBack();
					    throw new Zend_Exception($e->getMessage(). "\n" .  $e->getTraceAsString());
					}
					
				}
				else 
				{
					//bereits vorhanden
					$mbox->setMessage("MSG_INDEX_INVITE_REG_002");
					$this->view->mbox = $mbox->getMessages();
					$this->view->form = $form;
				}				
			}
			else
			{
				$mbox->setMessagesDirect($form->getMessages());
				$this->view->mbox = $mbox->getMessages();
				$this->view->form = $form;
			}			
		}
		else 
		{
			$this->view->form = $form;
		}
		
		
	}
	
	public function agbAction()
	{
		$this->view->headTitle('AGB');
	}
	
	public function setLayoutAction()
	{
		$ns = new Zend_Session_Namespace('Rule1Tool');
		$ns->layout = $this->_request->getParam("layout");

		$this->_helper->getHelper('Redirector')->setPrependBase(false);
	   	$this->_redirect($this->view->baseUrl()); //ab zur Startseite
	}
	
	public function tourAction()
	{
		$this->view->headTitle('Tour');
		
	}

	/*
	public function rule1infoAction()
	{
		$this->view->headTitle('Regel Nummer 1 / Rule #1: Einfach erfolgreich anlegen!');
		$this->view->headMeta()->appendName('description', 'Mit Regel Nummer 1 von Phil Town zur erfolgreichen Aktien-Anlage: wunderbare Unternehmen finden, die Sie lieben, den Empfehlungspreis und Sicherheitspolster berechnen und anhand der 3 Werkzeuge Chart-Entwicklungen frühzeitig bemerken.');	
	}
	public function paybacktimeinfoAction()
	{
		$this->view->headTitle('Payback Time / Jetzt aber!: Erfolgreich anlegen in 8 Schritten');
		$this->view->headMeta()->appendName('description', 'Mit Payback Time von Phil Town zur erfolgreichen Aktien-Anlage: wunderbare Unternehmen finden, die Sie lieben, den Empfehlungspreis und Sicherheitspolster berechnen und Aktien kaufen (stockpilling), wenn andere verkaufen.');	
	}
*/
	/*
	public function sitemapAction()
	{
		$this->view->headTitle('Sitemap');
	}
	*/
	public function donateAction()
	{
		
	}
	
	public function r1tRssAction()
	{
        $this->_helper->layout->disableLayout();
        
        $identifier = Zend_Registry::get('systemtype')."_rss_r1tnews";
        
        $lastUpdateDate = '20110112';
        
		$cache = Zend_Registry::get('Zend_Cache_Core');
		if (!($rssFeed = $cache->load($identifier.$lastUpdateDate))) {
				
	        $date = new Zend_Date();
				
			$linkR1T = $this->view->baseUrl();
	        $entries = array();

	        $date->set('2011-01-12', Zend_Date::ISO_8601); 
	 		$entries[] = array("lastUpdate" => $date->getTimestamp(), "link" => $linkR1T,
				"title" => $this->view->translate("Fehlende Kursdaten vom 11.01.2011"), 
				"description" => "Rule1Tool.com hatte letzte Nacht Schnupfen und hat deswegen nicht alle Kursdaten vom 11.01. rechtzeitig geholt. Die meisten wurden mittlerweile bereits nachgeladen. Die wenigen Fehlenden werden innerhalb der nächsten Tage automatisch nachgepflegt.");
				
	        $date->set('2010-10-20', Zend_Date::ISO_8601); 
	 		$entries[] = array("lastUpdate" => $date->getTimestamp(), "link" => $linkR1T,
				"title" => $this->view->translate("Downtime wegen Störung beim Provider"), 
				"description" => "Rule1Tool war heute - wie vielleicht einige bemerkt haben - von ca. 21:24 Uhr bis ca. 23 Uhr nicht erreichbar. Ursache war ein Problem mit der Stromversorgung bei unserem Provider STRATO. Aber nun ist ja der Strom wieder da :).");
				        
	        $date->set('2010-09-19', Zend_Date::ISO_8601); 
	 		$entries[] = array("lastUpdate" => $date->getTimestamp(), "link" => $linkR1T,
				"title" => $this->view->translate("Störung des Mail-Versands - Update Nr 2"), 
				"description" => "Die eigentliche Ursache der letzten Versandstörung (doppelter/vielfacher Versand) ist zwar (trotz nervenaufreibender Suche) nicht ganz klar - wir haben aber Vorsichtsmaßnahmen ergriffen, damit dies nicht wieder vorkommt. Wir bitten hiermit nochmals um Entschuldigung für den Spam :).");
			
	        $date->set('2010-09-15', Zend_Date::ISO_8601); 
	 		$entries[] = array("lastUpdate" => $date->getTimestamp(), "link" => $linkR1T,
				"title" => $this->view->translate("Störung des Mail-Versands - Again"), 
				"description" => "Leider hat es in der vergangenen Nacht wieder Probleme beim Mail-Versand gegeben. Die Ursache muss noch ergründet werden. Update folgt.");
			
	        $date->set('2010-08-07', Zend_Date::ISO_8601); 
	 		$entries[] = array("lastUpdate" => $date->getTimestamp(), "link" => $linkR1T,
				"title" => $this->view->translate("Mail-Versand - Update"), 
				"description" => "Wir haben die Störung beim Mail-Versand untersucht: die Ursache war eine unglückliche Kombination aus Störungen bei unserem Mail-Provider und PHP-Restriktionen. Eine angepasste Lösung wurde nun implementiert, sodass solche Probleme in Zukunft nicht mehr auftreten sollten. Wir bitte nochmals um Entschuldigung.");
			     
    		$date->set('2010-08-06', Zend_Date::ISO_8601); 
	 		$entries[] = array("lastUpdate" => $date->getTimestamp(), "link" => $linkR1T,
				"title" => $this->view->translate("Störung des Mail-Versands"), 
				"description" => "In der vergangenen Nacht wurden nicht alle Signal-Mails korrekt verschickt. Hierfür bitten wir vielmals um Entschuldigung. Wir sind noch dabei eine Lösung zu erarbeiten, damit dies nicht wieder vorkommt!");
			        
	        
	        $date->set('2010-05-19', Zend_Date::ISO_8601); 
	 		$entries[] = array("lastUpdate" => $date->getTimestamp(), "link" => $linkR1T,
				"title" => $this->view->translate("Payback Time Analyse und einige kleine Verbesserungen"), 
				"description" => "Ab sofort wird bei jeder Regel-1-Analyse auch gleich die Payback Time (nach Phil Towns neuem Buch) berechnet. Hierbei gehen wir nicht den Weg über die Marktkapitalisierung, so wie es Phil Town tut, sondern berechnen die Payback Time mit Hilfe des EPS. Dadurch müssen keine zusätzlichen Daten eingegeben werden. Die Payback Time stellt ein weiteres Mittel dar, den potentiellen Einstiegskurs zu bewerten. Liegt die Payback Time des Kurses unter 10 Jahre, so ist dies schon gut, aber je geringer die Payback Time desto besser!");
		
	        $date->set('2010-04-30', Zend_Date::ISO_8601); 
	 		$entries[] = array("lastUpdate" => $date->getTimestamp(), "link" => $linkR1T,
				"title" => $this->view->translate("Automatische Mails mit neuen Signalen"), 
				"description" => "Für alle Watchlists und Depots kann ab sofort eine tägliche Mail mit den aktuellen (neuen) Signalen abonniert werden, hierzu jeweils bei der Watchlist bzw. Depot neben dem Watchlist/Depot-Namen auf [bearbeiten] klicken und danach das entsprechende Häkchen setzten. Die Mails werden täglich (Mo-Fr.) um ca. 23:40 Uhr versendet.");
		
	        $date->set('2010-04-25', Zend_Date::ISO_8601); 
	 		$entries[] = array("lastUpdate" => $date->getTimestamp(), "link" => $linkR1T,
				"title" => $this->view->translate("Indikatoren: SMA-Zeitraum einstellen"), 
				"description" => $this->view->translate("Seit heute gibt es die Möglichkeit, den Zeitraum für den gleitenden Durchschnitt (SMA) unter Profil -> Einstellungen selbst einzustellen. Man kann zwischen 10 (Standard), 30 und 50 Tagen wählen. Phil Town selbst hat mehrfach empfohlen den SMA(30) oder SMA(50) zu nehmen, falls der SMA(10) zu viele Signale produziert und man dadurch recht häufig ein- bzw. aussteigt."));
		
	 		$date->set('2010-04-12', Zend_Date::ISO_8601); 
	 		$entries[] = array("lastUpdate" => $date->getTimestamp(), "link" => $linkR1T,
	 			"title" => $this->view->translate("Forum: Kooperation mit Regel1Investor.de"), 
				"description" => $this->view->translate("Wir freuen uns, heute bekanntgeben zu dürfen, dass wir ab sofort mit Regel1Investor.de kooperieren. Regel1Investor bietet ein hervorragendes Forum rund um Regel Nummer 1 und Value Investment im Allgemeinen. Hier können Sie Meinungen zu Unternehmen mit Gleichgesinnten austauschen und Ihre eigene Meinung so kritisch beleuchten. Außerdem gibt es ein Unterforum 'Rule1Tool', in dem Sie gerne Meinungen, Fragen und Verbesserungsvorschläge zu Rule1Tool anbringen können. Wir freuen uns auf interessante Diskussionen!"));
		
			$date->set('2010-03-27', Zend_Date::ISO_8601); 
	 		$entries[] = array("lastUpdate" => $date->getTimestamp(), "link" => $linkR1T,
	 			"title" => $this->view->translate("Jetzt Charts mit Zeiträumen bis zu 360 Tagen"), 
				"description" => $this->view->translate("Alle Charts können jetzt in verschiedenen Zeiträumen betrachtet werden &ndash von 60 Tagen bis 360 Tagen (sofern Kursdaten vorhanden). Außerdem wird ab einem Zeitraum von 240 Tagen auch der SMA 200 angezeigt, um noch besser einen langfristigen Trend erkennen zu können."));
			
			$date->set('2010-03-25', Zend_Date::ISO_8601); 
	 		$entries[] = array("lastUpdate" => $date->getTimestamp(), "link" => $linkR1T,
				"title" => $this->view->translate("Neu: Depot-Performance einfach überwachen"), 
				"description" => $this->view->translate("Seit heute gibt es eine neue Funktion bei Rule1Tool: Depots. Das Ziel der Entwicklung ist, eine Möglichkeit zu schaffen, die eigene Investment-Performance, sowohl des aktuellen Depots als auch bisheriger Transaktionen, unkompliziert und schnell im Blick zu behalten. Wir haben uns deshalb für ein 'offenes' Depot entschieden um die Performance der Transaktionen in den Vordergrund zu stellen und uns so auf das Wesentliche zu beschränken; es gibt also keinen festen Depotwert (Depotwert + freies Kapital) und somit auch keine prozentuale Performance, dafür ist die Benutzung aber denkbar einfach! Wie immer: über Feedback würden wir uns freuen!"));
	
			$date->set('2009-09-24', Zend_Date::ISO_8601); 
	 		$entries[] = array("lastUpdate" => $date->getTimestamp(), "link" => $linkR1T,
				"title" => $this->view->translate("Say hello to: Indikatoren"), 
				"description" => $this->view->translate("Heute gibt es etwas ganz besonderes: Indikatoren in der Watchlist! Ab sofort sehen Sie die Kauf- und Verkaufssignale direkt in der Watchlist - ohne sich eine Chart anzusehen. Wir haben für den Anfang die drei Regel-1-Indikatoren implementiert: 10-Tage gleitender Durchschnitt, MACD(8,17,9) und den langsamen Stochastik-Indikator K(14) D(5). Natürlich sind die Indikatoren auch in unserer wunderbaren iPhone-GUI implementiert :). Außerdem werden seit gestern in den Charts die Signal-Punkte durch grüne bzw. rote Punkte dargestellt. Sollte es Probleme, Fragen oder Anregungen geben: einfach eine Nachricht über das Feedback-Formular hinterlassen. Danke und viel Spaß mit den neuen Funktionen!"));
	
			$date->set('2009-09-03', Zend_Date::ISO_8601); 
	 		$entries[] = array("lastUpdate" => $date->getTimestamp(), "link" => $linkR1T,
				"title" => $this->view->translate("iPhone-GUI - Die Watchlist immer dabei"), 
				"description" => $this->view->translate("Mit dem heutigen Update haben wir eine iPhone-Oberfläche eingeführt. Diese neue Ansicht wird automatisch eingeschaltet, sofern man mit einem iPhone oder iPod Touch Rule1Tool aufruft."));
				
			$date->set('2009-06-15', Zend_Date::ISO_8601); 
	 		$entries[] = array("lastUpdate" => $date->getTimestamp(), "link" => $linkR1T,
				"title" => $this->view->translate("Kleines Update, Feedback und der Beschleuniger"), 
				"description" => $this->view->translate("Auf das erste Feedback folgt nun ein kleines Update, das vor allem ein paar Änderung am Analyse-Erstell-Prozess vornimmt - z.B. die Einführung einer Währung. An dieser Stelle noch einmal vielen Dank an die Beta-Tester für das Feedback! Des Weiteren wird es nach dem eher gemäßigten Beta-Start in den nächsten Wochen schneller voran gehen. Stay tuned ;)"));
	
			$date->set('2009-04-30', Zend_Date::ISO_8601); 
	 		$entries[] = array("lastUpdate" => $date->getTimestamp(), "link" => $linkR1T,
			"title" => $this->view->translate("Beta-Phase gestartet"), 
			"description" => $this->view->translate("Die erste Phase der Beta ist gestartet. Wir hoffen auf fleißige Tester. Seid gespannt welche Funktionen in Zukunft noch hinzukommen."));
			   
		
		     // Create the RSS array
		     $rss = array(
		      'title'   => 'Rule1Tool - Aktuelle Meldungen',
		       'link'    => $linkR1T,
		       'charset' => 'UTF-8',
		       'entries' => $entries
		    );
		
		     // Import the array
		     $feed = Zend_Feed::importArray($rss, 'rss');  
		
		     $feed->addAuthor(array(
				    'name'  => 'Martin Böttcher',
				    'email' => Zend_Registry::get("config")->general->mail->from->default->email,
				    'uri'   => $linkR1T,
				));
				
			$date->set('2011-01-12', Zend_Date::ISO_8601); 
			$feed->setDateModified($date->getTimestamp());
			$feed->setFeedLink($this->view->url(array("language" => Zend_Registry::get('Zend_Locale')->getLanguage()
											), "rssr1tnews"), 'rss');
		    // Write the feed to a variable
		    $rssFeed = $feed->saveXML();
			
		    //Cache speichern
		    $cache->save($rssFeed, $identifier.$lastUpdateDate, array('rss_r1t'), 3600*72); //3 Tage Lifetime 	
   		} 
	    $this->view->rssFeed = $rssFeed;
		
	}

	
}