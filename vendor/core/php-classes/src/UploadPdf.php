<?php 

namespace Core;

class UploadPdf{
	//This method receveid destine path  and file to will upload
	public static function upload($path,$indice)
	{	
		$ext= $_FILES[$indice]['type'];
		$source=$_FILES[$indice]['tmp_name'];
		$source_name= $_FILES[$indice]['name']; 
		if(($source_name <> "none") && ($source_name <> "") && ($ext=='application/pdf'))
		{
			
				$pdf_name = 'comprov'.rand().date('YmdHis').'.pdf';
				if(move_uploaded_file($source, $path.$pdf_name)){
					$_POST[$indice]= $path.$pdf_name;
				}
			
		}else{
				
	
		}
	}
	
}