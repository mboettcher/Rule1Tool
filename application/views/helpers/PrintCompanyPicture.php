<?php
class View_Helper_PrintCompanyPicture extends Zend_View_Helper_Abstract
{
	public function printCompanyPicture(Company $company, $size = "m")
	{
		$picture = $company->getPicture();
		if($picture->isCatched())
		{
			$picUrl = "upload/".$picture->getImageId().".png";
		}
		else 
		{
			//Kein Bild vorhanden
			//Standard Bild ausgeben
			$picUrl = "companylogo_standard_75x.png";	
		
		}
		
		if($size == "m")
		{
			$class = "image_companylogo_75";
		}
		elseif($size == "s")
		{
			$class = "image_companylogo_30";
		}
		
		return '<img class="'.$class.'" src="'.$this->view->baseUrlShort()."/public/images/".$picUrl.'" alt=""/>';
	
	}
}