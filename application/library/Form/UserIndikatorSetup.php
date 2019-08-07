<?php

class Form_UserIndikatorSetup extends Zend_Form 
{
	protected $eleOptions;
	
	 public function __construct(User $user)
    {
        parent::__construct();
        
		//$this->setAction(Zend_Registry::get('Zend_View')->url(array("language" => Zend_Registry::get('Zend_Locale')->getLanguage(), "username" => $user->getNickname()), "user_indikators"));
        $this->setMethod('post');
        
		//$this->eleOptions = array('disableLoadDefaultDecorators' => true, "decorators" => array(array('ViewHelper')));
		$this->eleOptions = array();
		
		/** STANDARD EINSTELLUNGEN **/
		/*
		$standardIndikators = array(
								"buy" => array(
											"numberSignalIndikators" => 3,
											"SMA" => array(array("period" => 30)),
											"MACD" => array(array("fastEMA" => 8, "slowEMA" => 17, "signalEMA" => 9)),
											"STO" => array(array("k" => 14, "d" => 5, "type" => "slow"), array("k" => 14, "d" => 5, "type" => "fast"))
										)
								,"sell" => array(
											"numberSignalIndikators" => 2,
											"SMA" => array(array("period" => 10)),
											"MACD" => array(array("fastEMA" => 8, "slowEMA" => 17, "signalEMA" => 9)),
											"STO" => array(array("k" => 14, "d" => 5, "type" => "slow"), array("k" => 14, "d" => 5, "type" => "fast"))
										)
							);
		*/
		
		/** EINSTELLUNGEN **/
		$einstellungen = new Zend_Form_SubForm();
		$numberSignalIndikators = new Zend_Form_Element_Text('numberSignalIndikators', $this->eleOptions);
		$einstellungen->addElements(array($numberSignalIndikators));
		$this->addSubForm($einstellungen, 'Einstellungen');	
			
		/** INDIKATORS **/
		$this->addSubForm($this->createSMAInput(), 'SMA');	
		//$this->addSubForm($this->createEMAInput(), 'EMA');	
		$this->addSubForm($this->createMACDInput(), 'MACD');	
		$this->addSubForm($this->createSTOInput(), 'STO');	
			
		
        $submit = new Zend_Form_Element_Submit("submit", $this->eleOptions);
        $submit->setLabel("Indikator hinzufügen");
        //$reset = new Zend_Form_Element_Reset("reset", $this->eleOptions);
        //$reset->setLabel("Abbrechen");        
        
		
		$this->addElements(array($submit));
		/*$this->setDecorators(array(array('ViewScript', array(
							    'viewScript' => 'forms/_user_edit.phtml',
							    'class'      => ''
								))));
		*/
	}
	/**
	 * Erstellt ein Subform mit passenden Elementen
	 *
	 * @return Zend_Form_SubForm
	 */
	protected function createSMAInput()
	{
		$form = new Zend_Form_SubForm();
		
		$ele = new Zend_Form_Element_Text('period', $this->eleOptions);
		$ele->setLabel("Period");
		
		$form->addElements(array($ele));
		return $form;
	}
	/**
	 * Erstellt ein Subform mit passenden Elementen
	 *
	 * @return Zend_Form_SubForm
	 */
	protected function createEMAInput()
	{
		$form = new Zend_Form_SubForm();
		
		$ele = new Zend_Form_Element_Text('period', $this->eleOptions);
		$ele->setLabel("Period");
		
		$form->addElements(array($ele));
		return $form;
	}
	/**
	 * Erstellt ein Subform mit passenden Elementen
	 *
	 * @return Zend_Form_SubForm
	 */
	protected function createMACDInput()
	{
		$form = new Zend_Form_SubForm();
		
		//array("fastEMA" => 8, "slowEMA" => 17, "signalEMA" => 9)
		$fastEMA = new Zend_Form_Element_Text('fastEMA', $this->eleOptions);
		$fastEMA->setLabel("fastEMA");
		
		$slowEMA = new Zend_Form_Element_Text('slowEMA', $this->eleOptions);
		$slowEMA->setLabel("slowEMA");
		
		$signalEMA = new Zend_Form_Element_Text('signalEMA', $this->eleOptions);
		$signalEMA->setLabel("signalEMA");
		
		$form->addElements(array($fastEMA,$slowEMA,$signalEMA));
		return $form;
	}
	/**
	 * Erstellt ein Subform mit passenden Elementen
	 *
	 * @return Zend_Form_SubForm
	 */
	protected function createSTOInput()
	{
		$form = new Zend_Form_SubForm();
		
		//array("k" => 14, "d" => 5, "type" => "slow"
		$k = new Zend_Form_Element_Text('k', $this->eleOptions);
		$k->setLabel("k");
		
		$d = new Zend_Form_Element_Text('d', $this->eleOptions);
		$d->setLabel("d");
		
		$type = new Zend_Form_Element_Text('type', $this->eleOptions);
		$type->setLabel("type");
		
		$form->addElements(array($k, $d, $type));
		return $form;
	}
	
}

?>