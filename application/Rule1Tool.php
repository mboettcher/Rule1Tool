<?php
class Rule1Tool
{
	protected $config;
	protected $local_config = false;
	protected $log_filter;
	/**
	 * Zend_Controller_Front
	 *
	 * @var Zend_Controller_Front
	 */
	protected $frontController;
	/**
	 * Zend_Db
	 *
	 * @var Zend_Db
	 */
	protected $Zend_Db;
	
	public function run()
	{		
		//Alle Klassen laden sofern sie gebraucht werden! Sowohl Zend Klassen als auch Models, Plugins und eigene Klassen
		Zend_Loader::registerAutoload();
		
		$this->_setupController();
		
		$this->_loadConfig();

		//session_cache_expire(2592000);

		/*
		 * Hinweis: unter debian muss gc_maxlifetime in der php.ini gesetzt werden, da gc per cron erfolgt
		 */
		$time1 = 60*60*24*365; /*= 31536000 */
		$time2 = 60*60*24*30; /*= 2592000 */
		
		Zend_Session::setOptions(array(
				"use_only_cookies" => "on",
				"gc_maxlifetime" => $time2,
				"remember_me_seconds" => $time1 //statt seconds für rememberMe()
		));
		
		//remember_me_seconds manuell setzen, da Zend nur mit regenerateId das macht
		$cookieParams = session_get_cookie_params();
        session_set_cookie_params(
            $time1,
            $cookieParams['path'],
            $cookieParams['domain'],
            $cookieParams['secure']
            );

		Zend_Session::start();
                   
		$this->_setupDb();

		$this->_setupLogProfiler();	
		
		$this->_setupUserObject();

		$this->_setupAuthAndACL();

		$this->_setupView();
		
		$this->_setupLayout();
				
		$this->_setupLog();
		
		$this->_setupTranslate();
		
		$this->_setupRouting();
		
		$this->_setupCache();

		$this->_setupMail();
		
		$this->_setupFilterChainRequest();		
		
		//$this->_setupMessageLibrary();
		
		// run!
		$this->frontController->dispatch();
	}
	protected function _setupAuthAndACL()
	{
		//Auth und ACL
		$auth = Zend_Auth::getInstance();  
		$acl = new Plugin_ACL($auth); // Zend ACL Extension
		  
		//Privileg-Variable setzen
		Zend_Registry::set('Zend_Acl', $acl);	
		Zend_Registry::set('Zend_Auth', $auth);    
		
		//AuthPlugin registrieren, damit es automatisch ausgeführt wird
		$this->frontController->registerPlugin(new Plugin_Auth($auth, $acl)); //Auth
	}
	protected function _setupLogProfiler()
	{
		//Log Profiler
		$this->frontController->registerPlugin(new Plugin_Profiler()); //log_profiler Plugin registrieren
	}
	protected function _setupDb()
	{
		// setup database with befor selected db
		$this->Zend_Db->query('SET NAMES UTF8'); //Definieren, dass Inhalt der DB in UTF8 vorliegt
		Zend_Db_Table::setDefaultAdapter($this->Zend_Db);
		Zend_Registry::set('Zend_Db', $this->Zend_Db);
	}
	protected function _loadSystemConfig()
	{
		//Prüfen auf welchem System wir sind
		/*
		 * rule1tool.com
		 * dev.rule1tool.com
		 * localhost
		 */
		 if(strstr($_SERVER['SERVER_NAME'], "dev.rule1tool.com")){
			$system = "dev";
			$this->log_filter = new Zend_Log_Filter_Priority(Zend_Log::DEBUG);
			$this->Zend_Db = Zend_Db::factory($this->config->general->db->dev->adapter,
								$this->config->general->db->dev->config->toArray());
		}	
		elseif(strstr($_SERVER['SERVER_NAME'], "rule1tool.com")){
			$system = "live";
			$this->log_filter = new Zend_Log_Filter_Priority(Zend_Log::NOTICE);
			$this->Zend_Db = Zend_Db::factory($this->config->general->db->live->adapter,
								$this->config->general->db->live->config->toArray());
								
			//Handling von PHP-Fehlern (z.B. durch trigger_error()) modifizieren
			//error_reporting(E_ALL ^ E_USER_NOTICE ^ E_NOTICE);
			ini_set('display_errors',0); //Keine Fehler ausgeben
			//Alternative: über set_error_handler eigenen Handler definieren, siehe php.net
		}
		elseif(strstr($_SERVER['SERVER_NAME'], "localhost") || strstr($_SERVER['SERVER_NAME'], "192.168."))
		{
			$system = "local";
			$this->log_filter = new Zend_Log_Filter_Priority(Zend_Log::DEBUG);
			/*if($this->local_config)
			{
				$this->Zend_Db = Zend_Db::factory($this->local_config->db->local->adapter,
								$this->local_config->db->local->config->toArray());
			}
			else
			{*/
				$this->Zend_Db = Zend_Db::factory($this->config->general->db->local->adapter,
								$this->config->general->db->local->config->toArray());	
			//}
			
		}
		else
			throw new Zend_Exception("Konnte Serversystem nicht ermitteln. Bitte URL prüfen.");
			
		Zend_Registry::set('systemtype', $system); 
				
		//Systemstatus holen
		Zend_Registry::set('systemstatus', $this->config->general->settings->status->$system); 
		
	}
	protected function _setupController()
	{
		// setup controller
		$this->frontController = Zend_Controller_Front::getInstance();
		$this->frontController->throwExceptions(false);
		$this->frontController->setControllerDirectory(
						array("default" => '../application/controllers/'));
		Zend_Registry::set('Zend_Controller_Front', $this->frontController);
				
		Zend_Controller_Action_HelperBroker::addPrefix('Helper');
	}
	protected function _setupView()
	{
		//initial View
		$view = new Zend_View();
		// additional ViewHelper path ../application/views/helpers 
		//$view->addHelperPath(getcwd() .  DIRECTORY_SEPARATOR . 'application'  . DIRECTORY_SEPARATOR . 'views'  . DIRECTORY_SEPARATOR . 'helpers', 'My_View_Helper'); 
		$view->addHelperPath('../application/views/helpers', 'View_Helper_');
		$view->setEscape('htmlentities');
		$view->setEncoding("UTF-8");
		$viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
		$viewRenderer->setView($view);
		$view->headTitle($this->config->general->layout->head->title->title);
		$view->headTitle()->setSeparator($this->config->general->layout->head->title->separator);
		
		Zend_Registry::set('Zend_View', $view); 
		
		//DOCTYPE
		$doctypeHelper = new Zend_View_Helper_Doctype();
		$doctypeHelper->doctype('XHTML1_STRICT');
	}
	protected function _setupTranslate()
	{
		//translate
		$this->frontController->registerPlugin(new Plugin_Language()); //LanguagePlugin registrieren, damit es automatisch ausgeführt wird
	
	}
	protected function _setupLayout()
	{
		//Layouts
		$options = array(
		    'layout'     => $this->config->general->layout->standard->layout,
		    'layoutPath' => $this->config->general->layout->layoutPath
		);
		// Initialise Zend_Layout's MVC helpers
		$layout = Zend_Layout::startMvc($options);
		Zend_Registry::set('Zend_Layout', $layout); 
	}
	protected function _setupMail()
	{
		// Zend_Mail
		$transport = new Zend_Mail_Transport_Smtp($this->config->general->mail->server, $this->config->general->mail->config->toArray());
		Zend_Mail::setDefaultTransport($transport);
	}
	protected function _setupRouting()
	{
		//Routing
		/* Einen Router erstellen */
		$router = $this->frontController->getRouter(); // gibt standardmässig einen Rewrite Router zurück
		$router->addConfig($this->config->routing, 'routes'); // Routes aus der Config lesen
		
			
		if(strstr($_SERVER["REQUEST_URI"],"/index.php"))
		{
			//diese Aufrufe müssen wir vermeiden!
			//Redirect
			header("Location: http://www.rule1tool.com/");			
		}
	}
	protected function _setupLog()
	{
		//LOG
		$writer = new Zend_Log_Writer_Stream('../logs/application.txt');
		$logger = new Zend_Log($writer);
		$logger->addFilter($this->log_filter); //Filter hinzufügen
		Zend_Registry::set('Zend_Log', $logger); 
		/*
		 * Wie man eine Log schreibt:
		 * 
		 * $logger->log('Informative Nachricht', Zend_Log::INFO); //INFO gegen das Loglevel(name) ersetzen
		 * ODER
		 * $logger->info('Informative Nachricht'); //info gegen das Loglevel(name) ersetzen
		 */
		/*
		 * EMERG   = 0;  // Notfall: System ist nicht verwendbar
		 * ALERT   = 1;  // Alarm: Aktionen müssen sofort durchgefüht werden
		 * CRIT    = 2;  // Kritisch: Kritische Konditionen
		 * ERR     = 3;  // Fehler: Fehler Konditionen
		 * WARN    = 4;  // Warnung: Warnungs Konditionen
		 * NOTICE  = 5;  // Notiz: Normal aber signifikante Kondition
		 * INFO    = 6;  // Informativ: Informative Nachrichten
		 * DEBUG   = 7;  // Debug: Debug Nachrichten
		 */
		//LOG END
		
		//Debug
		/*
		 * Nutze Zend_Debug::dump($var, $label=null, $echo=true); um eine DEBUG-Ausgabe zu erzeugen
		 */
	}
	protected function _loadConfig()
	{
		// load configuration
		$this->config = new Zend_Config_Ini('../application/config/config.ini');
		$registry = Zend_Registry::getInstance();
		$registry->set('config', $this->config);
		
		// load menu config
		$menu_config = new Zend_Config_Ini('../application/config/menu.ini');
		Zend_Registry::set('menu_config', $menu_config->toArray());
		
		//load local config if exist
		if(file_exists('../application/config/local.config.ini'))
		{
			$this->local_config = new Zend_Config_Ini('../application/config/local.config.ini');
			Zend_Registry::set('local_config', $this->local_config);
		}
		$this->_loadSystemConfig();
	}
	protected function _setupCache()
	{
		//Cache
		$frontendOptions = array(
		   'lifetime' => 7200, //Lebensdauer des Caches 2 Stunden (7200)
		   'automatic_serialization' => true
		);
		$backendOptions = array(
		    'cache_dir' => '../cache/' //Verzeichnis, in welches die Cache Dateien kommen
		);
		if(Zend_Registry::get('systemtype') == "local" )
			$cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
		else {
			$cache = Zend_Cache::factory('Core', 'Apc', $frontendOptions, $backendOptions);
		}
		
		if(Zend_Registry::get('systemtype') == "local" )
			$cache->clean(Zend_Cache::CLEANING_MODE_ALL);
			
		Zend_Registry::set('Zend_Cache_Core', $cache); 
		
		//Zend_Date Cache		
		Zend_Date::setOptions(array('cache' => $cache));
		//Zend_Translate Cache
		Zend_Translate::setCache($cache);
		//Zend_Locale Cache
		Zend_Locale::setCache($cache);
	}
    protected function _setupMessageLibrary()
    {
        $lib = new MessageLibrary();
        Zend_Registry::set("MessageLibrary", $lib);
    }
    protected function _setupUserObject()
    {
    	//AuthPlugin registrieren, damit es automatisch ausgeführt wird
		$this->frontController->registerPlugin(new Plugin_UserObject()); 
    }
    protected function _setupFilterChainRequest()
    {
		$filterChain = new Zend_Filter();
		$filterChain->addFilter(new Zend_Filter_StripTags())
		            ->addFilter(new Zend_Filter_StringTrim());
		Zend_Registry::set("FilterChainRequest", $filterChain);
		// Filter the username
		//$username = Zend_Registry::get("FilterChainRequest")->filter($_POST['username']);

    }
}