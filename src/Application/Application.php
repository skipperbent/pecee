<?php

namespace Pecee\Application;

use Pecee\Application\UrlHandler\IUrlHandler;
use Pecee\Application\UrlHandler\UrlHandler;
use Pecee\Boolean;
use Pecee\Session\Session;
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
            '127.0.0.1',
        ]);

        Session::start();
    }

    /**
     * Get app secret
     *
     * @return string
     */
    public function getSecret(): string
    {
        return env('APP_SECRET', 'NoApplicationSecretDefined');
    }

    /**
     * @return array
     */
    public function getAdminIps(): array
    {
        return $this->adminIps;
    }

    /**
     * @param array $ips
     * @return static
     */
    public function setAdminIps(array $ips): self
    {
        $this->adminIps = $ips;

        return $this;
    }

    public function addAdminIp($ip): self
    {
        $this->adminIps[] = $ip;

        return $this;
    }

    public function hasAdminIp(string $ip = null): bool
    {
        $ip = $ip ?? \request()->getIp();

        if (\is_array($this->adminIps) === true) {
            return \in_array($ip, $this->adminIps, true);
        }

        return false;
    }

    /**
     * @return string $timezone
     */
    public function getTimezone(): string
    {
        return $this->timezone;
    }

    /**
     * @param string $timezone
     */
    public function setTimezone(string $timezone): void
    {
        $this->timezone = $timezone;
        date_default_timezone_set($timezone);
    }

    public function setLocale(string $locale): void
    {
        $this->locale = strtolower($locale);
        setlocale(LC_ALL, $locale);

        if ($this->translation->getProvider() !== null) {
            $this->translation->getProvider()->load($this->locale);
        }
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getDefaultLocale(): string
    {
        return $this->defaultLocale;
    }

    /**
     * Set site locale
     *
     * @param string $defaultLocale
     * @return static $this
     */
    public function setDefaultLocale(string $defaultLocale): self
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
    public function addModule(string $name, string $path): self
    {
        $this->modules[$name] = $path;

        return $this;
    }

    /**
     * Get module
     * @param string $name
     * @return string|null
     */
    public function getModule($name): ?string
    {
        return $this->modules[$name] ?? null;
    }

    /**
     * Get modules
     * @return array
     */
    public function getModules(): array
    {
        return $this->modules;
    }

    public function hasModules(): bool
    {
        return (\count($this->modules) > 0);
    }

    public function getCharset(): string
    {
        return $this->charset;
    }

    /**
     * Change the default wroute for the js wrapper
     *
     * @param string $url
     * @return static
     */
    public function setJsWrapRouteUrl(string $url): self
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
    public function setCssWrapRouteUrl(string $url): self
    {
        $this->cssWrapRouteUrl = $url;

        return $this;
    }

    public function getJsWrapRouteUrl(): ?string
    {
        return $this->jsWrapRouteUrl;
    }

    public function getCssWrapRouteUrl(): ?string
    {
        return $this->cssWrapRouteUrl;
    }

    /**
     * Get css wrapper route name
     *
     * @return string
     */
    public function getCssWrapRouteName(): ?string
    {
        return $this->cssWrapRouteName;
    }

    /**
     * Get js wrapper route name
     *
     * @return string
     */
    public function getJsWrapRouteName(): ?string
    {
        return $this->jsWrapRouteName;
    }

    /**
     * Disables all routes added by the framework.
     * Useful if running in cli or using a scraped site.
     *
     * @param bool $bool
     * @return static
     */
    public function setDisableFrameworkRoutes(bool $bool): self
    {
        $this->disableFrameworkRoutes = $bool;

        return $this;
    }

    public function getDisableFrameworkRoutes(): bool
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
    public function add(string $key, $value): self
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
    public function get(string $key, $defaultValue = null)
    {
        return $this->parameters[$key] ?? $defaultValue;
    }

    public function __set(string $name, $value): void
    {
        $this->parameters[$name] = $value;
    }

    public function __get(string $name)
    {
        return $this->parameters[$name] ?? null;
    }

    public function __isset(string $name): bool
    {
        return array_key_exists($name, $this->parameters);
    }

    public function setEncryptionMethod(string $method): self
    {
        $this->encryptionMethod = $method;

        return $this;
    }

    public function getEncryptionMethod(): string
    {
        return $this->encryptionMethod;
    }

    /**
     * Enable or disable debugging
     * @param bool $bool
     * @return static
     */
    public function setDebugEnabled(bool $bool): self
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
    public function getDebugEnabled(): bool
    {
        return $this->debugEnabled;
    }

    /**
     * Set url handler callback
     * @param IUrlHandler $callback
     * @return $this
     */
    public function setUrlHandler(IUrlHandler $callback): self
    {
        $this->urlHandler = $callback;

        return $this;
    }

    /**
     * Get url handler
     * @return IUrlHandler
     */
    public function getUrlHandler(): IUrlHandler
    {
        return $this->urlHandler;
    }

}