<?php
// $Id: register_argc_argv_on.php,v 1.3 2007/04/17 10:09:56 arpad Exp $


/**
 * Emulate enviroment register_argc_argv=on
 *
 * @category    PHP
 * @package     PHP_Compat
 * @license     LGPL - http://www.gnu.org/licenses/lgpl.html
 * @copyright   2004-2007 Aidan Lister <aidan@php.net>, Arpad Ray <arpad@php.net>
 * @link        http://php.net/manual/en/ini.core.php#ini.register-argc-argv
 * @author      Aidan Lister <aidan@php.net>
 * @version     $Revision: 1.3 $
 */
if (!isset($GLOBALS['argc'], $GLOBALS['argv'])) {
    $GLOBALS['argc'] = $_SERVER['argc'];
    $GLOBALS['argv'] = $_SERVER['argv'];

    // Register the change
    ini_set('register_argc_argv', 'on');
}
