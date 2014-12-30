<?php
/**
 * Convert Arguments Wiki Syntax, but No Output
 *
 * @author     sonots
 * @license    http://www.gnu.org/licenses/gpl.html GPL v2
 * @link       http://lsx.sourceforge.jp/?Plugin%2Fenull.inc.php
 * @version    $Id: enull.inc.php,v 1.1 2007-02-24 16:28:39Z sonots $
 * @package    plugin
 */

function plugin_enull_convert()
{
    $args = func_get_args();
    $lines = array_pop($args);
    $lines = str_replace("\r", "\n", $lines);
    convert_html($lines);
    return '';
}

function plugin_enull_inline()
{
    $args = func_get_args();
    array_pop($args);
    $lines = implode(',', $args);
    convert_html($lines);
    return '';
}
?>
