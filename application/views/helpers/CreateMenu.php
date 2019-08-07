<?php

class View_Helper_CreateMenu extends Zend_View_Helper_Abstract
{

	public function createMenu($specialType = null)
	{
		$menu = "<ul>";
		$menu_config = Zend_Registry::get('menu_config');
		$i = 0;
		foreach($menu_config as $menu_point)
		{
			$continue = true;
			
			if(isset($menu_point["onlyauth"]) && $menu_point["onlyauth"] == true)
			{
				if(!Zend_Registry::get("Zend_Auth")->hasIdentity())
					$continue = false;
				else 
				{
					//wenn Identity, dann aber auch die richtigen Rechte bidde
					if(!Zend_Registry::get("Zend_Acl")->isAllowed(Zend_Registry::get("UserObject")->getRole(), $menu_point["controller"], $menu_point["action"]))
						$continue = false;
				}
			}
			/*
			if($specialType == "iPhone" && $menu_point["title"] == "Home")
			{
				$menu_point["status"] = 0; //Home im mobileInterface nicht anzeigen
			}*/
						
			if($continue && $menu_point["status"] == 1)
			{	
				if(Zend_Registry::get('Zend_Controller_Front')->getRequest()->getControllerName() == $menu_point["controller"])
				{
					if($menu_point["action"] == "donate" && Zend_Registry::get('Zend_Controller_Front')->getRequest()->getActionName() != "donate")
						$menu .= "<li>";
					else
						$menu .= "<li class='active'>";					
				}
				else 
					$menu .= "<li>";
					
				/*
				 * profil.route.id = user
profil.route.var.0 = username
profil.route.var.1 = action
				 */
				$url = $this->view->baseUrl()."/".$menu_point["controller"]."/".$menu_point["action"];
				
				//URL ggf. überarbeiten
				if(isset($menu_point["route"]))
				{
					//Url aus Route erstellen
					if(isset($menu_point["route"]["var"]))
					{
						//Variablen befüllen
						$vars = array();
						foreach ($menu_point["route"]["var"] as $var)
						{
							if($var == "username")
								$vars["username"] = Zend_Registry::get("UserObject")->getNickname();
							elseif ($var == "action")
								$vars["action"] = $menu_point["action"];
							elseif ($var == "controller")
								$vars["controller"] = $menu_point["controller"];
						}
						
					}
					$vars["language"] = Zend_Registry::get('Zend_Locale')->getLanguage();
					
					$url = $this->view->url($vars, $menu_point["route"]["id"]);
				}
				
				$link = "<a href=\"".$url."\">".$this->view->translate($menu_point["title"])."</a>";
				
				$menu .= $link;
				$menu .= "</li>";
				
				
				$i++;
			}
			
		}
				
		$menu .= "</ul>";
		return $menu;
	}
}