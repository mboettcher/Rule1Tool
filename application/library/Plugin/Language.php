<?php
class Plugin_Language extends Zend_Controller_Plugin_Abstract
{
	protected $translate;
	
	public function preDispatch(Zend_Controller_Request_Abstract $request)
	{
	
		$this->translate = new Zend_Translate('gettext', '../application/languages', null, array("scan" => Zend_Translate::LOCALE_DIRECTORY));
				
		$lang = $this->_request->getParam("language");

		/*
		 * Theorie:
		 * Prüfe:
		 * 1. Parameter :language
		 * 2. Session-User-Einstellungen
		 * 3. benutze Local
		 */
/*
 * // Wenn das Gebietsschema 'de_AT' ist, wird 'de' als Sprache zurückgegeben
print $locale->getLanguage();

// Wenn das Gebietsschema 'de_AT' ist, wird 'AT' als Region zurückgegeben
print $locale->getRegion();
 */

		//set user language
		Zend_Locale::setDefault('de_DE');
		
		$locale = new Zend_Locale();
		
		//language-parameter prüfen
		if($lang != false && $lang != "")
		{
			//language-parameter als gewünschte sprache setzen
			$chosenLang = $lang;
		}
		else 
		{
			//Browser/Os-Sprache als gewünschte Sprache setzen
			$chosenLang = $locale->getLanguage();
		}
		
		//Egal woher die Sprache kommt, Sie muss auf verfügbarkeit geprüft werden
		$local_string = $this->validateLanguage($chosenLang);
		if($local_string)
		{
			$locale->setLocale($local_string); //Local richtig setzen
		}
		else 
		{
			//ansonsten defaulten
			$locale->setLocale('de_DE');
		}
		 
		$this->translate->getAdapter()->setLocale($locale);
			

		//use Zend_Registry::get('Zend_Locale')->getLanguage() to get language in script
		//use Zend_Registry::get('Zend_Locale')->toString() to get local in script (z.B.: de OR de_DE)
		Zend_Registry::set('Zend_Locale', $locale);
		Zend_Registry::set('Zend_Translate', $this->translate);

		
		//echo Zend_Registry::get('Zend_Locale')->getLanguage();
		//echo Zend_Registry::get('Zend_Locale')->toString();

		//Usage:

		/*
		 * Mit Zend_View_Helper_Translate
		 * 
		 * // without your View
		 * $translate = new Zend_View_Helper_Translate(Zend_Registry::get('Zend_Translate'));
		 * print $translate->translate('simple'); // this returns 'einfach'
		 * 
		 * // within your view
		 * $this->translate('simple');
		 * 
		 * see http://framework.zend.com/manual/en/zend.view.helpers.html#zend.view.helpers.initial.translate for included parameters like:
		 * $date = "Monday";
		 * $this->translate("Today is %1\$s", $date);
		 * // could return 'Heute ist Monday'
		 */
		
	}
	/*
	 * 
	 */
	protected function validateLanguage($lang)
	{
		if($this->translate->isAvailable($lang))
		{
			//supi
			$supported_langs = array(
									"de" => "de_DE"
									//, "en" => "en_US"
									);
			if(isset($supported_langs[$lang]))
			{
				return $supported_langs[$lang];
			}
			else
			{
				//auf standard zurückgreifen
				return false;
			}
		}
		else
		 return false;
	
	}
}