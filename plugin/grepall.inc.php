<?php
/**
 * Grep all pages
 *
 * @author     sonots
 * @license    http://www.gnu.org/licenses/gpl.html GPL v2
 * @version    $Id: grepall.inc.php,v 1.1 2007-06-10 11:14:46 sonots $
 * @package    plugin
 */

function plugin_grepall_action()
{
    global $vars;
    if (isset($vars['pcmd']) && $vars['pcmd'] == 'grep') {
        $body = plugin_grepall_grep();
    } else {
        $body = plugin_grepall_get_form();
    }
    return array('msg'=>'Grep All Plugin', 'body'=>$body);
}

function plugin_grepall_grep()
{
    global $vars, $defaultpage;
    $page   = isset($vars['page']) ? $vars['page'] : $defultpage;
    $filter = isset($vars['filter']) ? $vars['filter'] : '';
    $grep   = isset($vars['grep']) ? $vars['grep'] : '';

    // page lists
    if ($page !== '') {
        if (! is_page($page)) {
            $body = '<p>' . htmlspecialchars($page) . ' does not exist.</p>';
            return $body;
        }        
        $pages = (array)$page;
    } else {
        $pages = get_existpages();
        if ($filter !== '') {
            $pages = preg_grep('/' . preg_quote($filter, '/') . '/', $pages);
        }
    }
    // grep
    $body = '';
    foreach ($pages as $page) {
        if (! check_readable($page)) {
            $body = '<p>' . htmlspecialchars($page) . ' is not readable.</p>';
            return $body;
        }
        $lines = get_source($page);
        $lines = preg_grep('/' . preg_quote($grep, '/') . '/', $lines);
        if (empty($lines)) continue;
        $contents = '';
        foreach ($lines as $i => $line) {
            $contents .= sprintf('%04d:', $i) . htmlspecialchars($line);
        }
        $body .= make_pagelink($page) . '<br />' . "\n";
        $body .= '<pre>' . htmlspecialchars($contents) . '</pre>';
    }
    return $body;
}

function plugin_grepall_get_form($msg = "")
{
    $form = '';
    $form .= '<form action="' . get_script_uri() . '?cmd=grep " method="post">' . "\n";
    $form .= '<div>' . "\n";
    $form .= ' <input type="hidden" name="pcmd"  value="grep" />' . "\n";
    $form .= ' <input type="text" name="page" size="24" value="" /> Page Name<br />' . "\n";
    $form .= ' <input type="text" name="filter" size="24" value="" /> Filter Pages (Regular Expression)<br />' . "\n";
    $form .= ' <input type="text" name="grep" size="24" value="" /> Search String (Regular Expression)<br />' . "\n";
    $form .= ' <input type="submit" name="submit" value="Grep All!" /><br />' . "\n";
    $form .= '</div>' . "\n";
    $form .= '</form>' . "\n";
    if ($msg != '') {
        $msg = '<p><strong>' . $msg . '</strong></p>';
    }
    return $msg . $form;
}

?>
