<?php

namespace Pecee;

class Str
{

    /**
     * Returns true if all provided values are not null
     *
     * @param ...$args
     * @return bool
     */
    public static function notNull(...$args): bool
    {
        return (count(array_filter($args, static function ($value) {
                return $value === null;
            })) === 0);
    }

    /**
     * Returns true if all provided values are not empty
     *
     * @param ...$args
     * @return bool
     */
    public static function notEmpty(...$args): bool
    {
        return (count(array_filter($args, static function ($value) {
                return empty($value);
            })) === 0);
    }

    /**
     * Converts double newline into paragraphs.
     * Example: Word1\n\nWord2 = <p>Word1</p><p>Word2</p>
     * @param string $text
     * @return string
     */
    public static function nl2p(string $text): string
    {
        return preg_replace('/[\r\n]{4}/', '</p><p>', '<p>' . $text . '</p>');
    }

    /**
     * Sanitize/compress html
     * @param string $html
     * @return string
     */
    public static function sanitizeHtml(string $html): string
    {

        $regex = '%# Collapse ws everywhere but in blacklisted elements.
        (?>             # Match all whitespans other than single space.
          [^\S ]\s*     # Either one [\t\r\n\f\v] and zero or more ws,
        | \s{2,}        # or two or more consecutive-any-whitespace.
        ) # Note: The remaining regex consumes no text at all...
        (?=             # Ensure we are not in a blacklist tag.
          (?:           # Begin (unnecessary) group.
            (?:         # Zero or more of...
              [^<]++    # Either one or more non-"<"
            | <         # or a < starting a non-blacklist tag.
              (?!/?(?:textarea|pre)\b)
            )*+         # (This could be "unroll-the-loop"ified.)
          )             # End (unnecessary) group.
          (?:           # Begin alternation group.
            <           # Either a blacklist start tag.
            (?>textarea|pre)\b
          | \z          # or end of file.
          )             # End alternation group.
        )  # If we made it here, we are not in a blacklist tag.
        %ix';

        return preg_replace($regex, '', $html);
    }

    public static function getFirstOrDefault(string $value, $default = null)
    {
        return (empty($value) === false) ? trim($value) : $default;
    }

    public static function encode(string $source, string $encoding = 'UTF-8'): string
    {
        return mb_convert_encoding($source, 'HTML-ENTITIES', $encoding);
    }

    public static function isUtf8(string $str): bool
    {
        return ($str === mb_convert_encoding(mb_convert_encoding($str, 'UTF-32', 'UTF-8'), 'UTF-8', 'UTF-32'));
    }

    public static function substr(string $text, string $maxLength, string $end = '...', string $encoding = 'UTF-8'): string
    {
        if (\strlen($text) > $maxLength) {
            return mb_substr($text, 0, $maxLength, $encoding) . $end;
        }

        return $text;
    }

    public static function wordWrap(string $text, int $limit): string
    {
        $words = explode(' ', $text);

        return implode(' ', array_splice($words, 0, $limit));
    }

    public static function base64Encode($obj): string
    {
        return base64_encode(serialize($obj));
    }

    public static function base64Decode(string $str, $defaultValue = null)
    {
        $req = base64_decode($str);
        if ($req !== false) {
            $req = unserialize($req, ['allowed_classes' => true]);
            if ($req !== null) {
                return $req;
            }
        }

        return $defaultValue;
    }

    public static function deCamelize(string $word, string $separator = '_'): string
    {
        return preg_replace_callback('/(^|[a-z])([A-Z])/',
            function ($matches) use ($separator) {
                return strtolower('' !== $matches[1] ? $matches[1] . $separator . $matches[2] : $matches[2]);
            },
            $word
        );
    }

    public static function camelize(string $word, string $separator = '_'): string
    {
        $words = preg_replace_callback('/(^|' . $separator . ')([a-z])/', function ($matches) {
            return strtoupper($matches[2]);
        }, strtolower($word));

        $words[0] = strtolower($words[0]);

        return $words;
    }

    /**
     * Returns weather the $value is a valid email.
     * @param string $email
     * @return bool
     */
    public static function isEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

}