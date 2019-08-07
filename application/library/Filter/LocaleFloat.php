
<?php
require_once 'Zend/Filter/Interface.php';

/**
*	Wandelt das jeweils lokale Zahlenformat in das normalisierte Format um
* 
* 
* 
* 
* */
class Filter_LocaleFloat implements Zend_Filter_Interface
{
    public function filter($value, $precision = 2)
    {
    	$value = trim($value); //whitespaces am Anfang und Ende entfernen
        // einige Transformationen über $value durchführen um $valueFiltered zu erhalten
		if($value && !is_float($value) && !is_int($value))
		{
			$locale = Zend_Registry::get('Zend_Locale');
			if($locale->toString() == "de_DE")
				$valueFiltered = Zend_Locale_Format::getFloat($value, array('precision' => $precision, 'locale' => $locale));
			else
			{
	
				$valueFiltered = Zend_Locale_Format::getFloat($value,
				                                       array('precision' => $precision,
				                                             'locale' => $locale));
			}
				
	        return $valueFiltered;
		}
		else
			return $value; //Leere Felder kann man nicht filtern

    }
}

