<?php 

class Company_PictureSet extends Abstraction
{
	protected $pictures = array();
	protected $company_id = null;
	
	protected $dataCatched = false; //Datensatz bereits geladen?
	
	public function getPictures()
	{
		
	}
	
	public function input($company_id)
	{
		$uploadAdapter = new Zend_File_Transfer_Adapter_Http();
	   			
		$temp_dir = Zend_Registry::get("config")->general->upload->general->temp;
		
		$uploadAdapter->setDestination($temp_dir);
		
		$uploadAdapter->addValidator("Count", false, 1)
					->addValidator("FilesSize", false, "1MB")
					;
		$uploadAdapter->addValidator('IsImage', true);			
		$uploadAdapter->addValidator('ImageSize', false,
                 array('minwidth' => 40,
                       'maxwidth' => 2000,
                       'minheight' => 40,
                       'maxheight' => 2000
                ));
                
		if(!$uploadAdapter->isUploaded())	//Error
			$this->_getMessageBox()->setMessage("MSG_PICTURE_INPUT_001");
		else
		{

			if(!$uploadAdapter->isValid())	//Error
				$this->_getMessageBox()->setMessagesDirect($uploadAdapter->getMessages());
			else 
			{
				$uploadAdapter->receive();
	
				$filePath = $uploadAdapter->getFileName(null, true);

				$company = new Company($company_id);
				
				$imageId_old = $company->getImageId();
				
				//SMALL
				$image = new Image();
				$image->setImage($filePath, array("width" => 75, "height" => 90));
				$this->pictures["s"] = $image->getImageId();
				unset($image);
				//BildId beim User einfügen
				$company->setImageId($this->pictures["s"]);
				
				//Ausgangs-Datei löschen
				unlink($filePath);
			
				//Änderungen speichern
				$save = $company->save();
				if(!$save)
				{
					//@TODO nicht eingefügte Bilder löschen
					$this->_getMessageBox()->setMessagesDirect($user->getMessageBox()->getMessages());
					return false;
				}
				
				//Alte Bilder löschen
				
				
				//Aus DB löschen
				$imageModel = new ImagesModel();
				$rows = $imageModel->find(array($imageId_old));
				foreach ($rows as $row)
					$row->delete();
				
				//Dateien löschen
				$dir = Zend_Registry::get("config")->general->upload->images->destination;
				if($imageId_old)
				{
					$path = $dir.$imageId_old.".png";
					unlink($path);
				}
				return $this;
			}			
		}
		return false;
	}
	

}