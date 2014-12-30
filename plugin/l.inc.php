<?php
/** $Id: l.inc.php 227 2007-01-13 15:35:48Z sonots $
 * multilang wrapper. Shorten the word 'multilang' to 'l'
 * Usage: See multilang plugin
 */ 
function plugin_l_init()
{
    exist_plugin('multilang'); // to require_once
    if (function_exists('plugin_multilang_init')) plugin_multilang_init();
}

function plugin_l_action()
{
    return plugin_multilang_action();
}

function plugin_l_inline()
{
    $args = func_get_args();
    return call_user_func_array('plugin_multilang_inline', $args);
}

function plugin_l_convert()
{
    $args = func_get_args();
    return call_user_func_array('plugin_multilang_convert', $args);
}

?>
