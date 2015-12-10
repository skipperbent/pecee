<?php
namespace Pecee\UI\Form;
use Pecee\Session\Session;
use Pecee\UI\Html\HtmlImage;
use Pecee\Util;

class FormCaptcha {

	// Settings
	private $name;
	private $identifier;
	private $numbersonly;
	private $uniqueValueLength;
	private $backgroundImage;
	private $fontColor;
	private $fontShadowColor;
	private $fontRotationDegrees = 0;
	private $fontShadowRotationDegrees = 0;
	private $fontMarginLeft = 0;
	private $fontMarginTop = 25;
	private $fontShadowMarginTop = 26;
	private $fontShadowMarginLeft = 0;
	private $fontPath;
	private $fontSize;
	private $fontShadowSize;
	private $imageNoise;

	public $image;

	public function __construct($name) {
		$this->name = $name;
		$this->numbersonly = false;
		$this->uniqueValueLength = 8;
		$this->setFontColor('#000000');
		$this->setFontShadowColor('#FFFFFF');
		$this->fontSize = 21;
		$this->fontShadowSize = 21;
		$this->imageNoise = true;
		$relPath=str_replace('.php', DIRECTORY_SEPARATOR, __FILE__);
		$this->fontPath = $relPath . DIRECTORY_SEPARATOR . 'pakenham.ttf';
		$this->identifier = $this->createUniqueIdentifier();
		$this->image = new HtmlImage(url('ControllerCaptcha@show', array($this->name)));
	}

	protected function createUniqueIdentifier() {
		$output = '';
		if($this->numbersonly) {
			for( $i=0;$i<$this->uniqueValueLength;$i++ ) {
				$output .= rand(0, 9);
			}
		} else {
			$str = 'ABCDEFGHIJKLMNOPQRSTUVXYZW0123456789';
			for( $i=0;$i<$this->uniqueValueLength;$i++ ) {
				$num = rand() % 33;
				$output .= substr( $str, $num, 1 );
			}
		}
		return $output;
	}

	public function showCaptcha() {
		Session::getInstance()->set($this->name, $this->identifier);

		if($this->backgroundImage) {
			$image = imagecreatefromjpeg($this->backgroundImage);
		} else {
			$image = imagecreate(100, 30);
		}
		/* Add font shadow */
		if( $this->fontShadowColor ) {
			$textShadow = imagecolorallocate( $image,
												$this->fontShadowColor[0],
												$this->fontShadowColor[1],
												$this->fontShadowColor[2] );

			// Add some shadow to the text
			imagettftext($image, $this->fontShadowSize, $this->fontShadowRotationDegrees, $this->fontShadowMarginLeft, $this->fontShadowMarginTop, $textShadow, $this->fontPath, $this->identifier);
		}

		$textColor = imagecolorallocate( $image,
											$this->fontColor[0],
											$this->fontColor[1],
											$this->fontColor[2] );

		// Add the text
		imagettftext($image, $this->fontSize, $this->fontRotationDegrees, $this->fontMarginLeft, $this->fontMarginTop, $textColor, $this->fontPath, $this->identifier);

		/* Add image-noise */
		if( $this->imageNoise ) {

			for( $i=0;$i<9;$i++ ) {
				$colorNr = rand(180,250);
				$color = imagecolorallocate($image, $colorNr, $colorNr, $colorNr);
				imageline( $image, rand(0,18), rand(6,50), rand(120,500), -50, $color );
			}
		}

		// Date in the past
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

		// always modified
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

		// HTTP/1.1
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);

		// HTTP/1.0
		header("Pragma: no-cache");

		// send the content type header so the image is displayed properly
		header('Content-type: image/jpeg');

		// send the image to the browser
		imagejpeg($image);

		// destroy the image to free up the memory
		imagedestroy($image);
	}

	public function __toString() {
		Session::getInstance()->set($this->name . '_data', $this);
		return $this->image->__toString();
	}

	/**
	 * Add attribute to image element
	 *
	 * @param string $attribute
	 * @param string $value
	 * @return static
	 */
	public function addAttribute( $attribute, $value ) {
		$this->image->addAttribute( $attribute, $value );
		return $this;
	}

	/**
	 * Unique value should only contain numbers?
	 *
	 * @param bool $bool
	 * @return \Pecee\UI\Form\FormCaptcha
	 */
	public function setNumbersOnly($bool) {
		if( !is_bool($bool) )
			throw new \InvalidArgumentException('Argument must be true or false.');
		$this->numbersonly = $bool;
		return $this;
	}

	/**
	 * Set unique value length
	 *
	 * @param int $length
	 * @return \Pecee\UI\Form\FormCaptcha
	 */
	public function setUniqueValueLength( $length ) {
		$this->uniqueValueLength = $length;
		$this->identifier = $this->createUniqueIdentifier();
		return $this;
	}

	/**
	 * Set background-image
	 *
	 * @param string $imagePath
	 * @throws \ErrorException
	 * @return \Pecee\UI\Form\FormCaptcha
	 */
	public function setBackgroundImage( $imagePath ) {
		if( !@file_exists($imagePath) )
			throw new \ErrorException('File does not exist..');
		$this->backgroundImage = $imagePath;
		return $this;
	}

	/**
	 * Set font size
	 *
	 * @param int $size
	 * @return \Pecee\UI\Form\FormCaptcha
	 */
	public function setFontSize( $size ) {
		$this->fontSize = $size;
		return $this;
	}

	/**
	 * Set font shadow size
	 *
	 * @param int $size
	 * @return \Pecee\UI\Form\FormCaptcha
	 */
	public function setFontShadowSize( $size ) {
		$this->fontShadowSize = $size;
		return $this;
	}

	public function setFontRotation( $degrees ) {
		$this->fontRotationDegrees = $degrees;
		return $this;
	}

	public function setFontMarginTop( $pixels ) {
		$this->fontMarginTop = $pixels;
		return $this;
	}

	public function setFontShadowMarginTop( $pixels ) {
		$this->fontShadowMarginTop = $pixels;
		return $this;
	}

	/**
	 * Set font shadow rotation
	 *
	 * @param int $degrees
	 * @return \Pecee\UI\Form\FormCaptcha
	 */
	public function setFontShadowRotation( $degrees ) {
		$this->fontShadowRotationDegrees = $degrees;
		return $this;
	}

	/**
	 * Set normal text margin left
	 *
	 * @param int $pixels
	 * @return \Pecee\UI\Form\FormCaptcha
	 */
	public function setFontMarginLeft( $pixels ) {
		$this->fontMarginLeft = $pixels;
		return $this;
	}

	/**
	 * Set shadow text margin left
	 *
	 * @param int $pixels
	 * @return \Pecee\UI\Form\FormCaptcha
	 */
	public function setFontShadowMarginLeft( $pixels ) {
		$this->fontShadowMarginLeft = $pixels;
		return $this;
	}

	/**
	 * Set font color
	 *
	 * @param string $htmlColor
	 * @return \Pecee\UI\Form\FormCaptcha
	 */
	public function setFontColor( $htmlColor ) {
		$this->fontColor = Util::html2rgb($htmlColor);
		return $this;
	}

	/**
	 * Set font shadow color
	 *
	 * @param string $htmlColor
	 * @return \Pecee\UI\Form\FormCaptcha
	 */
	public function setFontShadowColor( $htmlColor ) {
		$this->fontShadowColor = Util::html2rgb($htmlColor);
		return $this;
	}

}