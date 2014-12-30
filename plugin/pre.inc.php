<?php
/**
 * pre plugin
 *
 * @author     sonots
 * @license    http://www.gnu.org/licenses/gpl.html GPL v2
 * @version    $Id: pre.inc.php,v 1.1 2007-06-10 11:14:46 sonots $
 *             originally created by panda
 * @package    plugin
 */

function plugin_pre_inline() {
	$args = func_get_args();
	array_pop($args); // drop {}
	$body = implode(',', $args);
	return htmlspecialchars($body);
}
function plugin_pre_convert() {
	$args = func_get_args();
	if (count($args) == 0) {
		return FALSE;
	}
	$body = array_pop($args);
	$soft = (count($args) > 0 && $args[0] == 'soft');

	$body = str_replace("\r", "\n", $body);
	$body = $soft
		? make_link($body)
		: htmlspecialchars($body);
	return '<pre>' . $body . '</pre>';
}
?>
