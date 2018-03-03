<?php
namespace Pecee\Application;

use Pecee\Application\UrlHandler\IUrlHandler;
use Pecee\Application\UrlHandler\UrlHandler;
use Pecee\Boolean;
use Pecee\Translation\Translation;
use Pecee\UI\Site;

class Application
{
    public const CHARSET_UTF8 = 'UTF-8';

    protected $debugEnabled = false;

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
    protected $encryptionMethod = 'AES-256-CBC';
    protected $settings;
    /**
     * Callback for handling urls
     * @var IUrlHandler
     */
    protected $urlHandler;

    public function __construct()
    {
        $this->setDebugEnabled(env('DEBUG', false));

        $this->site = new Site();
        $this->translation = new Translation();
        $this->charset = static::CHARSET_UTF8;
        $this->urlHandler = new UrlHandler();

        // Default stuff
        $this->setTimezone('UTC');
        $this->setDefaultLocale('en_gb');
        $this->setLocale('en_gb');
        $this->setAdminIps([
            '127.0.0.1'
        ]);
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
        $ip = $ip ?? request()->getIp();

        if (\is_array($this->adminIps) === true) {
            return \in_array($ip, $this->adminIps, true);
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
        $this->locale = strtolower($locale);
        setlocale(LC_ALL, $locale);

        if ($this->translation->getProvider() !== null) {
            $this->translation->getProvider()->load($this->locale);
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
        return $this->modules[$name] ?? null;
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
        return (\count($this->modules) > 0);
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

    /**
     * Get application parameter
     *
     * @param string $key
     * @param mixed $value
     * @return static $this
     */
    public function add($key, $value)
    {
        $this->parameters[$key] = $value;

        return $this;
    }

    /**
     * Set application parameter
     *
     * @param string $key
     * @param mixed|null $defaultValue
     * @return mixed|null
     */
    public function get($key, $defaultValue = null)
    {
        return $this->parameters[$key] ?? $defaultValue;
    }

    public function __set($name, $value)
    {
        $this->parameters[$name] = $value;
    }

    public function __get($name)
    {
        return $this->parameters[$name] ?? null;
    }

    public function __isset($name)
    {
        return array_key_exists($name, $this->parameters);
    }

    public function setEncryptionMethod($method)
    {
        $this->encryptionMethod = $method;

        return $this;
    }

    public function getEncryptionMethod()
    {
        return $this->encryptionMethod;
    }

    /**
     * Enable or disable debugging
     * @param bool $bool
     * @return static
     */
    public function setDebugEnabled($bool)
    {
        $bool = Boolean::parse($bool);
        $this->debug = ($bool === true) ? new Debug() : null;
        $this->debugEnabled = $bool;

        return $this;
    }

    /**
     * Is debug enabled
     * @return bool
     */
    public function getDebugEnabled()
    {
        return $this->debugEnabled;
    }

    /**
     * Set url handler callback
     * @param IUrlHandler $callback
     * @return $this
     */
    public function setUrlHandler(IUrlHandler $callback)
    {
        $this->urlHandler = $callback;
        return $this;
    }

    /**
     * Get url handler
     * @return IUrlHandler
     */
    public function getUrlHandler()
    {
        return $this->urlHandler;
    }

}