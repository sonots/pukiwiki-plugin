<?php
/**
 * @author     sonots 
 *
 * based on
 *
 * @author     lunt
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GPL 2
 * @version    $Id: monobook_recent.inc.php 196 2007-07-01 07:07:11Z lunt $
 */

function plugin_whiteflow_recent_convert()
{
	if (! exist_plugin_convert('recent')) return;
	$args = func_get_args();
	$ret  = call_user_func_array('plugin_recent_convert', $args);
	$ret  = str_replace('<div>', '<div class="recent_list">', $ret);
	$ret  = preg_replace('/<ul[^>]*>/', '<ol>', $ret);
	$ret  = str_replace('</ul>', '</ol>', $ret);
	return $ret;
}
?>