<?php
// $Id: register_globals_on.php,v 1.4 2007/04/17 10:09:56 arpad Exp $


/**
 * Emulate enviroment register_globals=on
 *
 * @category    PHP
 * @package     PHP_Compat
 * @license     LGPL - http://www.gnu.org/licenses/lgpl.html
 * @copyright   2004-2007 Aidan Lister <aidan@php.net>, Arpad Ray <arpad@php.net>
 * @link        http://php.net/register_globals
 * @author      Aidan Lister <aidan@php.net>
 * @author      Arpad Ray <arpad@php.net>
 * @version     $Revision: 1.4 $
 */
if (!ini_get('register_globals')) {
    $superglobals = array(
        'S' => '_SESSION',
        'E' => '_ENV',
        'C' => '_COOKIE',
        'P' => '_POST',
        'G' => '_GET'
    );
    $order = ini_get('variables_order');
    $order_length = strlen($order);
    $inputs = array();

    // determine on which arrays to operate and in what order
    for ($i = 0; $i < $order_length; $i++) {
        $key = strtoupper($order[$i]);
        if (!isset($superglobals[$key])
             || ($key == 'S' && !isset($_SESSION))) {
            continue;
        }
        if ($key == 'P' && $_SERVER['REQUEST_METHOD'] == 'POST') {
            $inputs[] = $_FILES;
        }
        $inputs[] = ${$superglobals[$key]};
    }

    // extract the specified arrays
    $superglobals[] = 'GLOBALS';
    for ($i = 0, $c = count($inputs); $i < $c; $i++) {
        // ensure users can't set superglobals
        $ins = array_intersect($superglobals, array_keys($inputs[$i]));
        if (empty($ins)) {
            extract($inputs[$i]);
        }
    }

    // Register the change
    ini_set('register_globals', 'on');
}
