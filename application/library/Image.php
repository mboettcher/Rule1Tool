<?php
class Image extends Abstraction
{
	protected $image_id;
	protected $width;
	protected $height;
	protected $mime;
	protected $date_add;
	protected $date_edit;
		
	protected $_isCatched = false;
	
	/**
	 * Gibt das Image Objekt zurück
	 *
	 * @param INT $image_id
	 * @param ARRAY $new_data
	 * @return Object
	 */
	public function __construct($image_id = null)
	{		
		if($image_id != null)
		{
			//Hole Bild
			return $this->_getImage($image_id);
		}
	}
	public function isCatched()
	{
		return $this->_isCatched;
	}
	protected function catchIt()
	{
		if(!$this->_isCatched)
			$this->getImage($this->getImageId());
	}
	
	protected function _getImage($image_id)
	{
		$table = new ImagesModel();
		$row = $table->find($image_id)->current();
		if($row)
		{
			$this->image_id = $row->id;
			$this->width = $row->width;
			$this->height = $row->height;
			$this->mime = $row->mime;
			$this->date_add = $row->date_add;
			$this->date_edit = $row->date_edit;
			
			$this->_isCatched = true;
			
			return $this;
		}
		else
		{
			$this->_isCatched = false;
			return false;
		}
		
	}
	/**
	 * Fügt ein neues, resized Bild hinzu
	 *
	 * @param STRING $sourceFile
	 * @param ARRAY $options width, height
	 * @return OBJECT
	 * 
	 */
	public function setImage($sourceFile, $options = array("width" => 100, "height" => 100))
	{
		//Transaktion starten
		Zend_Registry::get('Zend_Db')->beginTransaction();
	
		$destinationDir = Zend_Registry::get("config")->general->upload->images->destination;
		
		try
		{
   			//Image-ID holen
   			$table = new ImagesModel();
			$input_data = array();
   			$image_id = $table->insert($input_data);	
		
   			if($image_id > 0)
   			{
   				$this->image_id = $image_id;
   				//Resizing and Copy to Destination
	   			$resize = $this->_resize($sourceFile, $options["width"], $options["height"], $destinationDir, $image_id);
		   		
	   			if(!$resize)
	   				throw new Zend_Exception("Konnte Bild nicht ändern und speichern");
		        
	   			$table->update(
	   					array("width" => $this->width,
	   						"height" => $this->height
	   					//,"mime" => $mime //Überbord wegen auslese schwierigkeiten
	   					),
	   					$table->getAdapter()->quoteInto('id = ?', $this->image_id)
	   			);
   				
	   			
   				$this->dataCatched = false;
   				
   				// Wenn alle erfolgreich waren, übertrage die Transaktion und alle Änderungen werden auf einmal übermittelt
		    	Zend_Registry::get('Zend_Db')->commit();	
		    	
		    	return $this;
   			}
   			else
   			{
   				//Irgendwas schief?
   				throw new Zend_Exception("Insert des Images fehlgeschlagen");
   			}
		} catch (Zend_Exception $e) {
		    // Rollback!
		    Zend_Registry::get('Zend_Db')->rollBack();
		    throw new Zend_Exception($e->getMessage(). "\n" .  $e->getTraceAsString());
		}	

	}

