<?php 

namespace Core;

class UploadImg{
	//This method receveid destine path  and file to will upload
	public static function upload($path,$indice)
	{	
		$ext= mime_content_type($_FILES[$indice]['tmp_name']);
		$source=$_FILES[$indice]['tmp_name'];
		$source_name= $_FILES[$indice]['name']; 
		if(($source_name <> "none") && ($source_name <> "") && ($ext=='image/png' || $ext =='image/jpeg'))
		{
			switch($ext){
    			case 'image/jpeg':
					$image_name = rand().date('YmdHis').'.jpg';
				break;
				case 'image/png':
					$image_name = rand().date('YmdHis').'.png';
				break;
		    }
			$image = new UploadImg();
			if($image->createThumbnail($source,$ext,$path.$image_name)){
				$_POST[$indice]= $path.$image_name;
			}
		}else{
				
	
		}
	}
	public function createThumbnail($img,$ext,$dest,$qualityjpg=40,$qualitypng=9)
	{
		//get the image width and height
		list($old_width,$old_height)= getimagesize($img);
		$new_width = $old_width;
		$new_height = $old_height;
		$new_image = imagecreatetruecolor($new_width,$new_height);
		// preserve transparency
  		if($ext == "image/png"){
    		imagecolortransparent($new_image, imagecolorallocatealpha($new_image, 0, 0, 0, 127));
    		imagealphablending($new_image, false);
    		imagesavealpha($new_image, true);
  		}
		switch($ext){
			case 'image/jpeg':
				$old_image = @imagecreatefromjpeg($img);
				
			break;
			case 'image/png':
				$old_image = @imagecreatefrompng($img);
			break;
		}
		@imagecopyresampled($new_image,$old_image,0,0,0,0,$new_width,$new_height,$old_width,$old_height);
		switch($ext){
    		case 'image/jpeg': @imagejpeg($new_image,$dest,$qualityjpg); break;
    		case 'image/png': @imagepng($new_image, $dest,$qualitypng); break;
        }
        return true;
		
		@imagedestroy($old_image);
		@imagedestroy($new_image);
	}
}