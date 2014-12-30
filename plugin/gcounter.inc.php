<?php
/**
 * Global counter plugin (a simple counter wrapper)
 *
 * Usage:
 *  Refer counter.inc.php, same with it. 
 *
 * Technical Details: 
 *  PLUGIN_GCOUNTER_PAGE is automatically created and used as a log page
 *
 * @author     sonots
 * @license    http://www.gnu.org/licenses/gpl.html GPL v2
 * @version    $Id: gcounter.inc.php,v 1.3 2007-02-24 16:28:39Z sonots $
 * @package    plugin
 */

if (! defined('PLUGIN_GCOUNTER_PAGE')) 
define('PLUGIN_GCOUNTER_PAGE', ':gcounter');

function plugin_gcounter_inline()
{
    if(! exist_plugin_inline('counter')) 
        return '<span>gcounter(): counter plugin does not exist.</span>';
    if (plugin_gcounter_init_gcounter() === FALSE)
        return '<span>gcounter(): failed to create gcounter log file.</span>';
    global $vars;
    $tmp = $vars['page'];
    $vars['page'] = PLUGIN_GCOUNTER_PAGE;
    $args = func_get_args();
    $body = array_pop($args);
    $body = do_plugin_inline('counter', csv_implode(',', $args), $body);
    $vars['page'] = $tmp;
    return $body;
 
}
 
function plugin_gcounter_convert()
{
    if(! exist_plugin_convert('counter')) 
        return '<p>gcounter(): counter plugin does not exist.</p>';
    if (plugin_gcounter_init_gcounter() === FALSE)
        return '<p>gcounter(): failed to create gcounter log file.</p>';
    global $vars;
    $tmp = $vars['page'];
    $vars['page'] = PLUGIN_GCOUNTER_PAGE;
    $args = func_get_args();
    $body = do_plugin_convert('counter', csv_implode(',', $args));
    $vars['page'] = $tmp;
    return $body;
}

function plugin_gcounter_init_gcounter()
{
    if (is_page(PLUGIN_GCOUNTER_PAGE)) return;
    if (! exist_plugin('counter')) return;
    $pages = get_existpages();
    $gtotal = $gtoday = $gyesterday = 0;
    $gdate = $gip = '';
    foreach ($pages as $file => $page) {
        $counter = COUNTER_DIR . encode($page) . PLUGIN_COUNTER_SUFFIX;
        $lines = file($counter);
        $lines = array_map('rtrim', $lines);
        $total     = $lines[0];
        $date      = $lines[1];
        $today     = $lines[2];
        $yesterday = $lines[3];
        $ip        = $lines[4];
        $gtotal   += $total;
        // Ignore today, yesterday because they must take into account
        // $date (when is today), but will be deleted tomorrow. 
        // date and ip are also not important. 
    }
    $counter = COUNTER_DIR . encode(PLUGIN_GCOUNTER_PAGE) . PLUGIN_COUNTER_SUFFIX;
    $source = "$gtotal\n$gdate\n$gtoday\n$gyesterday\n$gip\n";
    if (! $fp = fopen($counter, "w")) {
        return FALSE;
    }
    if (! fwrite($fp, $source)) {
        return FALSE;
    }
    fclose($fp);
    page_write(PLUGIN_GCOUNTER_PAGE, "Log Page for Global Counter\n#counter");
}

?>