	/**
	 * Make thumbs from JPEG, PNG, GIF source file
	 *
	 * @param STRING $tmpname
	 * @param INT $maxWidth
	 * @param INT $maxHeight
	 * @param STRING $save_dir
	 * @param STRING $save_name
	 * @return STRING|BOOLEAN savePath or false
	 * 
	 */
	protected function _resize( $tmpname, $width, $height, $save_dir, $save_name )
    {
	    $save_dir .= ( substr($save_dir,-1) != "/") ? "/" : "";
	    
        $imorig = $this->imagecreatefromfile($tmpname, true);

        if(!$imorig)
        	return false;
        	
        imagealphablending($imorig, false);
		imagesavealpha($imorig, true);	
		
		$ow = imagesx( $imorig );
		$oh = imagesy( $imorig );
		
		//Proportionen erhalten
		//if( $ow > $mw || $oh > $mh ) {
		    if( $ow > $oh ) {
		    	$tnh = $height;
		        $tnw = $tnh * $ow / $oh;
		        
		    } else {
		    	$tnw = $width;
		        $tnh = $tnw * $oh / $ow;
		        
		    }
	/*	} else {
		    // although within size restriction, we still do the copy/resize process
		    // which can make an animated GIF still
		    $tnw = $ow;
		    $tnh = $oh;
		}*/
		//Calculate Resize/ImagePosition
		if($tnw > $width)
			$margin_x = round((($tnw - $width)),0);  
		else 	
			$margin_x = 0;
		if($tnh > $height)
			$margin_y = round((($tnh - $height)),0);   
		else
			$margin_y = 0;
			
		    
		// the document recommends you to use truecolor to get better result
		$imtn = imagecreatetruecolor( $width, $height );
		

		/* making the new image transparent */
		$background = imagecolorallocate($imtn, 0, 255, 0);
		ImageColorTransparent($imtn, $background); // make the new temp image all transparent
		// Turn off alpha blending and set alpha flag
		imagealphablending($imtn, false);
		imagesavealpha($imtn, true);	
		
		
		// copy/resize as usual
	    if (imagecopyresized( $imtn, $imorig, 0, 0, $margin_x, $margin_y, $tnw, $tnh, $ow, $oh ))
	    {
	    	$return = imagepng($imtn, $save_dir.$save_name.".png");
	    	
	    	imagedestroy( $imorig );
			imagedestroy( $imtn );
			
			$this->width = $tnw;
			$this->height = $tnh;
			
	    	if ($return)
	            return $save_dir.$save_name."png";
	        else
	            return false;
	    }
	    else 	
	    	return false;
    }

