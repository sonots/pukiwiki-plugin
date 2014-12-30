<?php
/**
 * @author     lunt
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GPL 2
 * @version    $Id: monobook_navigation.inc.php 298 2008-01-03 14:10:30Z lunt $
 */

function plugin_monobook_navigation_init()
{
	$messages['_monobook_navigation_messages'] = array(
		'add'           => '追加',
		'article'       => '本文',
		'attachinfo'    => 'ファイル',
		'attachlist'    => '添付ファイル一覧',
		'attachlistall' => '全添付ファイル一覧',
		'backup'        => 'バックアップ',
		'copy'          => 'コピー',
		'diff'          => '差分',
		'discuss'       => 'ノート',
		'edit'          => '編集',
		'filelist'      => 'ファイル名一覧',
		'freeze'        => '凍結',
		'help'          => 'ヘルプ',
		'list'          => '一覧',
		'new'           => '新規',
		'rdf'           => 'RDF',
		'recent'        => '最終更新',
		'refer'         => 'リファラ',
		'related'       => 'リンク元',
		'reload'        => 'リロード',
		'rename'        => '名前変更',
		'revert'        => 'この版へ差し戻し',
		'rss'           => 'RSS',
		'rss10'         => 'RSS',
		'rss20'         => 'RSS',
		'search'        => '検索',
		'source'        => 'ソース',
		'top'           => 'トップ',
		'trackback'     => 'トラックバック',
		'unfreeze'      => '凍結解除',
		'upload'        => '添付',
		'yetlist'       => '未作成',
		'undefined'     => '特別ページ',
	);
	set_plugin_messages($messages);
}

function plugin_monobook_navigation($wikinote, $tabs, $background)
{
	global $vars, $plugin, $_monobook_navigation_messages;

	if (! exist_plugin('monobook_getlink')) die('monobook_getlink plugin not found');
	do_plugin_init('monobook_navigation');

	$main_tabs = '';
	if ($wikinote->is_effect()) {
		$main_tabs = str_replace(array('<ul class="wikinote">', '</ul>', "\n"), '',
			$wikinote->show_tabs(array(
				array('cmd' => 'main', 'label' => $_monobook_navigation_messages['article']),
				array('cmd' => 'note', 'label' => $_monobook_navigation_messages['discuss']),
			)));
	}

	$sub_tabs = '';
	$selected_flag = FALSE;
	foreach ($tabs as $tab) {
		if ($tab === 'edit' && is_freeze($vars['page']) && ! in_array('source', $tabs)) $tab = 'source';
		if ($tab === 'edit' && $plugin === 'paraedit') $tab = 'paraedit';
		if ($tab === 'edit' && exist_plugin('revert') && plugin_revert_getlink()) $tab = 'revert';
		list($link, $selected) = plugin_monobook_getlink($tab, $_monobook_navigation_messages, TRUE);
		if (! $link) continue;
		if ($selected) {
			$sub_tabs .= '<li class="selected">' . $link . '</li>';
			$selected_flag = TRUE;
		} else {
			$sub_tabs .= '<li>' . $link . '</li>';
		}
	}

	if (! $selected_flag) {
		$link = plugin_monobook_getlink('nowplugin', $_monobook_navigation_messages);
		if (! $main_tabs) {
			if ($link) {
				$sub_tabs = '<li class="selected" id="separate">' . $link .'</li>' . $sub_tabs;
			} else {
				$sub_tabs = '<li class="selected"><a href="' . get_script_uri() . strrchr($_SERVER['REQUEST_URI'], '?') .
					'">' . $_monobook_navigation_messages['undefined'] . '</a></li>';
			}
		} elseif ($main_tabs && ! arg_check('read') && $link) {
			$sub_tabs = '<li class="selected">' . $link . '</li>' . $sub_tabs;
		}
	}

	return '<div id="navigator"><ul' . $background . '>' . $main_tabs . $sub_tabs . '</ul></div>' . "\n";
}
?>
