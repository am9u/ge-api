<?php defined('SYSPATH') or die('No direct script access.');

class Kohana_Expires {

    /**
     * Sets the amount of time before content expires
     *
     * @param   integer Seconds before the content expires
     * @return  array   Expire headers
     */
    public static function set($seconds = 60)
    {
        $now = time();
        $expires = $now + $seconds;

        $headers = array(
            'Last-Modified'     => gmdate('D, d M Y H:i:s T', $now),
            'Expires'           => gmdate('D, d M Y H:i:s T', $expires),
            'Cache-Control'     => 'max-age='.$seconds
        );

        return $headers;
    }

    /**
     * Parses the If-Modified-Since header
     *
     * @return  integer|boolean Timestamp or FALSE when header is lacking or malformed
     */
    public static function get()
    {
        if ( ! empty($_SERVER['HTTP_IF_MODIFIED_SINCE']))
        {
            // Some versions of IE6 append "; length=####"
            if (($strpos = strpos($_SERVER['HTTP_IF_MODIFIED_SINCE'], ';')) !== FALSE)
            {
                $mod_time = substr($_SERVER['HTTP_IF_MODIFIED_SINCE'], 0, $strpos);
            }
            else
            {
                $mod_time = $_SERVER['HTTP_IF_MODIFIED_SINCE'];
            }

            return strtotime($mod_time);
        }

        return FALSE;
    }

    /**
     * Checks to see if content should be updated
     *
     * @uses    Expires::get()
     *
     * @param   integer         Maximum age of the content in seconds
     * @return  array|boolean   Expire headers or FALSE when header is lacking or malformed
     */
    public static function check($seconds = 60)
    {
        if ($last_modified = Expires::get())
        {
            $expires = $last_modified + $seconds;
            $max_age = $expires - time();

            if ($max_age > 0)
            {       
                $headers = array(
                    'Last-Modified'     => gmdate('D, d M Y H:i:s T', $last_modified),
                    'Expires'           => gmdate('D, d M Y H:i:s T', $expires),
                    'Cache-Control'     => 'max-age='.$max_age
                );

                return $headers;
            }
        }

        return FALSE;
    }

    /**
     * Check if expiration headers are already set
     *
     * @return boolean
     */
    public static function headers_set()
    {
        foreach (headers_list() as $header)
        {
            if (strncasecmp($header, 'Expires:', 8) === 0
                OR strncasecmp($header, 'Cache-Control:', 14) === 0
                OR strncasecmp($header, 'Last-Modified:', 14) === 0)
            {
                return TRUE;
            }
        }

        return FALSE;
    }

} // End expires

