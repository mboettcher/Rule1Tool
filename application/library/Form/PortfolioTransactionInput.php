<?php
/**
 * Formular für die Eingabe einer Transaktion
 */
class Form_PortfolioTransactionInput extends Zend_Form 
{ 

    public function __construct($options = null, $values = array()) 
    {
    	parent::__construct($options);
        $this->createForm($values);
        
    }
    protected function createForm($values)
    {
    	$this->setMethod('post');
    	
		//liste aller Portfolios holen
		$portmodl = new PortfolioModel();
		$prows = $portmodl->fetchAll($portmodl->select()->where("user_id = ?", Zend_Registry::get("UserObject")->getUserId()));
		
		//liste aller Companys holen
		$commodl = new CompaniesModel();
		$crows = $commodl->fetchAll($commodl->select()->where("type != ?", 2)->order("name ASC"));

        $options = array('disableLoadDefaultDecorators' => true, "decorators" => array(array('ViewHelper')));
		
        //Portfolioauswahl
        if(count($prows) > 1 && !$values["portfolio_id"])
        {
	        $pid = new Zend_Form_Element_Select("portfolio_id", $options);
			foreach ($prows as $prow)
			{
				$pid->addMultiOption($prow->id, $prow->name);
			}
			if($values["portfolio_id"])
        		$pid->setValue($values["portfolio_id"]);
        }
        else 
        {
        	$pid = new Zend_Form_Element_Hidden("portfolio_id", $options);
        	if($values["portfolio_id"])
        		$pid->setValue($values["portfolio_id"]);
        	else
        		$pid->setValue($prows->current()->id);
        }
        
        //Unternehmensauswahl
		$cid = new Zend_Form_Element_Select("company_id", $options);
		$cid->setAttrib("size", 5);
		foreach ($crows as $crow)
		{
			$cid->addMultiOption($crow->company_id, $crow->name);
		}
		if($values["company_id"])
        		$cid->setValue($values["company_id"]);
		$cid->setAttrib("class", "flexselect");
		
		$price = new Zend_Form_Element_Text("price", $options);
		$price->setAttrib("size", 6);
		
		$anzahl = new Zend_Form_Element_Text("anzahl", $options);
		$anzahl->setAttrib("size", 5);
		if($values["anzahl"])
        		$anzahl->setValue($values["anzahl"]);
		
		$date = new Zend_Form_Element_Text("date", $options);
		$date->setAttrib("size", 8);
		
		$time = new Zend_Form_Element_Text("time", $options);
		$time->setAttrib("size", 4);
		
		$gebuehren = new Zend_Form_Element_Text("gebuehren", $options);
		
		$type = new Zend_Form_Element_Hidden("type", $options);
		
        $submit = new Zend_Form_Element_Submit("submit", $options);
        $submit->setAttrib("class","abutton blue large");
        $submit->setLabel("Transaktion speichern");     
        
		$this->addElements(array($pid, $cid, $date, $time, $price, $anzahl, $gebuehren, $submit, $type));
		
		$this->setDefault("gebuehren", 0);
		$this->setDefault("time", "12:00");
		$this->setDefault("type", "buy");	
		
		if($values["type"])
        	$type->setValue($values["type"]);
				
		$this->setDecorators(array(array('ViewScript', array(
						    'viewScript' => 'forms/_portfolio_transaction_add.phtml',
						    'class'      => ''
							))));
		
    	
    }
    
}