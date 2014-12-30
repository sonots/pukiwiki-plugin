<?php
/**
 * Edit Plugin using TinyMCE (a simple modification of edit.inc.php)
 *
 * @author     sonots
 * @license    http://www.gnu.org/licenses/gpl.html GPL v2
 * @link       http://lsx.sourceforge.jp/?Plugin%2Fmceedit.inc.php
 * @version    $Id: mceedit.inc.php,v 1.1 2007-02-24 16:28:39Z sonots $
 * @package    plugin
 */

// Remove #freeze written by hand
define('PLUGIN_EDIT_FREEZE_REGEX', '/^(?:#freeze(?!\w)\s*)+/im');

function plugin_mceedit_action()
{
	// global $vars, $_title_edit, $load_template_func;
	global $vars, $load_template_func;

	// if (PKWK_READONLY) die_message( _('PKWK_READONLY prohibits editing') );
	if (auth::check_role('readonly')) die_message( _('PKWK_READONLY prohibits editing') );

	if (isset($vars['realview'])) {
		return plugin_mceedit_realview();
	}

	$page = isset($vars['page']) ? $vars['page'] : '';
	check_editable($page, true, true);

	if (isset($vars['preview']) || ($load_template_func && isset($vars['template']))) {
		return plugin_mceedit_preview();
	} else if (isset($vars['write'])) {
		return plugin_mceedit_write();
	} else if (isset($vars['cancel'])) {
		return plugin_mceedit_cancel();
	}

	$source = get_source($page);
	$postdata = $vars['original'] = join('', $source);
	if (!empty($vars['id']))
	{
		$postdata = plugin_mceedit_parts($vars['id'],$source);
		if ($postdata === FALSE)
		{
			unset($vars['id']); // なかったことに :)
			$postdata = $vars['original'];
		}
	}
	if ($postdata == '') $postdata = auto_template($page);

	return array('msg'=> _('Edit of  $1'), 'body'=>plugin_mceedit_edit_form($page, $postdata));
}

// Preview by Ajax
function plugin_mceedit_realview()
{
	global $vars;

	$vars['msg'] = preg_replace(PLUGIN_EDIT_FREEZE_REGEX, '' ,$vars['msg']);
	$postdata = $vars['msg'];

	if ($postdata) {
		$postdata = make_str_rules($postdata);
		$postdata = explode("\n", $postdata);
		$postdata = drop_submit(convert_html($postdata));
	}
	// Feeding start
	pkwk_common_headers();
	header('Content-type: text/xml; charset=UTF-8');
	print '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	print $postdata;

	$longtaketime = getmicrotime() - MUTIME;
	$taketime     = sprintf('%01.03f', $longtaketime);
	print '<span class="small1">(Time:' . $taketime . ')</span>';
	exit;
}

// Preview
function plugin_mceedit_preview()
{
	global $vars;
	// global $_title_preview, $_msg_preview, $_msg_preview_delete;

	$page = isset($vars['page']) ? $vars['page'] : '';

	// Loading template
	if (isset($vars['template_page']) && is_page($vars['template_page'])) {

		$vars['msg'] = join('', get_source($vars['template_page']));

		// Cut fixed anchors
		$vars['msg'] = preg_replace('/^(\*{1,3}.*)\[#[A-Za-z][\w-]+\](.*)$/m', '$1$2', $vars['msg']);
	}

	// 手書きの#freezeを削除
	$vars['msg'] = preg_replace(PLUGIN_EDIT_FREEZE_REGEX, '' ,$vars['msg']);
	$postdata = $vars['msg'];

	if (isset($vars['add']) && $vars['add']) {
		if (isset($vars['add_top']) && $vars['add_top']) {
			$postdata  = $postdata . "\n\n" . @join('', get_source($page));
		} else {
			$postdata  = @join('', get_source($page)) . "\n\n" . $postdata;
		}
	}

	$body = _('To confirm the changes, click the button at the bottom of the page') . "<br />\n";
	if ($postdata == '')
		$body .= "<strong>" .
			 _('(The contents of the page are empty. Updating deletes this page.)') .
			 "</strong>";
	$body .= "<br />\n";

	if ($postdata) {
		$postdata = make_str_rules($postdata);
		$postdata = explode("\n", $postdata);
		$postdata = drop_submit(convert_html($postdata));
		$body .= '<div id="preview">' . $postdata . '</div>' . "\n";
	}
	$body .= plugin_mceedit_edit_form($page, $vars['msg'], $vars['digest'], FALSE);

	return array('msg'=> _('Preview of  $1'), 'body'=>$body);
}

