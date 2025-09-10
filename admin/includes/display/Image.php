<?php
class Image{		
	public function imageUploadResize($sourcename, $destination,$imgwidth='',$imgheight=''){	
		if($imgwidth != "" && $imgheight != ""){
			$img_new_width = $imgwidth;
			$img_new_height = $imgheight;
		}else{
			$img_new_width = GetConfig('smallimagewidth');
			$img_new_height = GetConfig('smallimageheight');
		}
		$limgwidth = GetConfig('largeimagewidth');
		$limgheight = GetConfig('largeimageheight');		
		$fileinfo = pathinfo($sourcename['name']);
		$dinfo = pathinfo($destination);
		$reqList = array('jpg', 'png', 'gif', 'jpeg');			
		if(in_array((strtolower(end(explode(".",$sourcename['name'])))),$reqList)){			
			$destination = $destination.".".strtolower($fileinfo['extension']);
			if(copy($sourcename['tmp_name'],$destination)){
				if(is_file($destination)){
					list($lwidth1, $lheight1) = getimagesize($destination);		
					$smallimagepath = str_replace('/large/','/small/',$destination);
					$this->imageResize($fileinfo['extension'],$destination,$smallimagepath,$img_new_width,$img_new_height);					
					if($lwidth1 >= $limgwidth && $lheight1 >= $limgheight){						
						$this->imageResize($fileinfo['extension'],$destination,$destination,$limgwidth,$limgheight);
					}							
				}
				return array('msg'=>'success','name'=>end(explode("/",$destination)));
			} 
		} else {
			return array('msg'=>'error','name'=>'');
		}		
	}
	
	public function imageUploadCrop($sourcename, $destination,$imgwidth='',$imgheight=''){	
		if($imgwidth != "" && $imgheight != ""){
			$img_new_width = $imgwidth;
			$img_new_height = $imgheight;
		}else{
			$img_new_width = GetConfig('smallimagewidth');
			$img_new_height = GetConfig('smallimageheight');
		}
		$limgwidth = GetConfig('largeimagewidth');
		$limgheight = GetConfig('largeimageheight');		
		$fileinfo = pathinfo($sourcename['name']);
		$dinfo = pathinfo($destination);
		$reqList = array('jpg', 'png', 'gif', 'jpeg');			
		if(in_array((strtolower(end(explode(".",$sourcename['name'])))),$reqList)){			
			$destination = $destination.".".strtolower($fileinfo['extension']);
			if(copy($sourcename['tmp_name'],$destination)){
				if(is_file($destination)){
					list($lwidth1, $lheight1) = getimagesize($destination);		
					$smallimagepath = str_replace('/large/','/small/',$destination);
					//$this->imageResize($fileinfo['extension'],$destination,$smallimagepath,$img_new_width,$img_new_height);		
					$this->imageCrop($fileinfo['extension'],$smallimagepath,$destination,4, 3,250,210,$lwidth1,$lheight1);			
					if($lwidth1 >= $limgwidth && $lheight1 >= $limgheight){						
						$this->imageResize($fileinfo['extension'],$destination,$destination,$limgwidth,$limgheight);
					}							
				}
				return array('msg'=>'success','name'=>end(explode("/",$destination)));
			} 
		} else {
			return array('msg'=>'error','name'=>'');
		}		
	}
	public function imageUpload($source, $destination, $filetype){		
		if($filetype == 'image'){			
			$fileinfo = pathinfo($source['name']);
			$dinfo = pathinfo($destination);
			$reqList = array('jpg', 'png', 'gif', 'jpeg', 'bmp');			
			if(in_array((strtolower(end(explode(".",$source['name'])))),$reqList)){
				$destination = $destination.".".strtolower($fileinfo['extension']);
				if(copy($source['tmp_name'],$destination)){
					return array('msg'=>'success','name'=>end(explode("/",$destination)));
				} 
			} else {
				return array('msg'=>'error','name'=>'');
			}
		}
		if($filetype == 'pdf'){
			$fileinfo = pathinfo($source['name']);
			$dinfo = pathinfo($destination);
			$reqList = array('jpg', 'png', 'gif', 'jpeg', 'bmp','pdf');			
			if(in_array((strtolower(end(explode(".",$source['name'])))),$reqList)){
				$destination = $destination.".".strtolower($fileinfo['extension']);
				if(copy($source['tmp_name'],$destination)){
					return array('msg'=>'success','name'=>end(explode("/",$destination)));
				} 
			} else {
				return array('msg'=>'error','name'=>'');
			}
		}	
		if($filetype == 'video'){
			$fileinfo = pathinfo($source['name']);
			$dinfo = pathinfo($destination);
			$destination = $destination.".".strtolower($fileinfo['extension']);
			if(copy($source['tmp_name'],$destination)){
				return array('msg'=>'success','name'=>end(explode("/",$destination)));
			} 
		}	
	}

	public function imageResize($img_format,$img_source,$img_destination,$img_new_width,$img_new_height){		
		$img_format = strtolower($img_format);		
		if($img_format == "jpg"){
			$image = imagecreatefromjpeg($img_source);
		}		
		if($img_format == "jpeg"){
			$image = imagecreatefromjpeg($img_source);
		}
		if($img_format == "gif"){
			$image = imagecreatefromgif($img_source);
		}
		if($img_format == "png"){
			$image = imagecreatefrompng($img_source);
		}		
		$width = imagesx($image);
		$height = imagesy($image);		
		$image_resized = imagecreatetruecolor($img_new_width,$img_new_height);
		imagecopyresampled($image_resized, $image, 0, 0, 0, 0, $img_new_width,$img_new_height, $width, $height);		
		if($img_format == "jpg"){
			imagejpeg($image_resized,$img_destination,100);		
		}		
		if($img_format == "jpeg"){
			imagejpeg($image_resized,$img_destination,100);		
		}		
		if($img_format == "gif"){
			imagegif($image_resized,$img_destination);	
		}			
		if($img_format == "png"){
			imagejpeg($image_resized,$img_destination,100);	
		}				
	}

	public function imageCrop($img_format,$img_source,$img_destination,$img_x, $img_y,$img_new_width,$img_new_height,$img_w,$img_h){		
		$img_format = strtolower($img_format);	
		if($img_format == "jpg"){
			$image = imagecreatefromjpeg($img_source);
		}		
		if($img_format == "jpeg"){
			$image = imagecreatefromjpeg($img_source);
		}
		if($img_format == "gif"){
			$image = imagecreatefromgif($img_source);
		}
		if($img_format == "png"){
			$image = imagecreatefrompng($img_source);
		}		
		$width = imagesx($image);
		$height = imagesy($image);		
		$image_resized = imagecreatetruecolor($img_new_width,$img_new_height);
		imagecopyresampled($image_resized, $image, 0, 0, $img_x, $img_y, $img_new_width,$img_new_height,$img_w,$img_h);					
		
		if($img_format == "jpg"){
			if(imagejpeg($image_resized,$img_destination,100)) return true;
		}		
		if($img_format == "jpeg"){
			if(imagejpeg($image_resized,$img_destination,100)) return true;
		}		
		if($img_format == "gif"){
			if(imagegif($image_resized,$img_destination)) return true;
		}			
		if($img_format == "png"){
			if(imagejpeg($image_resized,$img_destination,100)) return true;
		}	
		return false;
	}

}