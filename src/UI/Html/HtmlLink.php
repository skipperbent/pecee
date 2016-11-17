<?php
namespace Pecee\UI\Html;

class HtmlLink extends Html {

    const REL_ALTERNATE = 'alternate';
    const REL_AUTHOR = 'author';
    const REL_DNS_PREFETCH = 'dns-prefetch';
    const REL_HELP = 'help';
    const REL_ICON = 'icon';
    const REL_LICENCE = 'license';
    const REL_NEXT = 'next';
    const REL_PINGBACK = 'pingback';
    const REL_PRECONNECT = 'preconnect';
    const REL_PREFETCH = 'prefetch';
    const REL_PRELOAD = 'preload';
    const REL_PRERENDER = 'prerender';
    const REL_PREV = 'prev';
    const REL_SEARCH = 'search';
    const REL_STYLESHEET = 'stylesheet';

	public function __construct($href, $rel = self::REL_STYLESHEET, $type = null) {
		parent::__construct('link');

		$this->closingType = self::CLOSE_TYPE_SELF;
		$this->addAttribute('href', $href);
		$this->addAttribute('rel', $rel);

		if($type !== null) {
			$this->addAttribute('type', $type);
		}
	}

}