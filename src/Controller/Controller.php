<?php
namespace Pecee\Controller;

use Pecee\Base;
use Pecee\Debug;

abstract class Controller extends Base {

	public function __construct() {
		Debug::getInstance()->add('START CONTROLLER ' . get_class($this));
		parent::__construct();
	}

	public function hasParam($param) {
		return (isset($_GET[$param]));
	}

	public function getPost($index,$default=null) {
		if(isset($_POST[$index])) {
			return $_POST[$index];
		}
		foreach($_POST as $key=>$post) {
			if(strpos($key, '_') > -1) {
				$key = explode('_',$key);
				if($key[1] == $index) {
					return $post;
				}
			}
		}
		return $default;
	}

	public function asJSON(array $array, $cacheDuration = 2592000) {
        if(!is_null($cacheDuration)) {
            header('Cache-Control: public,max-age='.$cacheDuration.',must-revalidate');
            header('Expires: '.gmdate('D, d M Y H:i:s',(time()+$cacheDuration)).' GMT');
            header('Last-modified: '.gmdate('D, d M Y H:i:s',time()).' GMT');
        }
        header('Content-type: application/json');
		echo json_encode($array);
		die();
	}

    /**
     * @param string $name
     * @param array|null $args
     *
     * Simular to PHP __call() - called whenever a method is invoked from the router
     */
    public function callAction($name, $args = null) {
    }

	public function destruct() {
		$this->__destruct();
	}
}