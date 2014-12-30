<?php
/**
 * @author     lunt
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GPL 2
 * @version    $Id: monobook_getlink.inc.php 267 2007-08-03 11:25:16Z lunt $
 */

function plugin_monobook_getlink($tab, $message, $check_selected = FALSE)
{
	global $vars, $plugin, $_LINK;
	global $do_backup, $function_freeze, $referer, $trackback;
	global $defaultpage, $whatsnew, $help_page, $cantedit;
	static $page, $is_page, $is_pagename, $is_editable, $is_freeze;

	if (is_null($page)) {
		$page        = empty($vars['page']) ? '' : $vars['page'];
		$is_page     = is_page($page);
		$is_pagename = is_pagename($page);
		$is_editable = is_editable($page);
		$is_freeze   = is_freeze($page);
	}
	
	if ($tab === 'nowplugin') {
		$tab = $plugin;
		if ($plugin === 'attach') {
			if (isset($vars['pcmd']) && $vars['pcmd'] === 'info') {
				$tab = 'attachinfo';
			} elseif (isset($vars['pcmd']) && $vars['pcmd'] === 'list') {
				$tab = empty($vars['refer']) ? 'attachlistall' : 'attachlist';
			} else {
				$tab = 'upload';
			}
		}
	}
	
	$link = $title = '';
	$selected = 0;
	switch ($tab) {
		case 'add' :
		case 'edit' :
			if (auth::check_role('readonly') || ! $is_editable) break;
			$link  = $_LINK[$tab];
			$title = $message[$tab];
			break;
		
		case 'attach' :
		case 'upload' :
			if (auth::check_role('readonly') || ! ini_get('file_uploads') || ! $is_page || ! $is_editable) break;
			$link  = $_LINK['upload'];
			$title = $message['upload'];
			if ($plugin === 'attach' && isset($vars['pcmd']) && $vars['pcmd'] === 'upload')
				$selected = 1;
			break;
		
		case 'attachinfo' :
			$link  = get_script_uri() . strrchr($_SERVER['REQUEST_URI'], '?');
			$title = $message['attachinfo'];
			$selected = 1;
			break;
		
		case 'attachlist' :
			if ($is_pagename) $link = get_script_uri() . '?plugin=attach&amp;pcmd=list&amp;refer=' .
				rawurlencode($page);
			elseif ($plugin === 'attach' && isset($vars['pcmd']) &&
				$vars['pcmd'] === 'list' && ! empty($vars['refer']))
			{
				$link = get_script_uri() . strrchr($_SERVER['REQUEST_URI'], '?');
				$selected = 1;
			}
			$title = $message['attachlist'];
			break;
		
		case 'attachlistall' :
			$link  = get_script_uri() . '?plugin=attach&amp;pcmd=list';
			$title = $message['attachlistall'];
			if ($plugin === 'attach' && isset($vars['pcmd']) &&
				$vars['pcmd'] === 'list' && empty($vars['refer']))
			{
				$selected = 1;
			}
			break;
		
		case 'backlink' :
		case 'related' :
			if (! $is_pagename) break;
			$link  = get_script_uri() . '?plugin=related&amp;page=' . rawurlencode($page);
			$title = $message['related'];
			$tab   = 'related';
			break;
		
		case 'backup' :
			if (! $do_backup || ! $is_pagename) break;
			$link  = $_LINK['backup'];
			$title = $message['backup'];
			break;
		
		case 'copy' :
		case 'template' :
			if (auth::check_role('readonly')) break;
			if ($is_page) $link = $_LINK['copy'];
			elseif ($plugin === 'template')
				$link = get_script_uri() . strrchr($_SERVER['REQUEST_URI'], '?');
			$title = $message['copy'];
			$tab   = 'template';
			break;
		
		case 'diff' :
			if (! $is_pagename) break;
			$link  = $_LINK['diff'];
			$title = $message['diff'];
			break;
		
		case 'filelist' :
		case 'help' :
		case 'list' :
		case 'rdf' :
		case 'recent' :
		case 'rss' :
		case 'rss10' :
		case 'rss20' :
		case 'search' :
		case 'top' :
			$link  = $_LINK[$tab];
			$title = $message[$tab];
			break;
		
		case 'freeze' :
		case 'unfreeze' :
			if (auth::check_role('readonly') || ! $function_freeze || ! $is_page || in_array($page, $cantedit)) break;
			$tab   = $is_freeze ? 'unfreeze' : 'freeze';
			$link  = $_LINK[$tab];
			$title = $message[$tab];
			break;
		
		case 'new' :
		case 'newpage' :
			if (auth::check_role('readonly')) break;
			$link  = $_LINK['new'];
			$title = $message['new'];
			$tab   = 'newpage';
			break;
		
		case 'paraedit' :
			$link  = get_script_uri() . strrchr($_SERVER['REQUEST_URI'], '?');
			$title = $message['edit'];
			break;
		
		case 'read' :
			$link  = $_LINK['reload'];
			$title = $message['article'];
			break;
		
		case 'refer' :
		case 'referer' :
			if (! $referer || ! $is_pagename) break;
			$link  = $_LINK['refer'];
			$title = $message['refer'];
			$tab   = 'referer';
			break;
		
		case 'reload' :
			if (! $page) break;
			$link  = $_LINK['reload'];
			$title = $message['reload'];
			break;
		
		case 'rename' :
			if (auth::check_role('readonly')) break;
			if ($is_page && $is_editable) $link = $_LINK['rename'];
			elseif ($plugin === 'rename')
				$link = get_script_uri() . strrchr($_SERVER['REQUEST_URI'], '?');
			$title = $message['rename'];
			break;
		
		case 'revert' :
			$link  = plugin_revert_getlink();
			$link  = $link ? $link : get_script_uri() . strrchr($_SERVER['REQUEST_URI'], '?');
			$title = $message['revert'];
			break;
		
		case 'source' :
			if (! $is_page || PKWK_SAFE_MODE) break;
			$link  = get_script_uri() . '?cmd=source&amp;page=' . rawurlencode($page);
			$title = $message['source'];
			break;
		
		case 'trackback' :
			if (! $trackback || ! $is_pagename) break;
			$link  = $_LINK['trackback'];
			$title = $message['trackback'] . ' (' . tb_count($page) . ')';
			break;
		
		case 'yetlist' :
			if (auth::check_role('readonly')) break;
			$link  = get_script_uri() . '?plugin=yetlist';
			$title = $message['yetlist'];
			break;
	}
	
	if ($link) $link = '<a href="' . $link . '">' . $title . '</a>';
	
	if (! $check_selected) return $link;
	
	if (($plugin !== 'attach' && $plugin === $tab) ||
		($page === $defaultpage && $tab === 'top') ||
		($page === $whatsnew && $tab === 'recent') ||
		($page === $help_page && $tab === 'help'))
	{
		$selected = 1;
	}
	
	return array($link, $selected);
}
?>
