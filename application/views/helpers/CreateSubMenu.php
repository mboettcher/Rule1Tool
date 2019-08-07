<?php

class View_Helper_CreateSubMenu extends Zend_View_Helper_Abstract
{
	public function createSubMenu()
	{
		$menu = "";
		$menu_config = Zend_Registry::get('menu_config');
		foreach($menu_config as $menu_point)
		{
			if($menu_point["controller"] == Zend_Registry::get('Zend_Controller_Front')->getRequest()->getControllerName())
			{
				$continue_menu = true;
				if(isset($menu_point["onlyauth"]) && $menu_point["onlyauth"] == true)
				{
					if(!Zend_Registry::get("Zend_Auth")->hasIdentity())
						$continue_menu = false;
				}
				$i = 0;
				if($continue_menu && isset($menu_point["sub"]))
				{
					foreach($menu_point["sub"] as $submenu_point)
					{
						$continue = true;	
						if(isset($submenu_point["onlyuser"]) && $submenu_point["onlyuser"] == true)
						{
							if (isset($submenu_point["route"]["var"]["username"]))
							{
								//Vergleiche Nutzernamen
								if(Zend_Registry::get("UserObject")->getNickname() != 
										Zend_Registry::get('Zend_Controller_Front')->getRequest()->getParam("username"))
									$continue = false;
								
							}
							elseif (isset($submenu_point["route"]["var"]["UID"]))
							{
								//Vergleiche UserId
								if(Zend_Registry::get("UserObject")->getUserId() != 
										Zend_Registry::get('Zend_Controller_Front')->getRequest()->getParam("UID"))
									$continue = false;
							}
							else
								throw new Zend_Exception("Keine Überprüfungsvariable für Nutzer gesetzt");
							
						}
						if(isset($menu_point["onlyauth"]) && $menu_point["onlyauth"] == true)
						{
							if(!Zend_Registry::get("Zend_Auth")->hasIdentity())
								$continue = false;
							else 
							{
								//wenn Identity, dann aber auch die richtigen Rechte bidde
								if(!Zend_Registry::get("Zend_Acl")->isAllowed(Zend_Registry::get("UserObject")->getRole(), $submenu_point["controller"], $submenu_point["action"]))
									$continue = false;
							}
						}
						
						if($continue && $submenu_point["status"] == 1)
						{
							$menu .= "<li>";
							
							$url = $this->view->baseUrl()."/".$submenu_point["controller"]."/".$submenu_point["action"];
					
							if(isset($submenu_point["route"]))
							{
								//Url aus Route erstellen
								if(isset($submenu_point["route"]["var"]))
								{
									//Variablen befüllen
									$vars = array();
									foreach ($submenu_point["route"]["var"] as $var)
									{
										if($var == "username")
											$vars["username"] = Zend_Registry::get("UserObject")->getNickname();
										elseif ($var == "action")
											$vars["action"] = $submenu_point["action"];
							
									}
									
								}
								$vars["language"] = Zend_Registry::get('Zend_Locale')->getLanguage();
								
								$url = $this->view->url($vars, $submenu_point["route"]["id"]);
							}
							
							$link = "<a href=\"".$url."\">".$this->view->translate($submenu_point["title"])."</a>";
							
							if(Zend_Registry::get('Zend_Controller_Front')->getRequest()->getActionName() == $submenu_point["action"])
								$menu .= "<strong>".$link."</strong>";
							else
								$menu .= $link;
							$menu .= "</li>";
							
							
							$i++;				
						}				
					}				
				}



			}
			
		}
		if($menu != "")
			return '<div id="container_menu_sub"><ul>'.$menu.'</ul></div>';
		else
			return null;
	}
}