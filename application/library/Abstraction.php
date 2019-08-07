<?php
/**
 * Abstrakte Klasse, um gewisse Funktionen überall benutzen zu können
 *
 */
abstract class Abstraction
{
	/**
	 * MessageBox-Objekt
	 *
	 * @var MessageBox
	 */
	protected $_MessageBox;
	
	/**
	 * Zend_View_Helper_Translate-Objekt
	 *
	 * @var Zend_View_Helper_Translate
	 */
	protected $_Translate;
	
	/**
	 * Gibt eine Instance von MessageBox zurück
	 *
	 * @return MessageBox
	 */
	protected function _getMessageBox()
	{
		if($this->_MessageBox instanceof MessageBox)
			return $this->_MessageBox;
		else
		{
			$this->_MessageBox = new MessageBox();
			return $this->_MessageBox;
		}	
	}
	/**
	 * Gibt eine Instance von Zend_View_Helper_Translate zurück
	 *
	 * @return Zend_View_Helper_Translate
	 */
	protected function _getTranslate()
	{
		if($this->_Translate instanceof Zend_View_Helper_Translate)
			return $this->_Translate;
		else
		{
			$this->_Translate = new Zend_View_Helper_Translate(Zend_Registry::get('Zend_Translate'));
			return $this->_Translate;
		}	
	}
	/**
	 * Gibt die Messages aus der MessageBox als Array zurück
	 *
	 * @return ARRAY
	 */
	public function getMessages()
	{
		return $this->_getMessageBox()->getMessages();
	}
	/**
	 * Alias für getMessages
	 *
	 * @return getMessages
	 */
	public function getErrorlist()
	{
		return $this->getMessages();
	}
	/**
	 * Gibt Objekt zurück
	 *
	 * @return MessageBox
	 */
	public function getMessageBox()
	{
		return $this->_getMessageBox();
	}
}