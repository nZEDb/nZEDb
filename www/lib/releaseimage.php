<?php
require_once(WWW_DIR."/lib/framework/db.php");
require_once(WWW_DIR."/lib/util.php");

class ReleaseImage
{	
	function ReleaseImage()
	{	
		$this->imgSavePath = WWW_DIR.'covers/preview/';
		$this->vidSavePath = WWW_DIR.'covers/video/';
		$this->jpgSavePath = WWW_DIR.'covers/sample/';
	}
	
	public function fetchImage($imgLoc)
	{		
		$img = false;
		
		if (preg_match('/^http:/i', $imgLoc))
			$img = getUrl($imgLoc);
		elseif (file_exists($imgLoc))
			$img = @file_get_contents($imgLoc);	
		
		if ($img !== false)
		{
			$im = @imagecreatefromstring($img);
			if ($im !== false)
			{
				imagedestroy($im);
				return $img;
			}
			return false;
		}
		return false;	
	}
	
	public function saveImage($imgName, $imgLoc, $imgSavePath, $imgMaxWidth='', $imgMaxHeight='', $saveThumb=false)
	{
		$cover = $this->fetchImage($imgLoc);
		if ($cover === false) 
			return 0;
		
		if ($imgMaxWidth != '' && $imgMaxHeight != '')
		{
			$im = @imagecreatefromstring($cover); 
			$width = imagesx($im);
			$height = imagesy($im); 
			$ratioh = $imgMaxHeight/$height; 
			$ratiow = $imgMaxWidth/$width; 
			$ratio = min($ratioh, $ratiow); 
			// New dimensions 
			$new_width = intval($ratio*$width); 
			$new_height = intval($ratio*$height); 
			if ($new_width < $width) {
				$new_image = imagecreatetruecolor($new_width, $new_height);
  				imagecopyresampled($new_image, $im, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
  				ob_start();
  				imagejpeg($new_image, null, 85);
  				$thumb = ob_get_clean();
  				imagedestroy($new_image);
  				
  				if ($saveThumb)
  					@file_put_contents($imgSavePath.$imgName.'_thumb.jpg', $thumb);
  				else
  					$cover = $thumb;
  					
  				unset($thumb);
			}
			imagedestroy($im);
		}
		$coverPath = $imgSavePath.$imgName.'.jpg';
		$coverSave = @file_put_contents($coverPath, $cover);
		return ($coverSave !== false || ($coverSave === false && file_exists($coverPath))) ? 1 : 0;
	}
	
	public function delete($guid)
	{
		@unlink($this->imgSavePath.$guid.'_thumb.jpg');
	}

}
?>
