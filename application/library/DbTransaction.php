<?php
/**
 * DbTransaction ermöglichst es, mit hilfe einer Callback-Funktion einfach eine DB-Transaktions-Session zu starten und automatisch zu beenden. 
 * Im Falle es eines Fehler, werden so alle vorgenommenen Änderungen wieder Rückgängig gemacht.
 * 
 **/
class DbTransaction
{
	public static function startTransaction($callback_object, $callback_function, $variables)
	{
    	Zend_Registry::get('Zend_Db')->beginTransaction();
    
    	try {
    		$return = $callback_object->$callback_function($variables);
    		
    		// Wenn alle erfolgreich waren, übertrage die Transaktion und alle Änderungen werden auf einmal übermittelt
			Zend_Registry::get('Zend_Db')->commit();	
			
    		return $return;
    	} catch (Exception $e) {
        	// Wenn irgendeine der Abfragen fehlgeschlagen ist, wirf eine Ausnahme, wir wollen die komplette Transaktion
        	// zurücknehmen, alle durch die Transaktion gemachten Änderungen wieder entfernen auch die erfolgreichen.
        	// So werden alle Änderungen auf einmal übermittelt oder keine.
        	Zend_Registry::get('Zend_Db')->rollBack();
        	throw new Zend_Exception($e->getMessage());
    	}
	}

}