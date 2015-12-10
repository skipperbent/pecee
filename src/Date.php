<?php
namespace Pecee;
class Date {

	public static function isValid($date,$format='d-m-Y') {
		return (\DateTime::createFromFormat($format, $date) !== false);
	}

    public static function isDate($str) {
        $stamp = strtotime($str);
        if (!is_numeric($stamp)){
            return false;
        }
        $month = date('m', $stamp);
        $day = date('d', $stamp);
        $year = date( 'Y', $stamp);
        return (checkdate($month, $day, $year));
    }

	/**
	 * Returns age by given string date.
	 *
	 * @param int $time
	 * @return int
	 */
	public static function getAge($time) {
		$d = date('d', $time);
		$m = date('m', $time);
		$Y = date('Y', $time);
	    return( date('md') < $m.$d ? date('Y')-$Y-1 : date('Y')-$Y );
	}

	public static function toDate($time = null) {
		return date("Y-m-d", (($time === null) ? time() : $time));
	}

	public static function toDateTime($time = null) {
		return date("Y-m-d H:i:s", (($time === null) ? time() : $time));
	}

	/**
	 * Parse date to unix-time, weather it is dc time or MS datetime.
	 * @param string $date
	 * @return int
	 */
	public static function parseDate($date) {
    	// first see if strtotime manages it.
    	$matches = array();
   		$timestamp = strtotime($date);  // strtotime() returns -1 on failure
        /*
         * You might think from the php docs at http://us4.php.net/strtotime
		 * that php strtotime uses gnu strtotime. It doesn't. Rather, it implements its own grammar at:
		 * http://cvs.php.net/php-src/ext/standard/parsedate.y
		 * In http://cvs.php.net/php-src/ext/standard/
		 * As you can see there, the support for parsing ISO8601 dates is poor prior to v1.52 of Mar 1 05:42:27 2004
		 *
		 * As an example feed that has dates with timezone offsets, see
		 * http://www.rpgdot.com/team/rss/rss13.xml
		 * For example, it has dc:date of "2004-06-15T09:55+06:00".
         */
        // remove numerical timezone, and parse that ourselves
        if(preg_match('/(.*)([\+\-]\d\d):?(\d\d)/', $date, $matches)) {
            $timestamp = strtotime($matches[1]);
            $secs = 0 + $matches[2] * 3600 + $matches[3] * 60;
            // timezone offsets are local time minus UTC. so UTC = localtime - offset.
            $timestamp -= $secs;
        }
        return $timestamp;
	}

}