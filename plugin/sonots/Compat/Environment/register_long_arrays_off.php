<?php
// $Id: register_long_arrays_off.php,v 1.3 2007/04/17 10:09:56 arpad Exp $


/**
 * Emulate enviroment register_long_arrays=off
 *
 * @category    PHP
 * @package     PHP_Compat
 * @license     LGPL - http://www.gnu.org/licenses/lgpl.html
 * @copyright   2004-2007 Aidan Lister <aidan@php.net>, Arpad Ray <arpad@php.net>
 * @link        http://php.net/manual/en/ini.core.php#ini.register-long-arrays
 * @author      Aidan Lister <aidan@php.net>
 * @version     $Revision: 1.3 $
 */
unset($HTTP_GET_VARS);
unset($HTTP_POST_VARS);
unset($HTTP_COOKIE_VARS);
unset($HTTP_SERVER_VARS);
unset($HTTP_ENV_VARS);
unset($HTTP_FILES_VARS);

// Register the change
ini_set('register_long_arrays', 'off');
