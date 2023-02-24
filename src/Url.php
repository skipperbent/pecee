<?php

namespace Pecee;

class Url
{

    public static function hasParams($url): bool
    {
        return (strpos($url, '?') > -1);
    }

    public static function paramsToArray($query): array
    {
        $output = [];
        parse_str(trim($query, '?'), $output);

        return $output;
    }

    public static function getParamsSeparator($url): string
    {
        return (strpos($url, '?') > -1) ? '&' : '?';
    }

    public static function isValid($url): bool
    {
        return (preg_match('/^\w+:\/\/([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+):?(\d+)?\/?/i', $url) === 1);
    }

    /**
     * Check if string is valid relative url
     * @param string $url
     * @return bool
     */
    public static function isValidRelative($url): bool
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

    public static function urlEncodeString($string, $separator = '-', $maxLength = null)
    {
        if ($maxLength !== null && \strlen($string) > $maxLength) {
            $string = substr($string, 0, $maxLength);
        }

        $searchMap = [
            ' ' => $separator,
        ];

        $string = preg_replace("/&([a-z])[a-z]+;/i", "$1", $string);
        $string = str_ireplace(array_keys($searchMap), $searchMap, strtolower($string));

        $string = preg_replace('/[^\w\+' . join('', $searchMap) . ']|(-)\1{1,}/i', '', $string);
        return htmlentities($string);
    }

    public static function isSecure(string $url): bool
    {
        return (strtolower(parse_url($url, PHP_URL_SCHEME)) === 'https');
    }
}