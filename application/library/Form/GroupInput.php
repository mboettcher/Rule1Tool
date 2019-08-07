<?php
class Form_GroupInput extends Zend_Form 
{
	public function __construct($options = null) 
    {
    	parent::__construct($options);
    	
        $this->setMethod('post');

    	
        //$options = array('disableLoadDefaultDecorators' => true);
        $options = null;
        $title = new Zend_Form_Element_Text("title", $options);
		$description = new Zend_Form_Element_Textarea("description", $options);
		$open = new Zend_Form_Element_Checkbox("open");
		$language = new Zend_Form_Element_Select("language");
		
		$system_langs = Zend_Registry::get("config")->general->language->toArray();
   		foreach ($system_langs as $lang)
		{
			$language->addMultiOption($lang["short"], $lang["long"]);
		}
		$language->addMultiOption(NULL, $this->getView()->translate("ALLE"));
		
        $submit = new Zend_Form_Element_Submit("submit", $options);
        
        $submit->setLabel("Gruppe anlegen");     
        
		$this->addElements(array($title, $description, $open, $language, $submit));
	}
}
?>