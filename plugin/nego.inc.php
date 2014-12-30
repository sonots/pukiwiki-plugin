<?php
/**
 * Contents Negotiation
 *
 * @author     sonots
 * @license    http://www.gnu.org/licenses/gpl.html GPL v2
 * @link       http://lsx.sourceforge.jp/?Plugin%2Fnego.inc.php
 * @version    $Id: nego.inc.php,v 1.1 2007-06-10 11:14:46 sonots $
 * @package    plugin
 */

if(! defined('PLUGIN_NEGO_DEFAULT')) {
    define('PLUGIN_NEGO_DEFAULT', '');
}

function plugin_nego_action()
{
    global $vars;

    $page = isset($vars['page']) ? $vars['page'] : '';
    $nego = isset($vars['nego']) ? $vars['nego'] : PLUGIN_NEGO_DEFAULT;

    $parsed_url = parse_url(get_script_uri());
    $path = $parsed_url['path'];
    if (($pos = strrpos($path, '/')) !== FALSE) {
        $path = substr($path, 0, $pos + 1);
    }
    setcookie('nego', $nego, 0, $path);
    $_COOKIE['nego'] = $nego;

    header('Location: ' . get_script_uri() . '?' . rawurlencode($page) );
    exit;
}

function plugin_nego_inline()
{
    $args = func_get_args();
    $body = array_pop($args);
    if (count($args) > 0) {
        $nego = array_shift($args);
    } else {
        $nego = PLUGIN_NEGO_DEFAULT;
    }

    if ($nego == 'link') {
        if (count($args) > 0) {
            $nego = array_shift($args);
        } else {
            $nego = PLUGIN_NEGO_DEFAULT;
        }
        return plugin_nego_link($nego, $body);
    }

    if (plugin_nego_accept($nego)) {
        return $body;
    }
    return '';
}

function plugin_nego_convert()
{
    $args = func_get_args();
    $end = end($args);
    if (substr($end, -1) == "\r") {
        $body = array_pop($args);
        unset($end);
    } else {
        $body = '';
    }
    if (count($args) > 0) {
        $nego = array_shift($args);
    } else {
        $nego = PLUGIN_NEGO_DEFAULT;
    }

    if (plugin_nego_accept($nego)) {
        $body = str_replace("\r", "\n", $body);
        $body = convert_html($body);
        return $body;
    }
    return '';
}

function plugin_nego_accept($nego)
{
    $accept = isset($HTTP_COOKIE_VARS['nego']) ? $HTTP_COOKIE_VARS['nego'] : 
        isset($_COOKIE['nego']) ? $_COOKIE['nego'] : PLUGIN_NEGO_DEFAULT;
    return $nego === $accept;
}

function plugin_nego_link($nego, &$linkstr)
{
    global $vars;
    $linkstr = ($linkstr === '') ? htmlspecialchars($nego) : $linkstr;
    $url = get_script_uri().'?cmd=nego'.'&nego='.$nego.'&page='.rawurlencode($vars['page']);
    return '<a href="'.$url.'">'.$linkstr.'</a>';
}

?>
