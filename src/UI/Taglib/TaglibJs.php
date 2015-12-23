<?php
namespace Pecee\UI\Taglib;
use Pecee\Str;
use Pecee\UI\Site;

class TaglibJs extends Taglib {
	protected $containers = array();
	private static $JS_WRAPPER_TAG = '';
	private static $JS_EXPRESSION = '/js{(.*?)}/';
	private static $JS_WIDGET_EXPRESSION = '/js{_widget(.*?)}/';
	protected $output=array();

	public function __construct() {
		parent::__construct();
	}

	protected function makeJsString($string) {
		return preg_replace('/[\n\r\t]+|\s\s+/', '', trim($string));
	}

	protected function replaceJsExpressions($string) {
		$fixedExpressions=array();
		$expressionMatches=array();
		/* Change all widget expressions */
		$string = preg_replace(self::$JS_WIDGET_EXPRESSION, '$p.getWidget(\'"+g+"\')$1', $string);
		preg_match_all(self::$JS_EXPRESSION, $string, $expressionMatches);
		if(count($expressionMatches) > 0) {
			/* Let's ensure that our js-expression don't get addslashed */
			foreach($expressionMatches[1] as $match) {
				$fixedExpressions[] = '"+eval("'.Str::removeSlashes($match).'")+"';
			}
			/* Now we replace the expression tags, with the fixed js expression */
			for($i=0;$i<count($expressionMatches[0]);$i++) {
				$string = str_replace($expressionMatches[0][$i], $fixedExpressions[$i], $string);
			}
		}
		return $string;
	}

	protected function tagContainer($attrs) {
		$this->requireAttributes($attrs, array('id'));
		$output = sprintf('$.%1$s=function(d,g){var o="<%3$s>%2$s</%3$s>"; return o;};', $attrs->id, $this->makeJsString($this->getBody()), self::$JS_WRAPPER_TAG);
		$matches=array();

		preg_match_all('%<'.self::$JS_WRAPPER_TAG.'>(.*?)</'.self::$JS_WRAPPER_TAG.'>%', $output, $matches);
		if(isset($matches[1])) {
			foreach($matches[1] as $m) {
				$output = str_replace('<'.self::$JS_WRAPPER_TAG.'>'.$m.'</'.self::$JS_WRAPPER_TAG.'>', addslashes($m), $output);
			}
		}
		$this->containers[$attrs->id] = $this->replaceJsExpressions($output);
	}

	protected function tagIf($attrs) {
		$this->requireAttributes($attrs, array('test'));
		return sprintf('</%3$s>";if(%1$s){o+="<%3$s>%2$s</%3$s>"; } o+="<%3$s>', $this->makeJsString($attrs->test), $this->makeJsString($this->getBody()), self::$JS_WRAPPER_TAG);
	}

	protected function tagElse($attrs) {
		return sprintf('</%2$s>";}else{o+="<%2$s>%s', $this->makeJsString($this->getBody()), self::$JS_WRAPPER_TAG);
	}

	protected function tagElseIf($attrs) {
		$this->requireAttributes($attrs, array('test'));
		return sprintf('</%3$s>";}else if(%1$s){o+="<%3$s>%2$s', $attrs->test, $this->makeJsString($this->getBody()), self::$JS_WRAPPER_TAG);
	}

	protected function tagWhile($attrs) {
		$this->requireAttributes($attrs, array('test'));
		return sprintf('</%3$s>";while(%1$s){o+="<%3$s>%2$s</%3$s>";}o+="<%3$s>', $attrs->test, $this->makeJsString($this->getBody()), self::$JS_WRAPPER_TAG);
	}

	protected function tagEach($attrs) {
		$this->requireAttributes($attrs, array('in'));
		$row = (!isset($attrs->as)) ? 'row' : $attrs->as;
		$index = (!isset($attrs->index)) ? 'i' : $attrs->index;
		return sprintf('</%4$s>"; for(var %5$s=0;%5$s<%1$s.length;%5$s++){var %2$s=%1$s[%5$s]; o+="<%4$s>%3$s</%4$s>"; } o+="<%4$s>', $attrs->in, $row, $this->makeJsString($this->getBody()), self::$JS_WRAPPER_TAG, $index);
	}

	protected function tagFor($attrs) {
		$this->requireAttributes($attrs, array('limit', 'start', 'in'));
		return sprintf('</%5$s>";for(var %1$s=%2$s;%1$s<%3$s;%1$s++){o+="<%5$s>%4$s</%5$s>";}o+="<%5$s>', $attrs->in, $attrs->start, $attrs->limit, $this->makeJsString($this->getBody()), self::$JS_WRAPPER_TAG);
	}

	protected function tagBreak() {
		return sprintf('</%1$s>"; break; o+="<%1$s>', self::$JS_WRAPPER_TAG);
	}

	protected function tagCollect($attrs) {
		Site::getInstance()->addWrappedJs('pecee-widget.js');
		$output = array('<!-- JSTaglib start --><script>');
		if($this->containers) {
			foreach($this->containers as $c) {
				$output[] = $c;
			}
		}
		$output[] = '</script><!-- JSTaglib end -->';
		return join('', $output);
	}
}