// Inline: Show edit (or unfreeze text) link
function plugin_mceedit_inline()
{
	static $usage = '&edit(pagename#anchor[[,noicon],nolabel])[{label}];';

	global $script, $vars, $fixed_heading_edited;
	global $_symbol_paraedit;

	if (!$fixed_heading_edited || is_freeze($vars['page'])) {
		return '';
	}

	$args = func_get_args();

	$s_label = strip_htmltag(array_pop($args), FALSE); // {label}. Strip anchor tags only
	if ($s_label == '')
	{
		$s_label = $_symbol_paraedit;
	}

	list($page,$id) = array_pad($args,2,'');
	if (!is_page($page))
	{
		$page = $vars['page'];
	}
	if ($id != '')
	{
		$id = '&amp;id='.rawurlencode($id);
	}
	$r_page = rawurlencode($page);
	return "<a class=\"anchor_super\" href=\"$script?cmd=edit&amp;page=$r_page$id\">$s_label</a>";
}

// Write, add, or insert new comment
function plugin_mceedit_write()
{
	global $vars, $trackback;
	global $notimeupdate;
//	global $_title_collided, $_msg_collided_auto, $_msg_collided, $_title_deleted;
//	global $_msg_invalidpass;

	$page = isset($vars['page']) ? $vars['page'] : '';
	$retvars = array();

	// 手書きの#freezeを削除
	$vars['msg'] = preg_replace('/^#freeze\s*$/im','',$vars['msg']);
		$vars['msg'] = $vars['before'] . "\n#html{{\n" . $vars['msg'] . "\n}}\n"; //TinyMCE
	$postdata = $postdata_input = $vars['msg'];

	if (isset($vars['add']) && $vars['add']) {
		if (isset($vars['add_top']) && $vars['add_top']) {
			$postdata  = $postdata . "\n\n" . @join('', get_source($page));
		} else {
			$postdata  = @join('', get_source($page)) . "\n\n" . $postdata;
		}
	} else {
		if (isset($vars['id']) && $vars['id']) {
			$source = preg_split('/([^\n]*\n)/',$vars['original'],-1,PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE);
			if (plugin_mceedit_parts($vars['id'],$source,$vars['msg']) !== FALSE) {
				$postdata = $postdata_input = join('',$source);
			} else {
				// $post['msg']だけがページに書き込まれてしまうのを防ぐ。
				$postdata = $postdata_input = rtrim($vars['original'])."\n\n".$vars['msg'];
			}
		}
	}

	$oldpagesrc = join('', get_source($page));
	$oldpagemd5 = md5($oldpagesrc);

	if (! isset($vars['digest']) || $vars['digest'] != $oldpagemd5) {
		$vars['digest'] = $oldpagemd5;

		$retvars['msg'] = _('On updating  $1, a collision has occurred.');
		list($postdata_input, $auto) = do_update_diff($oldpagesrc, $postdata_input, $vars['original']);

		$_msg_collided_auto =
		_('It seems that someone has already updated this page while you were editing it.<br />') .
		_('The collision has been corrected automatically, but there may still be some problems with the page.<br />') .
		_('To confirm the changes to the page, press [Update].<br />');

		$_msg_collided =
		_('It seems that someone has already updated this page while you were editing it.<br />') .
		_(' + is placed at the beginning of a line that was newly added.<br />') .
		_(' ! is placed at the beginning of a line that has possibly been updated.<br />') .
		_(' Edit those lines, and submit again.');

		$_msg_invalidpass = _('Invalid password.');

		$retvars['body'] = ($auto ? $_msg_collided_auto : $_msg_collided)."\n";

		if (TRUE) {
			global $do_update_diff_table;
			$retvars['body'] .= $do_update_diff_table;
		}

		unset($vars['id']);	// 更新が衝突したら全文編集に切り替え
		$retvars['body'] .= plugin_mceedit_edit_form($page, $postdata_input, $oldpagemd5, FALSE);
	}
	else {
		if ($postdata) {
			$notimestamp = ($notimeupdate != 0) && (isset($vars['notimestamp']) && $vars['notimestamp'] != '');
			// if($notimestamp && ($notimeupdate == 2) && !pkwk_login($vars['pass'])) {
			if ($notimestamp && ($notimeupdate == 2) && auth::check_role('role_adm_contents') && !pkwk_login($vars['pass'])) {
				// enable only administrator & password error
				$retvars['body']  = "<p><strong>$_msg_invalidpass</strong></p>\n";
				$retvars['body'] .= plugin_mceedit_edit_form($page, $vars['msg'], $vars['digest'], FALSE);
			} else {
				page_write($page, $postdata, $notimestamp);
				pkwk_headers_sent();
				if ($vars['refpage'] != '') {
					if ($vars['id'] != '') {
						header('Location: ' . get_script_uri() . '?' . rawurlencode($vars['refpage'])) . '#' . rawurlencode($vars['id']);
					} else {
						header('Location: ' . get_script_uri() . '?' . rawurlencode($vars['refpage']));
					}
				} else {
					if ($vars['id'] != '') {
						header('Location: ' . get_script_uri() . '?' . rawurlencode($page)) . '#' . rawurlencode($vars['id']);
					} else {
						header('Location: ' . get_script_uri() . '?' . rawurlencode($page));
					}
				}
				exit;
			}
		} else {
			$_title_deleted = _(' $1 was deleted');

			page_write($page, $postdata);
			$retvars['msg'] = $_title_deleted;
			$retvars['body'] = str_replace('$1', htmlspecialchars($page), $_title_deleted);
			if ($trackback) tb_delete($page);
		}
	}

	return $retvars;
}