    /**
     * Gibt die ImageID zurück
     *
     * @return INT
     */
	public function getImageId()
	{
		if(!$this->image_id)
			throw new Zend_Exception("ImageId noch nicht gesetzt");
		return $this->image_id;
	}
	/**
	 * Gibt die Bildweite zurück
	 *
	 * @return INT
	 */
	public function getWidth()
	{
		$this->catchIt();
		return $this->width;
	}
	/**
	 * Gibt die Bildhöhe zurück
	 *
	 * @return INT
	 */
	public function getHeight()
	{
		$this->catchIt();
		return $this->height;
	}
	/**
	 * Gibt den Mime-Type des Bildes zurück
	 *
	 * @return STRING
	 */
	public function getMime()
	{
		$this->catchIt();
		return $this->mime;
	}
	function imagecreatefromfile($path, $user_functions = false)
	{
	    $imagetype = exif_imagetype($path);
	    
	    if(!$imagetype)
	    {
	        return false;
	    }
	    
	    $functions = array(
	        IMAGETYPE_GIF => 'imagecreatefromgif',
	        IMAGETYPE_JPEG => 'imagecreatefromjpeg',
	        IMAGETYPE_PNG => 'imagecreatefrompng',
	        IMAGETYPE_WBMP => 'imagecreatefromwbmp',
	        IMAGETYPE_XBM => 'imagecreatefromwxbm'
	        );
	    
	    if($user_functions)
	    {
	        $functions[IMAGETYPE_BMP] = 'imagecreatefrombmp';
	    }
	    
	    if(!isset($functions[$imagetype]))
	    {
	        return false;
	    }
	    
	    if(!function_exists($functions[$imagetype]))
	    {
	        return false;
	    }
	    
	    return $functions[$imagetype]($path);
	}
}
function ImageCreateFromBMP($filename)
	{
		/*********************************************/
	/* Fonction: ImageCreateFromBMP              */
	/* Author:   DHKold                          */
	/* Contact:  admin@dhkold.com                */
	/* Date:     The 15th of June 2005           */
	/* Version:  2.0B                            */
	/*********************************************/
	
	 //Ouverture du fichier en mode binaire
	   if (! $f1 = fopen($filename,"rb")) return FALSE;
	
	 //1 : Chargement des ent�tes FICHIER
	   $FILE = unpack("vfile_type/Vfile_size/Vreserved/Vbitmap_offset", fread($f1,14));
	   if ($FILE['file_type'] != 19778) return FALSE;
	
	 //2 : Chargement des ent�tes BMP
	   $BMP = unpack('Vheader_size/Vwidth/Vheight/vplanes/vbits_per_pixel'.
	                 '/Vcompression/Vsize_bitmap/Vhoriz_resolution'.
	                 '/Vvert_resolution/Vcolors_used/Vcolors_important', fread($f1,40));
	   $BMP['colors'] = pow(2,$BMP['bits_per_pixel']);
	   if ($BMP['size_bitmap'] == 0) $BMP['size_bitmap'] = $FILE['file_size'] - $FILE['bitmap_offset'];
	   $BMP['bytes_per_pixel'] = $BMP['bits_per_pixel']/8;
	   $BMP['bytes_per_pixel2'] = ceil($BMP['bytes_per_pixel']);
	   $BMP['decal'] = ($BMP['width']*$BMP['bytes_per_pixel']/4);
	   $BMP['decal'] -= floor($BMP['width']*$BMP['bytes_per_pixel']/4);
	   $BMP['decal'] = 4-(4*$BMP['decal']);
	   if ($BMP['decal'] == 4) $BMP['decal'] = 0;
	
	 //3 : Chargement des couleurs de la palette
	   $PALETTE = array();
	   if ($BMP['colors'] < 16777216)
	   {
	    $PALETTE = unpack('V'.$BMP['colors'], fread($f1,$BMP['colors']*4));
	   }
	
	 //4 : Cr�ation de l'image
	   $IMG = fread($f1,$BMP['size_bitmap']);
	   $VIDE = chr(0);
	
	   $res = imagecreatetruecolor($BMP['width'],$BMP['height']);
	   $P = 0;
	   $Y = $BMP['height']-1;
	   while ($Y >= 0)
	   {
	    $X=0;
	    while ($X < $BMP['width'])
	    {
	     if ($BMP['bits_per_pixel'] == 24)
	        $COLOR = unpack("V",substr($IMG,$P,3).$VIDE);
	     elseif ($BMP['bits_per_pixel'] == 16)
	     {  
	        $COLOR = unpack("n",substr($IMG,$P,2));
	        $COLOR[1] = $PALETTE[$COLOR[1]+1];
	     }
	     elseif ($BMP['bits_per_pixel'] == 8)
	     {  
	        $COLOR = unpack("n",$VIDE.substr($IMG,$P,1));
	        $COLOR[1] = $PALETTE[$COLOR[1]+1];
	     }
	     elseif ($BMP['bits_per_pixel'] == 4)
	     {
	        $COLOR = unpack("n",$VIDE.substr($IMG,floor($P),1));
	        if (($P*2)%2 == 0) $COLOR[1] = ($COLOR[1] >> 4) ; else $COLOR[1] = ($COLOR[1] & 0x0F);
	        $COLOR[1] = $PALETTE[$COLOR[1]+1];
	     }
	     elseif ($BMP['bits_per_pixel'] == 1)
	     {
	        $COLOR = unpack("n",$VIDE.substr($IMG,floor($P),1));
	        if     (($P*8)%8 == 0) $COLOR[1] =  $COLOR[1]        >>7;
	        elseif (($P*8)%8 == 1) $COLOR[1] = ($COLOR[1] & 0x40)>>6;
	        elseif (($P*8)%8 == 2) $COLOR[1] = ($COLOR[1] & 0x20)>>5;
	        elseif (($P*8)%8 == 3) $COLOR[1] = ($COLOR[1] & 0x10)>>4;
	        elseif (($P*8)%8 == 4) $COLOR[1] = ($COLOR[1] & 0x8)>>3;
	        elseif (($P*8)%8 == 5) $COLOR[1] = ($COLOR[1] & 0x4)>>2;
	        elseif (($P*8)%8 == 6) $COLOR[1] = ($COLOR[1] & 0x2)>>1;
	        elseif (($P*8)%8 == 7) $COLOR[1] = ($COLOR[1] & 0x1);
	        $COLOR[1] = $PALETTE[$COLOR[1]+1];
	     }
	     else
	        return FALSE;
	     imagesetpixel($res,$X,$Y,$COLOR[1]);
	     $X++;
	     $P += $BMP['bytes_per_pixel'];
	    }
	    $Y--;
	    $P+=$BMP['decal'];
	   }
	
	 //Fermeture du fichier
	   fclose($f1);
	
	 return $res;
	}