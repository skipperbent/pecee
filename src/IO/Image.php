<?php
namespace Pecee\IO;

class Image {

	protected $originalBinary;
	protected $mimeType;
	protected $width;
	protected $height;
	protected $quality;
	protected $transparent;

	public function __construct($originalBinary, $mimeType) {
		$this->originalBinary = $originalBinary;
		$this->mimeType = $mimeType;
		$this->quality = 100;
		$this->transparent = true;
	}

	public static function create($binary, $mime) {
		return new self($binary, $mime);
	}

	public static function getExtensionByMimeType($mimeType) {
		switch($mimeType) {
			case 'image/jpg':
			case 'image/jpeg':
				return 'jpg';
				break;
			case 'image/gif':
				return 'gif';
				break;
			case 'image/png':
				return 'png';
				break;
		}
		throw new \InvalidArgumentException('Unknown image!');
	}

	public static function getImageType($filePath) {
		switch (File::GetExtension($filePath)) {
			case 'jpeg':
			case 'jpg':
				return IMAGETYPE_JPEG;
				break;
			case 'gif':
				return IMAGETYPE_GIF;
				break;
			case 'bmp':
				return IMAGETYPE_BMP;
				break;
			case 'png':
				return IMAGETYPE_PNG;
				break;
			default:
				throw new \ErrorException('Ukendt filtype: ' . File::GetExtension($filePath));
		}
	}

	public static function getMime($filePath) {
		$imagetype = self::getImageType( $filePath );
		if( $imagetype ) {
			return image_type_to_mime_type( $imagetype );
		}
		return null;
	}

	private function getSizeFromBinary( $binary ) {
		$im = imagecreatefromstring($binary);
		$width = imagesx($im);
		$height = imagesy($im);
		$arr = array($width, $height);
		return $arr;
	}

	protected function getImage($image) {
		ob_start();
		// Spool the image in the original format
		switch($this->mimeType) {
			case image_type_to_mime_type(IMAGETYPE_JPEG):
			case image_type_to_mime_type(IMAGETYPE_JPEG2000):
				imagejpeg($image, null, $this->quality);
				break;
			case image_type_to_mime_type(IMAGETYPE_GIF):
				if($this->transparent) {
					imagecolortransparent($image,imagecolorat($image,0,0));
				}
				imagegif($image);
				break;
			case image_type_to_mime_type(IMAGETYPE_BMP):
				imagewbmp($image);
				break;
			case image_type_to_mime_type(IMAGETYPE_PNG):
				imagepng($image, null, min($this->quality,9));
				break;
			default:
				throw new \ErrorException('Ukendt mimetype: ' . $this->mimeType);
		}
		$fileContents = ob_get_contents();
		ob_end_clean();
		return $fileContents;
	}

	public function getWithWatermark($watermarkPath) {
		// Load the requested image
		$image = @imagecreatefromstring($this->getOriginalBinary());

		$w = imagesx($image);
		$h = imagesy($image);

		// Load the watermark image
		$watermark = imagecreatefrompng($watermarkPath);
		$ww = imagesx($watermark);
		$wh = imagesy($watermark);

		// Merge watermark upon the original image
		imagecopy($image, $watermark, $w-$ww, $h-$wh, 0, 0, $ww, $wh);
		return $this->getImage($image);
	}

	public function getResizedExact($dstWidth,$dstHeight) {
		$src = @imagecreatefromstring($this->originalBinary);
		if (!$src)
			throw new \ErrorException('Kunne ikke læse fil.');
		$srcWidth  	= imagesx($src);
		$srcHeight 	= imagesy($src);
		$widthRatio = $srcWidth  / $dstWidth;
		$heightRatio = $srcHeight / $dstHeight;

		if ($widthRatio < $heightRatio) {
			$tmp = ($dstHeight * $widthRatio);
			//Center
			$srcY = ($srcHeight - $tmp) / 2;
			$srcHeight = $tmp;
			$srcX = 0;
		} else {
			$tmp = ($dstWidth * $heightRatio);
			// Center
			$srcX = ($srcWidth - $tmp) / 2;
			$srcWidth = $tmp;
			$srcY = 0;
		}

		$dst = imagecreatetruecolor($dstWidth,$dstHeight);
		if($this->mimeType == image_type_to_mime_type(IMAGETYPE_PNG)){
			imagealphablending($dst, false);
			imagesavealpha($dst,true);
			$transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
			imagefilledrectangle($dst, 0, 0, $dstWidth, $dstHeight, $transparent);
		}

		imagecopyresampled($dst,$src,0,0,$srcX,$srcY,$dstWidth,$dstHeight,$srcWidth,$srcHeight);
		return $this->getImage($dst);
	}

	public function getResizedMaxSize($dstWidth,$dstHeight) {
		$src = @imagecreatefromstring($this->originalBinary);
		if (!$src)
			throw new \InvalidArgumentException('Image not found.');
		$srcWidth  	= imagesx($src);
		$srcHeight 	= imagesy($src);
		$widthRatio = $srcWidth  / $dstWidth;
		$heightRatio = $srcHeight / $dstHeight;
		if ($widthRatio < $heightRatio) {
			//src height is higher compared to destation - make height fit and adjust width accordingly
			$height = $dstHeight;
			$width 	= $srcWidth / $heightRatio;
		} else {
			$width 	= $dstWidth;
			$height	= $srcHeight / $widthRatio;
		}

		$dst = imagecreatetruecolor($width,$height);
		if($this->mimeType == image_type_to_mime_type(IMAGETYPE_PNG)){
			imagealphablending($dst, false);
			imagesavealpha($dst,true);
			$transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
			imagefilledrectangle($dst, 0, 0, $dstWidth, $dstHeight, $transparent);
		}

		imagecopyresampled($dst,$src,0,0,0,0,$width,$height,$srcWidth,$srcHeight);
		return $this->getImage($dst);
	}

	public function getMimeType() {
		return $this->mimeType;
	}

	public function getThumbnailBinary() {
		return $this->getResized(200,200);
	}

	public function setOriginalBinary($binary) {
		$this->originalBinary = $binary;
	}

	private function setSize() {
		$size = $this->getSizeFromBinary($this->getOriginalBinary());
		if( $size ) {
			$this->width = $size[0];
			$this->height = $size[1];
		}
	}

	public function getWidth() {
		if(!$this->width) {
			$this->setSize();
		}
		return $this->width;
	}

	public function getHeight() {
		if(!$this->height) {
			$this->setSize();
		}
		return $this->height;
	}

	public function getRatio() {
		return ($this->getWidth()/$this->getHeight())/($this->getHeight()/$this->getWidth());
	}

	/**
	 * @param int $quality
	 */
	public function setQuality($quality) {
		$this->quality = $quality;
	}

	/**
	 * @return string
	 */
	public function getOriginalBinary() {
		return $this->originalBinary;
	}

	/**
	 * @return bool $transparent
	 */
	public function getTransparent() {
		return $this->transparent;
	}

	/**
	 * @param bool $transparent
	 */
	public function setTransparent($transparent) {
		$this->transparent = $transparent;
	}
}