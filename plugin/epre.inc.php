<?php
/**
 * Show Converted HTML Output as a Pre-formatted Text
 *
 * @author     sonots
 * @license    http://www.gnu.org/licenses/gpl.html GPL v2
 * @link       http://lsx.sourceforge.jp/?Plugin%2Fepre.inc.php
 * @version    $Id: epre.inc.php,v 1.1 2007-02-24 16:28:39Z sonots $
 * @package    plugin
 */

function plugin_epre_convert()
{
    $args = func_get_args();
    $lines = array_pop($args);
    $lines = str_replace("\r", "\n", $lines);
    return '<pre>' . htmlspecialchars(convert_html($lines)) . '</pre>';
}
?>
