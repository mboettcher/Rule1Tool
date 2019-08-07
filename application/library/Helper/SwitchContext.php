<?php
/**
 * @see Zend_Controller_Action_Helper_ContextSwitch
 */
require_once 'Zend/Controller/Action/Helper/ContextSwitch.php';

/**
 * Simplify AJAX context switching based on requested format
 *
 * @uses       Zend_Controller_Action_Helper_ContextSwitch
 */
class Helper_SwitchContext extends Zend_Controller_Action_Helper_ContextSwitch
{
    /**
     * Controller property to utilize for context switching
     * @var string
     */
    //protected $_contextKey = 'ajaxable';

    /**
     * Constructor
     *
     * Add HTML context
     * 
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->addContext("mobile", array("suffix" => "mobile", 'callbacks' => array(
                        'init' => 'initMobileContext'
                    )));
    }

    /**
     * Initialize AJAX/mobile context switching
     *
     * Checks for XHR and iphone requests; if detected, attempts to perform context switch.
     * 
     * @param  string $format 
     * @return void
     */
    public function initContext($format = null)
    {
        $this->_currentContext = null;
		$ns = new Zend_Session_Namespace('Rule1Tool');
		
        if( stristr($_SERVER['HTTP_USER_AGENT'], "Mobile") && stristr($_SERVER['HTTP_USER_AGENT'], "Safari") && !stristr($_SERVER['HTTP_USER_AGENT'],"iPAD"))
		{	//its an iPhone or iPod!
		  	$context = "mobile";
		  	/*
		  	 * iPad UA:
		  	 * Mozilla/5.0 (iPad; U; CPU OS 3_2 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Version/4.0.4 Mobile/7B334b Safari/531.21.10
		  	 */
		}
    	elseif( stristr($_SERVER['HTTP_USER_AGENT'],"Fennec"))
		{	
		  	$context = "mobile";
		}
		elseif( stristr($_SERVER['HTTP_USER_AGENT'],"BlackBerry"))
		{	
		  	$context = "mobile";
		} 
   		elseif( stristr($_SERVER['HTTP_USER_AGENT'],"Maemo"))
		{	
		  	$context = "mobile";
		}
   		elseif( stristr($_SERVER['HTTP_USER_AGENT'],"Opera Mini"))
		{	
		  	$context = "mobile";
		}
		elseif( stristr($_SERVER['HTTP_USER_AGENT'],"Googlebot-Mobile"))
		{	
		  	$context = "mobile";
		}
		elseif($this->getRequest()->isXmlHttpRequest())
			$context = "json";
		else
			$context = null;

		$ns->recommendedLayout = $context;
			
		//Falls Layout manuell gesetzt wurde, überschreiben der Erkennung
    	if(isset($ns->layout) && !$this->getRequest()->isXmlHttpRequest())
        {
        	$layout = $ns->layout;
        	if($layout == "mobile")
        		$context = "mobile";
        	elseif($layout == "standard")
        		$context = null;
        	else 
        		$context = null;
        }	
			
        return parent::initContext($context);
    }
    
    public function initMobileContext()
    {
    	$this->setAutoDisableLayout(false);
    	 /**
         * @see Zend_Layout
         */
        require_once 'Zend/Layout.php';
        $layout = Zend_Layout::getMvcInstance();
        $layout->setLayout("mobile");
    }
}