// Cancel (Back to the page / Escape edit page)
function plugin_mceedit_cancel()
{
	global $vars;
	pkwk_headers_sent();
	header('Location: ' . get_script_uri() . '?' . rawurlencode($vars['page']));
	exit;
}

// ソースの一部を抽出/置換する
function plugin_mceedit_parts($id,&$source,$postdata='')
{
	$postdata = rtrim($postdata)."\n";
	$heads = preg_grep('/^\*{1,3}.+$/',$source);
	$heads[count($source)] = ''; // sentinel
	while (list($start,$line) = each($heads))
	{
		if (preg_match("/\[#$id\]/",$line))
		{
			list($end,$line) = each($heads);
			return join('',array_splice($source,$start,$end - $start,$postdata));
		}
	}
	return FALSE;
}

// From lib/html.php
// Show 'edit' form
function plugin_mceedit_edit_form($page, $postdata, $digest = FALSE, $b_template = TRUE)
{
	global $script, $vars, $rows, $cols, $hr, $function_freeze;
	global $whatsnew, $load_template_func;
	global $notimeupdate;
	global $_button, $_string;
	global $ajax;

	// Newly generate $digest or not
	if ($digest === FALSE) $digest = md5(join('', get_source($page)));

		// TinyMCE
		$lines = explode("\n", $postdata);
		if(preg_match('/^\*{1,3}/m', $lines[0]) !== 0) {
				$before = array_shift($lines);
		}
		$postdata = implode("\n", $lines);
		$postdata = trim($postdata);
	if(preg_match('/\A#html{{[\r\n](.*)[\r\n]}}\Z/m', $postdata, $matches) === 0){
		return "<p>The format of text data has to be<br />#html{{<br />}}<br /> or <br />**One headline<br />#html{{<br />}}<br />(This is for paragraph editing). </p>";
		}               
	$postdata = $matches[1];
	
	$refer = $template = $addtag = $add_top = $add_ajax = '';

	$checked_top  = isset($vars['add_top'])     ? ' checked="checked"' : '';
	$checked_time = isset($vars['notimestamp']) ? ' checked="checked"' : '';

	if(isset($vars['add'])) {
		$addtag  = '<input type="hidden" name="add" value="true" />';
		$add_top = '<input type="checkbox" name="add_top" value="true"' .
			$checked_top . ' /><span class="small">' .
			$_button['addtop'] . '</span>';
	}

	if($load_template_func && $b_template) {
		$pages  = array();
		foreach(get_existpages() as $_page) {
			if ($_page == $whatsnew || check_non_list($_page))
				continue;
			$s_page = htmlspecialchars($_page);
			$pages[$_page] = '   <option value="' . $s_page . '">' .
				$s_page . '</option>';
		}
		ksort($pages);
		$s_pages  = join("\n", $pages);
		$template = <<<EOD
  <select name="template_page">
   <option value="">-- {$_button['template']} --</option>
$s_pages
  </select>
  <input type="submit" name="template" value="{$_button['load']}" accesskey="r" />
  <br />
EOD;

		if (isset($vars['refer']) && $vars['refer'] != '')
			$refer = '[[' . strip_bracket($vars['refer']) . ']]' . "\n\n";
	}

	$r_page      = rawurlencode($page);
	$s_page      = htmlspecialchars($page);
	$s_digest    = htmlspecialchars($digest);
	$s_postdata  = htmlspecialchars($refer . $postdata);
	$s_original  = isset($vars['original']) ? htmlspecialchars($vars['original']) : $s_postdata;
	$s_id        = isset($vars['id']) ? htmlspecialchars($vars['id']) : '';
	$b_preview   = isset($vars['preview']); // TRUE when preview
	$btn_preview = $b_preview ? $_button['repreview'] : $_button['preview'];

	if ($ajax) {
		$add_ajax = '<input type="button" name="add_ajax" value="'.$btn_preview.'" accesskey="p" onclick="pukiwiki_apx(this.form.page.value)" />';
	}

	$add_notimestamp = '';
	if ( $notimeupdate != 0 ) {
		// enable 'do not change timestamp'
		$add_notimestamp = <<<EOD
  <input type="checkbox" name="notimestamp" id="_edit_form_notimestamp" value="true"$checked_time />
  <label for="_edit_form_notimestamp"><span class="small">{$_button['notchangetimestamp']}</span></label>
EOD;
		if ( $notimeupdate == 2 && auth::check_role('role_adm_contents')) {
			// enable only administrator
			$add_notimestamp .= <<<EOD
  <input type="password" name="pass" size="12" />
EOD;
		}
		$add_notimestamp .= '&nbsp;';
	}
	$refpage = htmlspecialchars($vars['refpage']);
	$add_assistant = edit_form_assistant();

	$body = <<<EOD
<div id="realview_outer"><div id="realview"></div><br /></div>
<form action="$script" method="post">
 <div class="edit_form" onmouseup="pukiwiki_pos()" onkeyup="pukiwiki_pos()">
$template
  $addtag
  <input type="hidden" name="cmd"    value="mceedit" />
  <input type="hidden" name="page"   value="$s_page" />
  <input type="hidden" name="digest" value="$s_digest" />
  <input type="hidden" name="id"     value="$s_id" />
  <input type="hidden" name="before" value="$before" />
  <textarea class="mceEditor" id="msg" name="msg" rows="$rows" cols="$cols" onselect="pukiwiki_apv(this.form.page.value,this)" onfocus="pukiwiki_apv(this.form.page.value,this)" onkeyup="pukiwiki_apv(this.form.page.value,this)" onmouseup="pukiwiki_apv(this.form.page.value,this)">$s_postdata</textarea>
  <br />
  $add_assistant
  <br />
  <input type="submit" name="write"   value="{$_button['update']}" accesskey="s" />
  $add_top
  $add_ajax
  $add_notimestamp
  <input type="submit" name="cancel"  value="{$_button['cancel']}" accesskey="c" />
  <textarea name="original" rows="1" cols="1" style="display:none">$s_original</textarea>
 </div>
</form>
EOD;

//  <input type="submit" name="preview" value="$btn_preview" accesskey="p" />

//	if (isset($vars['help'])) {
//		$body .= $hr . catrule();
//	} else {
//		$body .= '<ul><li><a href="' .
//			$script . '?cmd=edit&amp;help=true&amp;page=' . $r_page .
//			'">' . $_string['help'] . '</a></li></ul>';
//	}

	if ($ajax) {
		global $head_tags;
		$head_tags[] = ' <script type="text/javascript" charset="utf-8" src="' . SKIN_URI . 'ajax/msxml.js"></script>';
//		$head_tags[] = ' <script type="text/javascript" charset="utf-8" src="' . SKIN_URI . 'ajax/textloader.js"></script>';
		$head_tags[] = ' <script type="text/javascript" charset="utf-8" src="' . SKIN_URI . 'ajax/realedit.js"></script>';
		$head_tags[] = ' <script language="javascript" type="text/javascript" src="' . SKIN_URI . 'tiny_mce/tiny_mce.js"></script>'; //TinyMCE
		$head_tags[] = ' <script language="javascript" type="text/javascript">
tinyMCE.init({
	mode : "specific_textareas", 
	editor_selector : "mceEditor"
});
</script>'; //TinyMCE
		
	}  
	return $body;
}
?>
