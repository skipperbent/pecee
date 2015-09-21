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
		if(is_null(self::$instance)) {
			self::$instance=new self();
		}
		return self::$instance;
	}
	
	public function __construct() {
		// Default stuff
		$this->setTimezone('Europe/Copenhagen');
		$this->setLocale('en-UK');
		$this->setDefaultLocale('en-UK');
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
		setlocale(LC_ALL, str_replace('-', '_', $locale));
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