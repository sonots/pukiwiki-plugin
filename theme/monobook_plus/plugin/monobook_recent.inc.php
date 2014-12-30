<?php
/**
 * @author     lunt
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GPL 2
 * @version    $Id: monobook_recent.inc.php 196 2007-07-01 07:07:11Z lunt $
 */

function plugin_monobook_recent_convert()
{
	if (! exist_plugin_convert('recent')) return;
	$args = func_get_args();
	return str_replace('<div>', '<div class="monobook_recent">',
		call_user_func_array('plugin_recent_convert', $args));
}
?>
