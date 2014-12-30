<?php
/**
 * @author     lunt
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GPL 2
 * @version    $Id: monobook_toolbox.inc.php 196 2007-07-01 07:07:11Z lunt $
 */

function plugin_monobook_toolbox_init()
{
	$messages['_monobook_toolbox_messages'] = array(
		'add'           => 'ページに追加',
		'attachlist'    => '添付ファイルの一覧',
		'attachlistall' => '全添付ファイルの一覧',
		'backup'        => 'バックアップの表示',
		'copy'          => 'コピー',
		'diff'          => '変更個所の表示',
		'edit'          => 'ページの編集',
		'filelist'      => 'ファイル名の一覧',
		'freeze'        => '凍結',
		'help'          => 'ヘルプ',
		'list'          => '全ページ',
		'new'           => '新しいページの作成',
		'rdf'           => 'RDF',
		'recent'        => '最近更新したページ',
		'refer'         => '外部のリンク元',
		'related'       => 'リンク元',
		'reload'        => 'リロード',
		'rename'        => 'ページ名の変更',
		'rss'           => 'RSS',
		'rss10'         => 'RSS 1.0',
		'rss20'         => 'RSS 2.0',
		'search'        => '検索',
		'source'        => 'ソースの表示',
		'top'           => 'トップページ',
		'trackback'     => 'トラックバック',
		'unfreeze'      => '凍結解除',
		'upload'        => 'アップロード',
		'yetlist'       => '投稿が望まれているページ',
	);
	set_plugin_messages($messages);
}

function plugin_monobook_toolbox_convert()
{
	global $vars, $_monobook_toolbox_messages;
	
	$items = func_get_args();
	if (empty($items)) $items = array('add', 'backup', 'copy', 'diff', 'edit', 'filelist', 'freeze', 'help',
		'list', 'new', 'rdf', 'recent', 'refer', 'related', 'reload', 'rename', 'rss', 'rss10', 'rss20',
		'search', 'source', 'top', 'trackback', 'upload', 'attachlist', 'attachlistall', 'yetlist');
	
	if (! exist_plugin('monobook_getlink')) return;
	$body = '<div class="toolbox"><ul>';
	foreach ($items as $item) {
		if (! $item && $body !== '<div class="toolbox"><ul>') {
			$body .= '</ul><hr /><ul>';
			continue;
		}
		$link = plugin_monobook_getlink($item, $_monobook_toolbox_messages);
		if ($link) $body .= '<li>' . $link . '</li>';
	}
	if (substr($body, -15) === '</ul><hr /><ul>') $body = substr($body, 0, -15);
	$body .= '</ul></div>';
	
	return $body;
}
?>
