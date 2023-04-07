<?php

namespace Pecee;

class Url
{

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

    public static function isValid(string $url): bool
    {
        return (preg_match('/^\w+:\/\/([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+):?(\d+)?\/?/i', $url) === 1);
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

    public static function urlEncodeString($string, $separator = '-', $maxLength = null): string
    {
        if ($maxLength !== null && \strlen($string) > $maxLength) {
            $string = mb_substr($string, 0, $maxLength);
        }

        $searchMap = [
            ' ' => $separator,
        ];

        $string = mb_convert_encoding($string, 'utf-8');
        $string = str_ireplace(array_keys($searchMap), $searchMap, mb_strtolower($string));
        $string = preg_replace('/[^\p{L}\w\+' . join('', $searchMap) . ']|(' . $separator . ')\1/ui', '', $string);
        return preg_replace('/\\'.$separator.'\\'.$separator.'+/', $separator, $string);
    }

    public static function isSecure(string $url): bool
    {
        return (strtolower(parse_url($url, PHP_URL_SCHEME)) === 'https');
    }
}