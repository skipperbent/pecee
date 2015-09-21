<?php
namespace Pecee\UI;
class Tablist {
	public function __construct() {
		\Pecee\UI\Site::getInstance()->addWrappedJs('pecee-tablist.js');
	}
	
	public function button($id, $value=null) {
		$btn=new \Pecee\UI\Html\Html('a');
		$btn->addAttribute('href','#');
		$btn->addAttribute('class','pecee-tablist');
		$btn->addAttribute('data-id', $id);
		$btn->setInnerHtml($value);
		return $btn;
	}
	
	public function start($id, $visible=false) {
		$tab=new \Pecee\UI\Html\Html('div');
		$tab->setClosingType(\Pecee\UI\Html\Html::CLOSE_TYPE_NONE);
		$tab->addAttribute('class', 'pecee-tablist');
		$tab->addAttribute('data-id', $id);
		$tab->addAttribute('data-visible', (($visible) ? 'true' : 'false'));
		return $tab;
	}
	
	public function end() {
		return '</div>';
	}
}