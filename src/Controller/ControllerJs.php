<?php
namespace Pecee\Controller;

use Pecee\Controller\File\FileAbstract;

class ControllerJs extends FileAbstract {

	public function __construct() {
		parent::__construct(FileAbstract::TYPE_JAVASCRIPT);
	}

}