<?php
class View_Helper_PrintUserPicture extends Zend_View_Helper_Abstract
{
	public function printUserPicture(User $user, $size)
	{
		$picture = $user->getPicture($size);
		if($picture->isCatched())
		{
			$picUrl = "upload/".$picture->getImageId().".png";
		}
		else 
		{
			//Kein Bild vorhanden
			//Standard Bild ausgeben
			if ($size == "s")
			{
				$picUrl = "userlogo_standard_60x.jpg";	
			}
			elseif ($size == "m")
			{
				$picUrl = "userlogo_standard_160x.jpg";
			}
			else 
				throw new Zend_Exception("Ungültige Bildgröße gewählt");
		}
		
		if ($size == "s")
		{
			$class = "image_userpic_60";
		}
		elseif ($size == "m")
		{
			$class = "image_userpic_160";
		}	
		
		return '<div class="'.$class.'"><img src="'.$this->view->baseUrlShort()."/public/images/".$picUrl.'" alt=""/></div>';
	
	}
}