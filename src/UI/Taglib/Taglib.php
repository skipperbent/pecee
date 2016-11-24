<?php
namespace Pecee\UI\Taglib;

class Taglib
{

	const TAG_PREFIX = 'tag';

	protected $body = '';
	protected $bodies = [];
	protected $preProcess;
	protected $currentTag;

	public function __construct($preProcess = false)
	{
		$this->preProcess = $preProcess;
	}

	public function callTag($tag, $attrs, $body)
	{
		$this->currentTag = $tag;
		$method = self::TAG_PREFIX . ucfirst($tag);

		if (!method_exists($this, $method)) {
			throw new \Exception('Unknown tag: ' . $tag . ' in ' . get_class($this));
		}

		$this->body = $body;
		$this->bodies[] = $body;

		return $this->$method(((object)$attrs));
	}

	public function __call($tag, $args)
	{
		$this->callTag($tag, $args[0], $args[1]);
	}

	protected function requireAttributes($attrs, array $name)
	{
		$errors = [];
		foreach ($name as $n) {
			if (!isset($attrs->$n)) {
				$errors[] = $n;
			}
		}
		if (count($errors) > 0) {
			throw new \ErrorException('The current tag "' . $this->currentTag . '" requires the attribute(s): ' . join(', ', $errors));
		}
	}

	public function getBody()
	{
		return $this->body;
	}

	public function setBody($body)
	{
		$this->body = $body;
	}

	public function isPreprocess()
	{
		return $this->preProcess;
	}

}