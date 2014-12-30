<!-- START #content > #menu > #navigation -->
<div id="navigation">
<h3>ツールボックス</h3>
<dl>
<?php
$_tag_new	= sprintf ("<dt class=\"%s\"><a href=\"%s?cmd=newpage&amp;refer=%s\">%s</a></dt>\n<dd>%s</dd>\n", "cmd-newpage", $script, $r_page, "新しく作成", "新しくページを作成");
$_tag_edit	= sprintf ("<dt class=\"%s\"><a href=\"%s\">%s</a></dt>\n<dd>%s</dd>\n", "cmd-edit", $link_edit, "編集", "このページを編集");
$_tag_freeze	= sprintf ("<dt class=\"%s\"><a href=\"%s\">%s</a></dt>\n<dd>%s</dd>\n", "cmd-freeze", $link_freeze, "凍結", "このページを凍結");
$_tag_unfreeze	= sprintf ("<dt class=\"%s\"><a href=\"%s\">%s</a></dt>\n<dd>%s</dd>\n", "cmd-unfreeze", $link_unfreeze, "凍結解除", "凍結を解除");
$_tag_source	= sprintf ("<dt class=\"%s\"><a href=\"%s?cmd=source&amp;page=%s\">%s</a></dt>\n<dd>%s</dd>\n", "cmd-source", $script, $e_page, "ソースコード", "このページのソースコードを表示");
$_tag_diff	= sprintf ("<dt class=\"%s\"><a href=\"%s\">%s</a></dt>\n<dd>%s</dd>\n", "cmd-diff", $link_diff, "変更点", "このページの最終更新箇所を表示");
$_tag_backup	= sprintf ("<dt class=\"%s\"><a href=\"%s\">%s</a></dt>\n<dd>%s</dd>\n", "cmd-backup", $link_backup, "更新履歴", "このページの更新履歴とバックアップを表示");
$_tag_upload	= sprintf ("<dt class=\"%s\"><a href=\"%s?cmd=attach&amp;pcmd=upload&amp;page=%s\">%s</a></dt>\n<dd>%s</dd>\n", "cmd-upload", $script, $e_page, "アップロード", "このページにファイルを添付");
$_tag_help	= sprintf ("<dt class=\"%s\"><a href=\"%s\">%s</a></dt>\n<dd>%s</dd>\n", "cmd-help", $link_help, "ヘルプ", "Wiki および書き方のルールについて");

if ($command == "newpage"){
	echo	'<dt class="newpage" id="work">新規作成</dt>'."\n".
		'<dd>現在実行中です</dd>'."\n";
} else{
	echo	$_tag_new;

	if ($command == "edit"){
		echo	'<dt class="edit" id="work">編集</dt>'."\n".
			'<dd>現在実行中です</dd>'."\n";
	} else{
		if ((PKWK_READONLY || $is_freeze) && (! edit_auth($vars['page'],TRUE,FALSE))) {
			echo	$_tag_source;
			if ($is_freeze){
				echo	$_tag_unfreeze;
			}
		} else{ 
			echo	$_tag_edit;
			echo	$_tag_freeze;
		}
	}
}

if ($command == "diff"){
	echo	'<dt class="diff" id="work">変更点</dt>'."\n".
		'<dd>現在実行中です</dd>'."\n";
	} else{
	echo	$_tag_diff;
}

if ($command == "backup"){
	echo	'<dt class="backup" id="work">更新履歴</dt>'."\n".
		'<dd>現在実行中です</dd>'."\n";
	} else{
	echo	$_tag_backup;
} 

echo	$_tag_upload;

if ($v_page == "Help"){ 
	echo	'<dt class="cmd-help" id="work">ヘルプ</dt>'."\n".
		'<dd>現在表示中です</dd>'."\n";
	} else{
	echo	$_tag_help;
}?>
</dl>
</div>
<!-- END #content > #menu > #navigation -->
