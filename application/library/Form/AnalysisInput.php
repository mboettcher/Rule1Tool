<?php
/**
 * Formular für die Eingabe der Analysedaten
 */
class Form_AnalysisInput extends Zend_Form 
{ 
	protected $_filters;
	protected $_validators;
	protected $_basicdata;
	
    public function __construct($options = null, $company_id, $analysis_id = null) 
    {
    	parent::__construct($options);
        $this->createForm($company_id, $analysis_id);
        
    }
    protected function createForm($company_id, $analysis_id)
    {
    	$this->_filters = (object) NULL;
    	$this->_filters->int = new Zend_Filter_Int();
    	//$this->_filters->Float = new Filter_Float();
    
    	$this->_validators = (object) NULL;
    	$this->_validators->localeFloat = new Validate_LocaleFloat();
    	//$this->_validators->int = new Zend_Validate_Int();
    	
    	//$this->setIsArray(true);
    	
    	$this->setName('analysis_input');
		$this->setAction('')
     			->setMethod('post');
      	$this->setDecorators(array(array('ViewScript', array(
  				  'viewScript' => 'forms/analysis_input.phtml'))));

      	$this->_basicdata = new Zend_Form_SubForm("basicdata");
        $this->_basicdata->setIsArray(true)
        	->setElementsBelongTo("basicdata");

        if($analysis_id === NULL)
            $this->_createForm($company_id);
        else 
            $this->_editForm($analysis_id);
        
        $note = new Zend_Form_Element_Textarea('note');
        $note->setLabel('Bemerkung')
              //->setRequired(true)
              //->addValidator('NotEmpty', true)
              ->addValidator(new Zend_Validate_StringLength(0,255), true)
              ->setAttrib("rows", 6)
              ->setAttrib("cols", 40)
              ->setDecorators(array(array('ViewScript', array(
							    'viewScript' => 'forms/_analysis_input_element.phtml',
							    'class'      => ''
								)))); 
 		$this->_basicdata->addElement($note);
 		
 		$moat = new Zend_Form_Element_Textarea('moat');
        $moat->setLabel('Burggraben')
              //->setRequired(true)
              //->addValidator('NotEmpty', true)
              ->addValidator(new Zend_Validate_StringLength(0,500), true)
              ->setAttrib("rows", 6)
              ->setAttrib("cols", 40)
              ->setDecorators(array(array('ViewScript', array(
							    'viewScript' => 'forms/_analysis_input_element.phtml',
							    'class'      => ''
								)))); 
		$moat->setDescription("Der Burggraben (oder auch Alleinstellungsmerkmal) ist das wesentliche an einem Regel1-Unternehmen. Der Burggraben unterscheidet ein Regel1-Unternehmen von einem anderen und macht es langfristig erfolgreich.");
 		$this->_basicdata->addElement($moat);
 		
 		$management = new Zend_Form_Element_Textarea('management');
        $management->setLabel('Management')
              //->setRequired(true)
              //->addValidator('NotEmpty', true)
              ->addValidator(new Zend_Validate_StringLength(0,500), true)
              ->setAttrib("rows", 6)
              ->setAttrib("cols", 40)
              ->setDecorators(array(array('ViewScript', array(
							    'viewScript' => 'forms/_analysis_input_element.phtml',
							    'class'      => ''
								)))); 
		$management->setDescription("Das Management eines Unternehmen ist für den langfristigen Erfolg eines Unternehmens verantwortlich. Prüfen Sie also, ob das Management langfristig und im Sinne des Unternehmens denkt und handelt.");
 		$this->_basicdata->addElement($management);
 		
 		$private = new Zend_Form_Element_Select("private");
 		$private
 				->setLabel("Sichtbarkeit")
 				->addValidator(new Zend_Validate_Between(0,1))
 				->addMultiOptions(array(0 => "öffentlich", 1 => "privat"))
 				->setDecorators(array(array('ViewScript', array(
							    'viewScript' => 'forms/_analysis_input_element_select.phtml',
							    'class'      => ''
								)))); 
		$private->setDescription("Setzen Sie die Sichtbarkeit auf öffentlich, damit die Community Ihre Analyse sehen kann. Andere Mitglieder können dann - aufbauend auf Ihren Daten - eigene Analysen erstellen, von denen Sie wiederum profitieren können.");
 		$this->_basicdata->addElement($private);
 		
 		
 		$currency = new Zend_Form_Element_Select("currency");
 		$currency
 				->setLabel("Währung")
 				->addValidator(new Zend_Validate_InArray(Zend_Registry::get("config")->general->currencies->toArray()))
 				->addMultiOptions(Zend_Registry::get("config")->general->currencies->toArray())
 				->setDecorators(array(array('ViewScript', array(
							    'viewScript' => 'forms/_analysis_input_element_select.phtml',
							    'class'      => ''
								)))); 
		
		$currency->setDescription("Alle Kennzahlen müssen in der gleichen Währung vorliegen. Um die Zahlen nicht durch Währungsschwankungen zu verfälschen, sollte es in der Regel immer die Währung des Landes sein, aus dem das Unternehmen stammt.");
 		$this->_basicdata->addElement($currency);
 		
 		
 		$this->_createTextElement('analysts_estimated_growth', 'Erwartetes Wachstum (Analysten)', true);
        $this->_createTextElement('current_eps', 'Aktueller Ertrag pro Aktie (EPA)', false);
        $this->_createTextElement('my_estimated_growth', 'Erwartetes Wachstum (Eigenes)', true);
       	$this->_createTextElement('my_future_kgv', 'Zukünftige KGV (Eigenes)', true);
        
       	$this->addSubForm($this->_basicdata, 'basicdata');
       	
        //Jahresdaten
        $keydatasform = new Zend_Form_SubForm('keydata');
        $keydatasform->setIsArray(true);
        $keydatasform->setElementsBelongTo('keydata');
        
        $year_1 = $this->_createYearSubform(1);
        $year_2 = $this->_createYearSubform(2);
        $year_3 = $this->_createYearSubform(3);
        $year_4 = $this->_createYearSubform(4);
        $year_5 = $this->_createYearSubform(5);
        $year_6 = $this->_createYearSubform(6);
        $year_7 = $this->_createYearSubform(7);
        $year_8 = $this->_createYearSubform(8);
        $year_9 = $this->_createYearSubform(9);
        $year_10 = $this->_createYearSubform(10);
        	
        $year = $this->_createKeydataTextElement('year', "Int");   
        $income_after_tax = $this->_createKeydataTextElement('income_after_tax', "Float");        
        $revenue = $this->_createKeydataTextElement('revenue', "Float");         
        $equity = $this->_createKeydataTextElement('equity', "Float");         
        $eps = $this->_createKeydataTextElement('eps', "Float");         
        $cashflow = $this->_createKeydataTextElement('cashflow', "Float");         
        $depts = $this->_createKeydataTextElement('depts', "Float");         
        $kgv = $this->_createKeydataTextElement('kgv', "Float");
        
		
        $year_1 = $this->_SubformYearAddElements($year_1, 1, $year, $income_after_tax, $revenue, 
    			$equity, $eps, $cashflow, $depts, $kgv);       
    	$year_2 = $this->_SubformYearAddElements($year_2, 2, $year, $income_after_tax, $revenue, 
    			$equity, $eps, $cashflow, $depts, $kgv);          
    	$year_3 = $this->_SubformYearAddElements($year_3, 3, $year, $income_after_tax, $revenue, 
    			$equity, $eps, $cashflow, $depts, $kgv);         
    	$year_4 = $this->_SubformYearAddElements($year_4, 4, $year, $income_after_tax, $revenue, 
    			$equity, $eps, $cashflow, $depts, $kgv);         
    	$year_5 = $this->_SubformYearAddElements($year_5, 5, $year, $income_after_tax, $revenue, 
    			$equity, $eps, $cashflow, $depts, $kgv);         
    	$year_6 = $this->_SubformYearAddElements($year_6, 6, $year, $income_after_tax, $revenue, 
    			$equity, $eps, $cashflow, $depts, $kgv); 
        $year_7 = $this->_SubformYearAddElements($year_7, 7, $year, $income_after_tax, $revenue, 
    			$equity, $eps, $cashflow, $depts, $kgv); 
        $year_8 = $this->_SubformYearAddElements($year_8, 8, $year, $income_after_tax, $revenue, 
    			$equity, $eps, $cashflow, $depts, $kgv); 
        $year_9 = $this->_SubformYearAddElements($year_9, 9, $year, $income_after_tax, $revenue, 
    			$equity, $eps, $cashflow, $depts, $kgv); 
        $year_10 = $this->_SubformYearAddElements($year_10, 10, $year, $income_after_tax, $revenue, 
    			$equity, $eps, $cashflow, $depts, $kgv); 
           
		$keydatasform->addSubForm($year_1, 'a');
		$keydatasform->addSubForm($year_2, 'b');
		$keydatasform->addSubForm($year_3, 'c');
		$keydatasform->addSubForm($year_4, 'd');
		$keydatasform->addSubForm($year_5, 'e');
		$keydatasform->addSubForm($year_6, 'f');
		$keydatasform->addSubForm($year_7, 'g');
		$keydatasform->addSubForm($year_8, 'h');
		$keydatasform->addSubForm($year_9, 'i');
		$keydatasform->addSubForm($year_10, 'j');
		
		$this->addSubForm($keydatasform, 'keydata');

		$options = array('disableLoadDefaultDecorators' => true, "decorators" => array(array('ViewHelper')));
		
        $submit = new Zend_Form_Element_Submit('submit', $options);
        $submit->setLabel('Speichern');
        $this->addElement($submit);
                
        //moveOneYear-Button
        $moveOneYear = new Zend_Form_Element_Submit('moveOneYear',$options);
        $moveOneYear->setLabel('Spalte hinzufügen');
        $this->addElement($moveOneYear);
    }
    protected function _editForm($analysis_id)
    {
    	$options = array('disableLoadDefaultDecorators' => true, "decorators" => array(array('ViewHelper')));
        $analysis = new Zend_Form_Element_Hidden('analysis_id', $options);
        $analysis->setRequired(true)
                 ->addValidator('NotEmpty', true)
                 ->addValidator(new Validate_AnalysisId(), true)
                 ->setValue($analysis_id);
        $this->_basicdata->addElement($analysis);
        
    }
    protected  function _createForm($company_id)
    {
    	$options = array('disableLoadDefaultDecorators' => true, "decorators" => array(array('ViewHelper')));
        $company = new Zend_Form_Element_Hidden('company_id',$options);
        $company->setRequired(true)
                     ->addValidator('NotEmpty', true)
                     ->addValidator(new Validate_CompanyId(), true)
                     ->setValue($company_id);
        $this->_basicdata->addElement($company);
        
    }
    protected function _createKeydataTextElement($id, $numbertype = "Float")
    {
    	$el = new Zend_Form_Element_Text($id,
                                 array('disableLoadDefaultDecorators' => true));        
	    $el //->setRequired(true)
                 //->addValidator('NotEmpty',true)
                 ->setDecorators(array(array('ViewScript', array(
							    'viewScript' => 'forms/_analysis_input_element_keydata.phtml'
								))))
				 //->setAllowEmpty(true)
                 ->setAttrib("size", 5);
        
        if($numbertype == "Int")
			$el->addFilter($this->_filters->int);
		else
			$el->addValidator($this->_validators->localeFloat, true);
                 
        return $el;
    }
    protected function _createYearSubform($year)
    {
        $subform = new Zend_Form_SubForm($year);
        $subform->setIsArray(true)
        	->setElementsBelongTo("keydata[$year]");
       	return $subform;
    }
    protected function _SubformYearAddElements($subform, $numberyear, $year, $income_after_tax, $revenue, 
    			$equity, $eps, $cashflow, $depts, $kgv)
    {
    	if($numberyear >= 5)
    		$allowempty = true;
    	else
    		$allowempty = false;
    	$numberyear = $numberyear -1;
    	$jahreszahl = date("Y")-$numberyear;	
    		
    	$year_x = clone $year;
    	$year_x->setValue($jahreszahl);
    	$year_x->setAllowEmpty(false);
    	
    	$income_after_tax_x = clone $income_after_tax;
    	$income_after_tax_x->setAllowEmpty($allowempty);
    	$revenue_x = clone $revenue;
    	$revenue_x->setAllowEmpty($allowempty);
    	$equity_x = clone $equity;
    	$equity_x->setAllowEmpty($allowempty);
    	$eps_x = clone $eps;
    	$eps_x->setAllowEmpty($allowempty);
    	$cashflow_x = clone $cashflow;
    	$cashflow_x->setAllowEmpty($allowempty);
    	$depts_x = clone $depts;
    	$depts_x->setAllowEmpty($allowempty);
    	$kgv_x = clone $kgv;
    	$kgv_x->setAllowEmpty($allowempty);
    	
    	$subform->addElement($year_x);
    	$subform->addElement($income_after_tax_x);
    	$subform->addElement($revenue_x);
    	$subform->addElement($equity_x);
    	$subform->addElement($eps_x);
    	$subform->addElement($cashflow_x);
    	$subform->addElement($depts_x);
    	$subform->addElement($kgv_x);
        
    	return $subform;
    }
    protected function _createTextElement($id, $label, $allowempty = false)
    {
        $el = new Zend_Form_Element_Text($id);
        $el->setLabel($label)
                 ->setRequired(!$allowempty)
                 ->addValidator($this->_validators->localeFloat, true)
                 ->setAllowEmpty($allowempty)
                 ->setDecorators(array(array('ViewScript', array(
							    'viewScript' => 'forms/_analysis_input_element.phtml',
							    'class'      => ''
								))))
		;
                 
        $this->_basicdata->addElement($el);
    }
    
    public function isValid($values, $moveOneYear = false)
    {
    	if($moveOneYear)
    	{
    		//Jahresdaten um eins nach hinten verrücken
    		//keydata[1][year]
    		$tmp = array();
    		foreach ($values["keydata"] as $key => $valuearray)
    		{
    			$tmp[$key+1] = $valuearray;
    		}
    		//neues Jahr ergänzen
    		$tmp[1] = array("year" => $tmp[2]["year"]+1);
    		$values["keydata"] = $tmp;
    	}
    	return parent::isValid($values);
    }
    
}