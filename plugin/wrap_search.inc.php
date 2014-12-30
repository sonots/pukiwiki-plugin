<?php
/**
 * @author     sonots
 *
 * based on
 *
 * @author     lunt
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GPL 2
 * @version    $Id: monobook_search.inc.php 214 2007-07-24 11:16:00Z lunt $
 */

function plugin_wrap_search_convert()
{
	if (! exist_plugin_convert('search')) return;
	$str = explode("\n", plugin_search_convert());
	$str = preg_grep('/value="OR"|\<label for\="|encode_hint/', $str, PREG_GREP_INVERT);
	$str = str_replace(
		array('type="radio"', 'type="text"', 'type="submit"'),
		array('type="hidden"', 'class="searchInput" type="text"',
			'class="searchButton" type="submit"'), $str);
	return "<div class=\"search\">\n" . implode("\n", $str) . "\n</div>";
}
?>
