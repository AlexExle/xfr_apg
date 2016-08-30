<?php

class Nobita_Teams_XenForo_Image_Gd extends XFCP_Nobita_Teams_XenForo_Image_Gd
{
	public function resize($width, $height)
	{
		$newImage = imagecreatetruecolor($width, $height);
		$this->_preallocateBackground($newImage);

		imagecopyresized($newImage, $this->_image,
			0, 0, 0, 0,
			$width, $height, $this->_width, $this->_height
		);

		$this->_setImage($newImage);
	}

	public function setBackground($colorHex)
	{
		$rgb = group_hex2rgb($colorHex);

		$color = imagecolorallocate($this->_image, $rgb[0], $rgb[1], $rgb[2]);
        imagefill($this->_image, 0, 0, $color);
	}

	public function drawText($text, $textHexColor, $fontSize = null, $fontPath = null)
	{
		if (is_null($fontSize))
		{
			$fontSize = floor(($this->getWidth() - strlen($text)) / 3);
		}

        $bbox = imageftbbox($fontSize, 0, $fontPath, $text);
        $border = ($this->getWidth() > 48) ? 5 : 0;

        $x = $bbox[0] + $this->getWidth() / 2 - $bbox[4] / 2 - $border;
        $y = $bbox[1] + $this->getHeight() / 2 - $bbox[5] / 2;

		$rgb = group_hex2rgb($textHexColor);

        $color = imagecolorallocate($this->_image, $rgb[0], $rgb[1], $rgb[2]);
        imagettftext($this->_image, $fontSize, 0, $x, $y, $color, $fontPath, $text);
	}
}
