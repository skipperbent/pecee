<?php
namespace Pecee\UI\Form;

use Pecee\Boolean;
use Pecee\Dataset\Dataset;
use Pecee\Http\Input\Input;
use Pecee\Str;
use Pecee\UI\Html\HtmlCheckbox;
use Pecee\UI\Html\HtmlForm;
use Pecee\UI\Html\HtmlInput;
use Pecee\UI\Html\HtmlLabel;
use Pecee\UI\Html\HtmlSelect;
use Pecee\UI\Html\HtmlSelectOption;
use Pecee\UI\Html\HtmlTextarea;

class Form {
	const FORM_ENCTYPE_FORM_DATA = 'multipart/form-data';

	protected $prefixElements;
	protected $input;
	protected $name;
	protected $indexes;

	public function __construct(Input $input) {
		$this->prefixElements = true;
		$this->input = $input;
		$this->indexes = array();
	}

	protected function getPostBackValue($name) {

		$method = request()->getMethod();

		// Remove form name
		$name = substr($name, strlen($this->name)+1);

		if($method !== 'get') {
			if(!is_null($this->input->post->get($name))) {
				return $this->input->post->get($name)->getValue();
			}

			if(strpos($name, '[]') != false) {
				$index = $this->indexes[$name];
				$newName = substr($name, 0, strpos($name, '[]'));
				if(isset($_POST[$newName][$index])) {
					return $_POST[$newName][$index];
				}
			}
		}

		if(!is_null($this->input->get->get($name))) {
			return $this->input->get->get($name)->getValue();
		}

		return null;
	}

	/**
	 * Tries to make some kind of unique form input name
	 * @param string $name
	 * @return string
	 */
	protected function getInputName($name) {
		if($this->prefixElements) {
			$name = ($this->name) ? sprintf('%s_%s', $this->name, $name) : $name;
		}
		$this->indexes[$name] = (isset($this->indexes[$name])) ? ($this->indexes[$name]+1) : 0;
		return $name;
	}

	/**
	 * Starts new form
	 * @param string $name
	 * @param string $method
	 * @param string $action
	 * @param string $enctype
	 * @return \Pecee\UI\Html\HtmlForm
	 */
	public function start($name = null, $method = 'post', $action = null, $enctype = 'multipart/form-data') {
		$this->name = $name;
		return new HtmlForm($name, $method, $action, $enctype);
	}

	/**
	 * Creates new HTML input element
	 * @param string $name
	 * @param string $type
	 * @param string $value
	 * @param bool $forcePostback
	 * @return \Pecee\UI\Html\HtmlInput
	 */
	public function input($name, $type, $value = null, $forcePostback = false) {
		$name = $this->getInputName($name);
		if(is_null($value) && $this->getPostBackValue($name) || $forcePostback && request()->getMethod() !== 'get') {
			$value = $this->getPostBackValue($name);
		}
		$element = new HtmlInput($name, $type, Str::htmlEntities($value));
		// Added: if the input is an radio, then save the god damn post value :-D...
		if(strtolower($type) === 'radio' && $this->getPostBackValue($name) && $value === $this->getPostBackValue($name) ) {
			$element->addAttribute('checked', 'checked');
		}
		return $element;
	}

	/**
	 * Make new captcha element
	 * @param string $name
	 * @return \Pecee\UI\Form\FormCaptcha
	 */
	public function captcha( $name ) {
		return new FormCaptcha($name);
	}

	/**
	 * Creates new checkbox input element
	 * @param string $name
	 * @param string $value
	 * @param bool $forcePostback
	 * @param string|null $defaultValue
	 * @return \Pecee\UI\Html\HtmlCheckbox
	 */
	public function bool($name, $value = null, $forcePostback=true, $defaultValue = null) {
		$name = $this->getInputName($name);
		$v = (!is_null($defaultValue)) ? $defaultValue : true;
		$element = new HtmlCheckbox($name, $v);
		if($forcePostback && request()->getMethod() !== 'get') {
			if($v && $this->getPostBackValue($name) === $v) {
				$element->addAttribute('checked', 'checked');
			}
		} else {
			if(Boolean::parse($value)) {
				$element->addAttribute('checked', 'checked');
			}
		}
		return $element;
	}

	/**
	 * Creates new label
	 * @param string $value
	 * @param string $for
	 * @return \Pecee\UI\Html\HtmlLabel
	 */
	public function label($value, $for=null) {
		return new HtmlLabel($value, $for);
	}

	/**
	 * Creates new HTML Select element
	 * @param string $name
	 * @param Dataset $data
	 * @param string|null $value
	 * @param bool $forcePostback
	 * @return \Pecee\UI\Html\HtmlSelect
	 */
	public function selectStart($name, $data = null, $value=null, $forcePostback=false) {
		$name = $this->getInputName($name);
		$element = new HtmlSelect($name);
		if(!is_null($data)) {
			if($data instanceof Dataset) {
				$arr=$data->getData();
				if(count($arr) > 0) {
					foreach($data->getData() as $i) {
						$val=(!isset($i['value'])) ? $i['name'] : $i['value'];
						$selected=($forcePostback && $this->getPostBackValue($name) === $val  || $this->getPostBackValue($name) === $val || !$this->getPostBackValue($name) && $value === $val || (isset($i['selected']) && $i['selected']) || !$forcePostback && $value === $val);
						$element->addOption(new HtmlSelectOption($i['name'], $val, $selected));
					}
				}
			} elseif(is_array($data)) {
				foreach($data as $val=>$name) {
					$selected=($forcePostback && $this->getPostBackValue($name) == $val  || $this->getPostBackValue($name) === $val || !$this->getPostBackValue($name) && $value === $val || (isset($i['selected']) && $i['selected']) || !$forcePostback && $value === $val);
					$element->addOption(new HtmlSelectOption($name, $val, $selected));
				}
			} else {
				throw new \InvalidArgumentException('Data must be either instance of Dataset or array.');
			}
		}
		return $element;
	}

	/**
	 * Creates new textarea
	 * @param string $name
	 * @param int $rows
	 * @param int $cols
	 * @param string $value
	 * @param bool $forcePostback
	 * @return \Pecee\UI\Html\HtmlTextarea
	 */
	public function textarea($name, $rows, $cols, $value = null, $forcePostback = false) {
		$name = $this->getInputName($name);
		if(!$value && $this->getPostBackValue($name) || $forcePostback && request()->getMethod() !== 'get') {
			$value = $this->getPostBackValue($name);
		}
		return new HtmlTextarea($name, $rows, $cols, $value);
	}

	/**
	 * Creates submit element
	 * @param string $name
	 * @param string $value
	 * @return \Pecee\UI\Html\HtmlInput
	 */
	public function submit($name, $value) {
		return $this->input($name, 'submit', $value);
	}

	/**
	 * Ends open form
	 * @return string
	 */
	public function end() {
		return '</form>';
	}

	public function setPrefixElements($bool) {
		$this->prefixElements = $bool;
	}
}