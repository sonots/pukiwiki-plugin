<?php
/**
 * @author     lunt
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GPL 2
 * @version    $Id: relink.inc.php 271 2007-08-03 12:01:01Z lunt $
 */

define('PLUGIN_RELINK_ADD_EXTERNAL_CLASS_TO_IMAGE_LINK', 0);

// Rewrite rules
// 0. Default Type           : http://example.com/index.php?Menu%2FSubMenu
// 1. MediaWiki Type         : http://example.com/Menu%2FSubMenu
// 2. Directory Type         : http://example.com/Menu/SubMenu/
// 3. HTML Type              : http://example.com/Menu%2FSubMenu.html
// 4. Directory and HTML Type: http://example.com/Menu/SubMenu.html
//define('PLUGIN_RELINK_REWRITE_URL_TYPE', 0);

function plugin_relink($html)
{
	return preg_replace_callback('/(\<a[^\>]+\>)(\<img)?/', 'plugin_relink_callback', $html);
}

function plugin_relink_callback($matches)
{
	$atag = $matches[1];
	$img  = empty($matches[2]) ? '' : $matches[2]; // image tag
	
	preg_match_all('/([^\s^\"]+)=\"([^\"]+)\"/', $atag, $amatches);
	for ($i = 0; $i < count($amatches[0]); $i++) {
		$attr[$amatches[1][$i]] = $amatches[2][$i];
	}
	
	$parse_url = parse_url($attr['href']);
	$scheme    = isset($parse_url['scheme']) ? $parse_url['scheme'] : '';
	$path      = isset($parse_url['path']) ? $parse_url['path'] : '';
	$query     = isset($parse_url['query']) ? $parse_url['query'] : '';
	$fragment  = isset($parse_url['fragment']) ? '#' . $parse_url['fragment'] : '';
	$script    = get_script_uri();
	$is_ext    = $scheme && substr($attr['href'], 0, strlen($script)) !== $script;
	
	if ($is_ext && (! $img || $img && PLUGIN_RELINK_ADD_EXTERNAL_CLASS_TO_IMAGE_LINK)) {
		switch ($scheme) {
			case 'mailto':
				$attr['class'] = 'mail';
				break;
			case 'file':
				$attr['class'] = 'file';
				break;
			default:
				$attr['class'] = 'external';
		}
	}
	
	if (! $is_ext) {
		$attr['href'] = $path . ($query ? '?' . $query : '') . $fragment;
		if (isset($attr['rel'])) { unset($attr['rel']); }
		/*if (PLUGIN_RELINK_REWRITE_URL_TYPE && $query &&
			strpos($query, 'cmd=') === FALSE && strpos($query, 'plugin=') === FALSE)
		{
			$s_path = substr($path, 0, strrpos($path, '/') + 1); // cut index.php
			switch (PLUGIN_RELINK_REWRITE_URL_TYPE) {
				case 1:
					$attr['href'] = $s_path . $query . $fragment;
					break;
				case 2:
					$attr['href'] = $s_path . str_replace('%2F', '/', $query) . '/' . $fragment;
					break;
				case 3:
					$attr['href'] = $s_path . $query . '.html' . $fragment;
					break;
				case 4:
					$attr['href'] = $s_path . str_replace('%2F', '/', $query) . '.html' . $fragment;
					break;
			}
		}*/
	}
	
	$ret = '<a';
	foreach ($attr as $key => $val) {
		$ret .= ' ' . $key . '="' . $val . '"';
	}
	$ret .= '>' . $img;
	
	return $ret;
}
?>
