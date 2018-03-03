<?php
namespace Pecee;

class Url
{

    public static function hasParams($url)
    {
        return (strpos($url, '?') > -1);
    }

    public static function paramsToArray($query)
    {
        $output = [];
        parse_str(trim($query, '?'), $output);

        return $output;
    }

    public static function getParamsSeparator($url)
    {
        return (strpos($url, '?') > -1) ? '&' : '?';
    }

    public static function isValid($url)
    {
        return (preg_match('/^\w+:\/\/([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+):?(\d+)?\/?/i', $url) === 1);
    }

    /**
     * Check if string is valid relative url
     * @param string $url
     * @return bool
     */
    public static function isValidRelative($url)
    {
        return !preg_match('/[^\w.-]/', $url);
    }

    public static function isValidHostname($hostname)
    {
        return (preg_match('/^ (?: [a-z0-9] (?:[a-z0-9\\-]* [a-z0-9])? \\. )*  #Subdomains
   							[a-z0-9] (?:[a-z0-9\\-]* [a-z0-9])?            #Domain
   							\\. [a-z]{2,6} $                               #Top-level domain
							/ix', $hostname) === 1);
    }

    public static function urlEncodeString($string, $separator = '-', $maxLength = 50)
    {
        if ($maxLength !== null && \strlen($string) > $maxLength) {
            $string = substr($string, 0, $maxLength);
        }

        $searchMap = [
            'æ' => 'ae',
            'ø' => 'o',
            'å' => 'a',
            ' ' => $separator,
        ];

        $string = str_ireplace(array_keys($searchMap), $searchMap, strtolower($string));

        return preg_replace('/[^\w\ \+\&' . implode('', $searchMap) . ']/i', '', $string);
    }

    public static function isSecure($url)
    {
        return (strtolower(parse_url($url, PHP_URL_SCHEME)) === 'https');
    }
}