<?php
namespace Pecee;

class Locale {
    
	protected static $instance;
	protected $timezone;
	protected $defaultLocale;
	protected $locale;

	/**
	 * Get instance
	 * @return \Pecee\Locale
	 */
	public static function getInstance() {
		if(self::$instance === null) {
			self::$instance = new static();
		}
		return self::$instance;
	}

	public function __construct() {
		// Default stuff
		$this->setTimezone('Europe/Copenhagen');
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