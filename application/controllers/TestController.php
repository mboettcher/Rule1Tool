<?php

/**
 * TestController
 * 
 * Ein Controller zum testen!
 * 
 * @author
 * @version 
 */
	
require_once 'Zend/Controller/Action.php';

class TestController extends Zend_Controller_Action
{
	
    public function mailAction()
    {
    	$mail = new Mail(Zend_Registry::get("config")->general->mail->from->default->email);
    	$data = array("username" => "Ulf", "email" => "mail@rule1tool.com");
    	$mail->sendRegistrationMail($data);
    	//unset($mail);
    	echo "letz fets!";
    }
    public function errorAction()
    {
    	throw new Zend_Exception("Absichtlicher Fehler");
    }
    public function waitAction()
    {
    	sleep(15);
    }
    public function testAction()
    {
    	
    	
        $crawlelist[] = array(
                "company_id" => 1, 
                "market_id" => 3,
                "symbol" => "AAPL",
                "symbolextension" => null
        );
        $crawlelist[] = array(
                "company_id" => 7, 
                "market_id" => 3,
                "symbol" => "^DJI",
                "symbolextension" => null
        );
   				
        set_time_limit(90);
        //in hunderter pakete teilen
        Quotes::crawleEODQuotes($crawlelist, true);
        
    	
    	
    }
    public function googleAction()
    {
        //$y = new YahooFinanceStockQuotes("AAPL",Zend_Registry::get('config')->general->proxy->toArray());print_r($y->getResponse());EXIT;
        $g = new GoogleFinanceStockQuotes(array("^DJI","AAPL"));
        print_r($g->getResponse());
            
        
    }
  
    
}
