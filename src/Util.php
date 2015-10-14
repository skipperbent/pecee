<?php
namespace Pecee;

class Util {

    public static function getTypeOf( $object ) {
        $typeof = null;
        if(is_array($object)) {
            foreach($object as $o) {
                $typeof=class_parents($o);
                break;
            }
        } else {
            $typeof=class_parents($object);
        }
        if( is_array($typeof) ) {
            $typeof=array_values($typeof);
            return $typeof[0];
        }
        return $typeof;
    }

    /**
     * Converts html to rgb
     * Taken directly from:
     * http://php.dtbaker.com.au/post/transform_hex_html_colors_to_rbg_colors
     *
     * @param string $color
     * @param bool $returnstring
     * @return array|false
     */
    public static function html2rgb($color,$returnstring=false) {
        if($color[0] == '#') {
            $color = substr($color, 1);
        }
        if(strlen($color) == 6) {
            list($r, $g, $b) = array($color[0].$color[1], $color[2].$color[3], $color[4].$color[5]);
        } elseif(strlen($color) == 3) {
            list($r, $g, $b) = array($color[0].$color[0], $color[1].$color[1], $color[2].$color[2]);
        } else {
            return false;
        }
        // use this for normal range 0 to 255 eg: (0, 255, 50)
        $key = 1;
        $r = hexdec($r)*$key;
        $g = hexdec($g)*$key;
        $b = hexdec($b)*$key;
        return ($returnstring) ? "{rgb $r $g $b}" : array($r, $g, $b);
    }

    /**
     * Returns weather the $value is a valid email.
     * @param string $email
     * @return bool
     */
    public static function is_email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

}