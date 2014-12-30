<?php
// $Id: str_ireplace.php,v 1.22 2007/04/17 10:09:56 arpad Exp $


/**
 * Replace str_ireplace()
 *
 * @category    PHP
 * @package     PHP_Compat
 * @license     LGPL - http://www.gnu.org/licenses/lgpl.html
 * @copyright   2004-2007 Aidan Lister <aidan@php.net>, Arpad Ray <arpad@php.net>
 * @link        http://php.net/function.str_ireplace
 * @author      Aidan Lister <aidan@php.net>
 * @author      Arpad Ray <arpad@php.net>
 * @version     $Revision: 1.22 $
 * @since       PHP 5
 * @require     PHP 4.0.0 (user_error)
 * @note        count is returned by reference (required parameter)
 *              to disable, change '&$count' to '$count = null'
 */
function php_compat_str_ireplace($search, $replace, $subject, &$count)
{
    // Sanity check
    if (is_string($search) && is_array($replace)) {
        user_error('Array to string conversion', E_USER_NOTICE);
        $replace = (string) $replace;
    }

    // If search isn't an array, make it one
    if (!is_array($search)) {
        $search = array ($search);
    }
    $search = array_values($search);

    // If replace isn't an array, make it one, and pad it to the length of search
    if (!is_array($replace)) {
        $replace_string = $replace;

        $replace = array ();
        for ($i = 0, $c = count($search); $i < $c; $i++) {
            $replace[$i] = $replace_string;
        }
    }
    $replace = array_values($replace);

    // Check the replace array is padded to the correct length
    $length_replace = count($replace);
    $length_search = count($search);
    if ($length_replace < $length_search) {
        for ($i = $length_replace; $i < $length_search; $i++) {
            $replace[$i] = '';
        }
    }

    // If subject is not an array, make it one
    $was_array = false;
    if (!is_array($subject)) {
        $was_array = true;
        $subject = array ($subject);
    }

    // Prepare the search array
    foreach ($search as $search_key => $search_value) {
        $search[$search_key] = '/' . preg_quote($search_value, '/') . '/i';
    }
    
    // Prepare the replace array (escape backreferences)
    foreach ($replace as $k => $v) {   
        $replace[$k] = str_replace(array(chr(92), '$'), array(chr(92) . chr(92), '\$'), $v);
    }

    // do the replacement
    $result = preg_replace($search, $replace, $subject, -1, $count);

    // Check if subject was initially a string and return it as a string
    if ($was_array === true) {
        return $result[0];
    }

    // Otherwise, just return the array
    return $result;
}


// Define
if (!function_exists('str_ireplace')) {
    function str_ireplace($search, $replace, $subject, $count = null)
    {
        return php_compat_str_ireplace($search, $replace, $subject, $count);
    }
}
