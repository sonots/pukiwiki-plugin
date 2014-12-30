<?php
/**
 * Grep a page
 *
 * @author     sonots
 * @license    http://www.gnu.org/licenses/gpl.html GPL v2
 * @version    $Id: grep.inc.php,v 1.1 2007-06-10 11:14:46 sonots $
 * @package    plugin
 */

function plugin_grep_action()
{
    global $vars, $defaultpage;
    $page = isset($vars['page']) ? $vars['page'] : $defultpage;
    if (! is_page($page)) {
        $body = '<p>' . htmlspecialchars($page) . ' does not exist.</p>';
        return array('msg'=>'Grep Plugin', 'body'=>$body);
    }        
    if (! check_readable($page)) {
        $body = '<p>' . htmlspecialchars($page) . ' is not readable.</p>';
        return array('msg'=>'Grep Plugin', 'body'=>$body);
    }
    $grep = isset($vars['grep']) ? $vars['grep'] : '';
    $lines = get_source($page);
    $lines = preg_grep('/' . preg_quote($grep, '/') . '/', $lines);
    $contents = '';
    foreach ($lines as $i => $line) {
        $contents .= sprintf('%04d:', $i) . htmlspecialchars($line);
    }
    $body = '<pre>' . htmlspecialchars($contents) . '</pre>';
    return array('msg'=>'Grep Plugin', 'body'=>$body);
}
?>
