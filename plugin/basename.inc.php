<?php
// $Id: basename.inc.php 455 2007-02-19 08:24:54Z sonots $

function plugin_basename_convert()
{
    $args = func_get_args();
    return '<div id ="basename">' . call_user_func_array('plugin_basename_body', $args) . '</div>';
}

function plugin_basename_inline()
{
    $args = func_get_args();
    array_pop($args); // drop {}
    return '<span id="basename">' . call_user_func_array('plugin_basename_body', $args) . '</span>';
}

function plugin_basename_body()
{
    global $vars;
    global $defaultpage;

    $options['page'] = isset($vars['page']) ? $vars['page'] : $defaultpage;
    foreach (func_get_args() as $arg) {
        list($key, $val) = array_pad(explode('=', $arg, 2), 2, TRUE);
        $options[$key] = $val;
    }
    if ($options['page'] == '') return '';

    $basename = htmlspecialchars(basename($options['page']));
    if ($options['nolink'] || !is_page($options['page']))  {
        $body = $basename;
    } else {
        global $link_compact;
        $tmp = $link_compact; $link_compact = 1;
        $link = make_pagelink($options['page'], $basename);
        $link_compact = $tmp;
        return $link;
    }
    
    return $body;
}
?>
