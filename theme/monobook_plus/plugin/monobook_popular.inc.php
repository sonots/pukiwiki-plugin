<?php
/**
 * @author     lunt
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GPL 2
 * @version    $Id: monobook_popular.inc.php 196 2007-07-01 07:07:11Z lunt $
 */

function plugin_monobook_popular_convert()
{
	if (! exist_plugin_convert('popular')) return;
	$args = func_get_args();
	return str_replace('<div>', '<div class="menubox">',
		call_user_func_array('plugin_popular_convert', $args));
}
?>
