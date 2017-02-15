<?php
namespace Pecee\Translation\Providers;

use Pecee\Boolean;
use Pecee\Exceptions\TranslationException;

class XmlTranslateProvider implements ITranslationProvider
{
    protected $locale;
    protected $xml;
    protected $directory;

    public function __construct()
    {
        $this->directory = env('XML_TRANSLATION_DIR', env('base_path') . 'lang');
    }

    /**
     * Find xml translation-text
     * @param string $key
     * @return string|node
     * @throws TranslationException
     */
    public function lookup($key)
    {
        $xml = new \SimpleXmlElement($this->xml);
        $node = null;

        if (strpos($key, '.') !== false) {
            $children = explode('.', $key);
            foreach ($children as $i => $child) {
                if ($i === 0) {
                    $node = isset($xml->$child) ? $xml->$child : null;
                    continue;
                }
                $node = isset($node->$child) ? $node->$child : null;
            }
        } else {
            $node = isset($xml->$key) ? $xml->$key : null;
        }

        if ($node !== null) {
            return $node;
        }

        $exception = Boolean::parse(env('DISABLE_TRANSLATE_EXCEPTION', false));

        if($exception !== true) {
            throw new TranslationException(sprintf('Key "%s" does not exist for locale "%s"', $key, $this->locale));
        }

        return $key;
    }

    /**
     * Load xml file
     * @param string $locale
     * @throws TranslationException
     */
    public function load($locale)
    {
        $this->locale = $locale;
        $path = sprintf('%s/%s.xml', $this->directory, $locale);

        if (is_file($path) === false) {
            throw new TranslationException(sprintf('Language file %s not found for locale %s', $path, $locale));
        }

        $this->xml = file_get_contents($path, FILE_USE_INCLUDE_PATH);
    }

    /**
     * Get xml language directory
     * @return string
     */
    public function getDirectory()
    {
        return $this->directory;
    }

}