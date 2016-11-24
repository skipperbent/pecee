<?php
namespace Pecee\Application;

use Pecee\Translation\Translation;
use Pecee\UI\Site;

class Application
{
	const CHARSET_UTF8 = 'UTF-8';

	/**
	 * @var Debug
	 */
	public $debug;

	/**
	 * @var Translation
	 */
	public $translation;

	/**
	 * @var Site
	 */
	public $site;

	protected $adminIps = [
		'127.0.0.1',
		'::1',
	];

	protected $parameters = [];
	protected $charset;
	protected $timezone;
	protected $defaultLocale;
	protected $locale;
	protected $modules = [];
	protected $cssWrapRouteName = 'pecee.css.wrap';
	protected $jsWrapRouteName = 'pecee.js.wrap';
	protected $cssWrapRouteUrl = '/css-wrap';
	protected $jsWrapRouteUrl = '/js-wrap';
	protected $disableFrameworkRoutes = false;

	public function __construct()
	{
		$this->site = new Site();
		$this->translation = new Translation();
		$this->debug = new Debug();

		$this->charset = static::CHARSET_UTF8;

		// Default stuff
		$this->setTimezone('UTC');
		$this->setDefaultLocale('en-gb');
		$this->setLocale('en-gb');
	}

	/**
	 * @return array
	 */
	public function getAdminIps()
	{
		return $this->adminIps;
	}

	/**
	 * @param array $ips
	 * @return static
	 */
	public function setAdminIps(array $ips)
	{
		$this->adminIps = $ips;

		return $this;
	}

	public function addAdminIp($ip)
	{
		$this->adminIps[] = $ip;

		return $this;
	}

	public function hasAdminIp($ip = null)
	{
		$ip = ($ip === null) ? request()->getIp() : $ip;

		if (is_array($this->adminIps)) {
			return (in_array($ip, $this->adminIps));
		}

		return false;
	}

	/**
	 * @return string $timezone
	 */
	public function getTimezone()
	{
		return $this->timezone;
	}

	/**
	 * @param string $timezone
	 */
	public function setTimezone($timezone)
	{
		$this->timezone = $timezone;
		date_default_timezone_set($timezone);
	}

	public function setLocale($locale)
	{
		setlocale(LC_ALL, strtolower(str_replace('-', '_', $locale)));
		$this->locale = $locale;

		if ($this->translation->getProvider() !== null) {
			$this->translation->getProvider()->load($locale, $this->defaultLocale);
		}
	}

	public function getLocale()
	{
		return $this->locale;
	}

	public function getDefaultLocale()
	{
		return $this->defaultLocale;
	}

	/**
	 * Set site locale
	 *
	 * @param string $defaultLocale
	 * @return static $this
	 */
	public function setDefaultLocale($defaultLocale)
	{
		$this->defaultLocale = $defaultLocale;

		return $this;
	}

	/**
	 * Add new module
	 * @param string $name
	 * @param string $path
	 * @return static $this
	 */
	public function addModule($name, $path)
	{
		$this->modules[$name] = $path;

		return $this;
	}

	/**
	 * Get module
	 * @param string $name
	 * @return string
	 */
	public function getModule($name)
	{
		return isset($this->modules[$name]) ? $this->modules[$name] : null;
	}

	/**
	 * Get modules
	 * @return array
	 */
	public function getModules()
	{
		return $this->modules;
	}

	public function hasModules()
	{
		return (count($this->modules) > 0);
	}

	public function getCharset()
	{
		return $this->charset;
	}

	/**
	 * Change the default wroute for the js wrapper
	 *
	 * @param string $url
	 * @return static $this
	 */
	public function setJsWrapRouteUrl($url)
	{
		$this->jsWrapRouteUrl = $url;

		return $this;
	}

	/**
	 * Change the default url for the css wrapper
	 *
	 * @param string $url
	 * @return static $this
	 */
	public function setCssWrapRouteUrl($url)
	{
		$this->cssWrapRouteUrl = $url;

		return $this;
	}

	public function getJsWrapRouteUrl()
	{
		return $this->jsWrapRouteUrl;
	}

	public function getCssWrapRouteUrl()
	{
		return $this->cssWrapRouteUrl;
	}

	/**
	 * Get css wrapper route name
	 *
	 * @return string
	 */
	public function getCssWrapRouteName()
	{
		return $this->cssWrapRouteName;
	}

	/**
	 * Get js wrapper route name
	 *
	 * @return string
	 */
	public function getJsWrapRouteName()
	{
		return $this->jsWrapRouteName;
	}

	/**
	 * Disables all routes added by the framework.
	 * Useful if running in cli or using a scraped site.
	 *
	 * @param bool $bool
	 * @return static $this
	 */
	public function setDisableFrameworkRoutes($bool)
	{
		$this->disableFrameworkRoutes = $bool;

		return $this;
	}

	public function getDisableFrameworkRoutes()
	{
		return $this->disableFrameworkRoutes;
	}

	public function __set($name, $value)
	{
		$this->parameters[$name] = $value;
	}

	public function __get($name)
	{
		return isset($this->parameters[$name]) ? $this->parameters[$name] : null;
	}

}