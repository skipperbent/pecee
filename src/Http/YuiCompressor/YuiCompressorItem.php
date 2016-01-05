<?php
namespace Pecee\Http\YuiCompressor;

class YuiCompressorItem {

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