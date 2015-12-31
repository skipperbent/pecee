<?php
namespace Pecee\Web\YUICompressor;

class YUICompressorItem {
	public $filename;
	public $filepath;
	public $type;
	public $content;
	public $options;
	public $sizeKB;
	public $minified;
	public $minifiedKB = 0;
	public $minifiedRatio = 0;
}