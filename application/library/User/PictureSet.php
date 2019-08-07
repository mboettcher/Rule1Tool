<?php 

class User_PictureSet extends Abstraction
{
	protected $pictures = array();
	protected $user_id = null;
	
	protected $dataCatched = false; //Datensatz bereits geladen?
	
	public function getPictures()
	{
		
	}
	
	public function input($user_id)
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

				$user = new User($user_id);
				
				$imageIdS_old = $user->getImageIdS();
				$imageIdM_old = $user->getImageIdM();
				
				//SMALL
				$image = new Image();
				$image->setImage($filePath, array("width" => 60, "height" => 60));
				$this->pictures["s"] = $image->getImageId();
				unset($image);
				//BildId beim User einfügen
				$user->setImageIdS($this->pictures["s"]);
				
				//MEDIUM
				$image = new Image();
				$image->setImage($filePath, array("width" => 160, "height" => 160));
				$this->pictures["m"] = $image->getImageId();
				unset($image);
				$user->setImageIdM($this->pictures["m"]);

							
				//Ausgangs-Datei löschen
				unlink($filePath);
			
				//Änderungen speichern
				$save = $user->save();
				if(!$save)
				{
					//@TODO nicht eingefügte Bilder löschen
					$this->_getMessageBox()->setMessagesDirect($user->getMessageBox()->getMessages());
					return false;
				}
				
				//Alte Bilder löschen
				
				
				//Aus DB löschen
				$imageModel = new ImagesModel();
				$rows = $imageModel->find(array($imageIdS_old, $imageIdM_old));
				foreach ($rows as $row)
					$row->delete();
				
				//Dateien löschen
				$dir = Zend_Registry::get("config")->general->upload->images->destination;
				if($imageIdS_old)
				{
					$path = $dir.$imageIdS_old.".png";
					unlink($path);
				}
				if($imageIdM_old)
				{
					$path = $dir.$imageIdM_old.".png";
					unlink($path);	
				}
				return $this;
			}			
		}
		return false;
	}
	

}