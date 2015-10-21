<?php
namespace Pecee\UI\ResponseData;
use Pecee\Str;

class ResponseDataPost extends ResponseData {
	public function __construct() {
		parent::__construct();
		if(self::isPostBack()) {
			foreach($_POST as $i=>$post) {
				if(is_array($post)) {
					foreach($post as $k=>$p) {
						$post[$k] = Str::htmlEntitiesDecode($p);
					}
					$this->data[strtolower($i)] = $post;
				} else {
					$this->data[strtolower($i)] = Str::htmlEntitiesDecode($post);
				}
			}
		}
	}

	/**
	 * Adds validation input
	 * @param string $name
	 * @param string $index
	 * @param \Pecee\UI\Form\Validate\ValidateInput|array $type
	 * @throws \ErrorException
	 */
	public function addInputValidation($name, $index, $type) {
		if($type == 'Pecee\\UI\\Form\\Validate\\IValidateInput') {
			throw new \ErrorException('Unknown validate type. Must be of type \Pecee\UI\Form\Validate\IValidateInput');
		}
		if(self::IsPostBack()) {
			if(is_array($type)) {
				$types = array();
				foreach($type as $t) {
					$t->setIndex($index);
					$t->setName($name);
					$t->setValue($this->__get($index));
					$t->setForm($this->getFormName());
					$types[] = $t;
				}
				$this->validateInput($types);
			} else {
				$type->setIndex($index);
				$type->setName($name);
				$type->setValue($this->__get($index));
				$type->setForm($this->getFormName());
				$this->validateInput($type);
			}
		}
	}

	public static function GetFormName() {
		if(self::isPostBack()) {
			foreach($_POST as $key=>$p) {
				if(strpos($key, '_') > 0) {
					$form = explode('_', $key);
					return $form[0];
				}
			}
		}
		return null;
	}

	public function getPostCount() {
		return count($this->data);
	}

	public static function isPostBack() {
		return (isset($_POST) && isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) == 'post');
	}
}