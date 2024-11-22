<?php

namespace Pecee;

class Url
{

    /**
     * Encodes url components
     *
     * @param string $url
     * @return string
     * @throws \InvalidArgumentException Throw on invalid url
     */
    public static function encodeComponent(string $url): string
    {
        if (!static::isValid($url)) {
            throw new \InvalidArgumentException("Invalid URL: $url");
        }

        $components = parse_url($url);
        if (isset($components['path'])) {
            $path = join('/', array_map('rawurlencode', explode('/', $components['path'])));
            $url = str_replace($components['path'], $path, $url);
        }

        if (isset($components['query'])) {
            $url = str_replace('?' . $components['query'], urlencode('?' . $components['query']), $url);
        }

        return $url;
    }

    public static function hasParams(string $url): bool
    {
        return (strpos($url, '?') > -1);
    }

    public static function paramsToArray(string $query): array
    {
        $output = [];
        parse_str(trim($query, '?'), $output);

        return $output;
    }

    public static function getParamsSeparator(string $url): string
    {
        return (strpos($url, '?') > -1) ? '&' : '?';
    }

    /**
     * Returns true if URL is valid.
     * When strict is disabled, urls containing foreign characters will also pass as valid.
     *
     * @param string $url
     * @param bool $strict
     * @return bool
     */
    public static function isValid(string $url, bool $strict = false): bool
    {
        if ($strict) {
            return (filter_var($url, FILTER_VALIDATE_URL) !== false);
        }

        return (parse_url($url, PHP_URL_SCHEME) && parse_url($url, PHP_URL_HOST));
    }

    /**
     * Check if string is valid relative url
     * @param string $url
     * @return bool
     */
    public static function isValidRelative(string $url): bool
    {
        // PHP filter_var does not support relative urls, so we simulate a full URL
        return !(filter_var('http://www.example.com/' . ltrim($url, '/'), FILTER_VALIDATE_URL) === false);
    }

    public static function isValidHostname($hostname): bool
    {
        return (preg_match('/^ (?: [a-z0-9] (?:[a-z0-9\\-]* [a-z0-9])? \\. )*  #Subdomains
   							[a-z0-9] (?:[a-z0-9\\-]* [a-z0-9])?            #Domain
   							\\. [a-z]{2,6} $                               #Top-level domain
							/ix', $hostname) === 1);
    }

    public static function urlEncodeString(string $string, string $separator = '-', ?int $maxLength = null): string
    {
        if ($maxLength !== null && \strlen($string) > $maxLength) {
            $string = mb_substr($string, 0, $maxLength);
        }

        $searchMap = [
            ' ' => $separator,
            '+' => '-',
            ',' => '-',
        ];

        $string = mb_convert_encoding($string, 'utf-8');

        $string = preg_replace('/[^\p{L}\s\w\+' . join('', $searchMap) . ']|(' . $separator . ')\1/ui', '', $string);
        $string = str_ireplace(array_keys($searchMap), $searchMap, mb_strtolower($string));

        return preg_replace('/\\' . $separator . '\\' . $separator . '+/', $separator, $string);
    }

    public static function isSecure(string $url): bool
    {
        return (strtolower(parse_url($url, PHP_URL_SCHEME)) === 'https');
    }
}