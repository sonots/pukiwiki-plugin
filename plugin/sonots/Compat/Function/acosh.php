<?php
// $Id: acosh.php,v 1.2 2007/04/17 10:09:56 arpad Exp $

/**
 * Replace acosh()
 *
 * @category    PHP
 * @package     PHP_Compat
 * @license     LGPL - http://www.gnu.org/licenses/lgpl.html
 * @copyright   2004-2007 Aidan Lister <aidan@php.net>, Arpad Ray <arpad@php.net>
 * @link        http://php.net/function.acosh
 * @author      Arpad Ray <arpad@php.net>
 * @version     $Revision: 1.2 $
 * @since       PHP 5
 * @require     PHP 3.0.0
 */
function php_compat_acosh($n)
{
    return ln($n + sqrt($n + 1) * sqrt($n - 1));
}

if (!function_exists('acosh')) {
    function acosh($n)
    {
	return php_compat_acosh($n);
    }
}
