<?php
require_once(dirname(__FILE__) . '/sonots/sonots.class.php');
require_once(dirname(__FILE__) . '/sonots/metapage.class.php');
//error_reporting(E_ALL);

/**
 * Get TITLE: line or the first headline of a page
 *
 * Usage:
 * - &get_title(page,[option])
 * Option
 * - firsthead Get the first headline instead of title
 *
 * @package    plugin
 * @license    http://www.gnu.org/licenses/gpl.html GPL v2
 * @author     sonots
 * @link       http://lsx.sourceforge.jp/?Plugin%2Fget_title.inc.php
 * @version    $Id: get_title.inc.php,v 2.0 2008-07-30 16:28:39Z sonots $
 */

function plugin_get_title_inline()
{
    $args = func_get_args();
    array_pop($args);
    $page = array_shift($args);
    $conf_options = array('firsthead' => false);
    $options = sonots::parse_options($args, $conf_options);
    if ($options['firsthead']) {
        $str = PluginSonotsMetapage::firsthead($page);
    } else {
        $str = PluginSonotsMetapage::title($page);
    }
    if (is_null($str)) {
        $str = $page;
    }
    $str = strip_htmltag(make_link($str));
    return $str;
}
?>