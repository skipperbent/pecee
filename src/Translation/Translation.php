<?php
namespace Pecee\Translation;

class Translation {

	protected $translator;

	/**
	 * Translate message.
	 * @param string $key
	 * @param array $args
	 * @return string
	 */
	public function _($key, $args = null) {
		if (!is_array($args)) {
			$args = func_get_args();
			$args = array_slice($args, 1);
		}
		return vsprintf($this->lookup($key), $args);
	}

    /**
     * Translate message.
     * @param string $key
     * @param array $args
     * @return string
     */
    public function translate($key, $args = null) {
        if (!is_array($args)) {
            $args = func_get_args();
            $args = array_slice($args, 1);
        }
        return vsprintf($this->lookup($key), $args);
    }

	protected function lookup($key) {
        if($this->translator instanceof ITranslator) {
            return $this->translator->lookup($key);
        }

        return $key;
	}

	public function setTranslator(ITranslator $translator) {
		$this->translator = $translator;
	}

	public function getTranslator() {
		return $this->translator;
	}
}