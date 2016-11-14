<?php
namespace Pecee;

class Locale {

	protected $timezone;
	protected $defaultLocale;
	protected $locale;

	public function __construct() {
		// Default stuff
		$this->setTimezone('UTC');
		$this->setLocale('en-gb');
		$this->setDefaultLocale('en-gb');
	}

	/**
	 * @return string $timezone
	 */
	public function getTimezone() {
		return $this->timezone;
	}

	/**
	 * @param string $timezone
	 */
	public function setTimezone($timezone) {
		$this->timezone = $timezone;
		date_default_timezone_set($timezone);
	}

	public function setLocale($locale) {
		/* Set PHP language */
		setlocale(LC_ALL, strtolower(str_replace('-', '_', $locale)));
		$this->locale=$locale;
	}

	public function getLocale() {
		return $this->locale;
	}

	public function getDefaultLocale() {
		return $this->defaultLocale;
	}

	public function setDefaultLocale($defaultLocale) {
		$this->defaultLocale = $defaultLocale;
	}

}