<?php
/**
 * Comment Plugin eXtension
 *
 *  Copyright (C)
 *   2007-2007 sonots + PukiWiki Plus! Team
 *   2005-2007 PukiWiki Plus! Team
 *   2002-2005 PukiWiki Developers Team
 *   2001-2002 Originally written by yu-ji
 *
 * @author     sonots
 * @license    GPL v2 or (at your option) any later version
 * @version    $Id: commentx.inc.php,v 1.1 2007-06-10 11:14:46Z sonots $
 *             based on PukiWiki Plus! comment.inc.php,v 1.36.18
 * @package    plugin
 */

// ----
defined('PLUGIN_COMMENTX_DIRECTION_DEFAULT') or define('PLUGIN_COMMENTX_DIRECTION_DEFAULT', '1'); // 1: above 0: below
// Form size for textfield
defined('PLUGIN_COMMENTX_SIZE_MSG') or define('PLUGIN_COMMENTX_SIZE_MSG', '60%');
defined('PLUGIN_COMMENTX_SIZE_NAME') or define('PLUGIN_COMMENTX_SIZE_NAME', 15);
// Form size for textarea
defined('PLUGIN_COMMENTX_SIZE_TEXTAREA_COLS') or define('PLUGIN_COMMENTX_SIZE_TEXTAREA_COLS', '80%');
defined('PLUGIN_COMMENTX_SIZE_TEXTAREA_ROWS') or define('PLUGIN_COMMENTX_SIZE_TEXTAREA_ROWS', 3);
defined('PLUGIN_COMMENTX_SIZE_TEXTAREA_NAME') or define('PLUGIN_COMMENTX_SIZE_TEXTAREA_NAME', 20);
// The default value of option 'textarea' (Use textarea) or 'textfield' (Use textfield)
defined('PLUGIN_COMMENTX_TEXTAREA') or define('PLUGIN_COMMENTX_TEXTAREA', TRUE);

// ----
define('PLUGIN_COMMENTX_FORMAT_MSG',  '$msg');
define('PLUGIN_COMMENTX_FORMAT_NAME', '[[$name]]');
define('PLUGIN_COMMENTX_FORMAT_NOW',  '&new{$now};');
define('PLUGIN_COMMENTX_FORMAT_STRING', "\x08MSG\x08 -- \x08NAME\x08 \x08NOW\x08");

// Convert linebreaks into pukiwiki's linebreaks. Effective if textarea is used. 
defined('PLUGIN_COMMENTX_LINE_BREAK') or define('PLUGIN_COMMENTX_LINE_BREAK', FALSE);

// NGWORD by a regular expression
define('PLUGIN_COMMENTX_NGWORD') or define('PLUGIN_COMMENTX_NGWORD', '');

function plugin_commentx_action()
{
	global $vars, $post;

	// Petit SPAM Check (Client(Browser)-Server Ticket Check)
	$spam = FALSE;
	if (isset($post['encode_hint']) && $post['encode_hint'] != '') {
		if (PKWK_ENCODING_HINT != $post['encode_hint']) $spam = TRUE;
	} else {
		if (PKWK_ENCODING_HINT != '') $spam = TRUE;
	}

    if (method_exists('auth', 'check_role')) { // Plus! 
        if (auth::check_role('readonly')) die_message('PKWK_READONLY prohibits editing');

        if (!is_page($vars['refer']) && auth::is_check_role(PKWK_CREATE_PAGE)) {
            die_message( _('PKWK_CREATE_PAGE prohibits editing') );
        }
    } else {
        if (PKWK_READONLY) die_message('PKWK_READONLY prohibits editing');
    }

	// If SPAM, goto jail.
	if ($spam) return plugin_commentx_honeypot();
	return plugin_commentx_write();
}

function plugin_commentx_write()
{
	global $script, $vars, $now;
	global $_no_name;
//	global $_msg_comment_collided, $_title_comment_collided, $_title_updated;
	$_title_updated = _("$1 was updated");
	$_title_comment_collided = _("On updating  $1, a collision has occurred.");
	$_msg_comment_collided   = _("It seems that someone has already updated the page you were editing.<br />") .
	                           _("The comment was added, alhough it may be inserted in the wrong position.<br />");

	if (! isset($vars['msg'])) return array('msg'=>'', 'body'=>''); // Do nothing
	if (preg_match(PLUGIN_COMMENTX_NGWORD, $vars['msg'])) return array('msg'=>'', 'body'=>'');

	// Validate
	if (is_spampost(array('msg')))
		return plugin_commentx_honeypot();

	$vars['msg'] = preg_replace('/\s+$/', "", $vars['msg']); // Cut last LF
    if (PLUGIN_COMMENTX_LINE_BREAK) {
        // Convert linebreaks into pukiwiki's linebreaks &br;
        $vars['msg'] = str_replace("\n", "&br;\n", $vars['msg']);
    } else {
        // Replace empty lines into #br
        $vars['msg'] = preg_replace('/^\s*\n/m', "#br\n", $vars['msg']); 
    }

	$head = '';
	$match = array();
	if (preg_match('/^(-{1,2})-*\s*(.*)/', $vars['msg'], $match)) {
		$head        = & $match[1];
		$vars['msg'] = & $match[2];
	}
	if ($vars['msg'] == '') return array('msg'=>'', 'body'=>''); // Do nothing

	$comment  = str_replace('$msg', $vars['msg'], PLUGIN_COMMENTX_FORMAT_MSG);

	list($nick, $vars['name'], $disabled) = plugin_commentx_get_nick();

	if(isset($vars['name']) || ($vars['nodate'] != '1')) {
		$_name = (! isset($vars['name']) || $vars['name'] == '') ? $_no_name : $vars['name'];
		$_name = ($_name == '') ? '' : str_replace('$name', $_name, PLUGIN_COMMENTX_FORMAT_NAME);
		$_now  = ($vars['nodate'] == '1') ? '' :
			str_replace('$now', $now, PLUGIN_COMMENTX_FORMAT_NOW);
		$comment = str_replace("\x08MSG\x08",  $comment, PLUGIN_COMMENTX_FORMAT_STRING);
		$comment = str_replace("\x08NAME\x08", $_name, $comment);
		$comment = str_replace("\x08NOW\x08",  $_now,  $comment);
	}
	$comment = '-' . $head . ' ' . $comment;

	$postdata    = '';
	$comment_no  = 0;
	$above       = (isset($vars['above']) && $vars['above'] == '1');
	foreach (get_source($vars['refer']) as $line) {
		if (! $above) $postdata .= $line;
		if (preg_match('/^#commentx/i', $line) && $comment_no++ == $vars['comment_no']) {
			if ($above) {
				$postdata = rtrim($postdata) . "\n" .
					$comment . "\n" .
					"\n";  // Insert one blank line above #commment, to avoid indentation
			} else {
				$postdata = rtrim($postdata) . "\n" .
					$comment . "\n"; // Insert one blank line below #commment
			}
		}
		if ($above) $postdata .= $line;
	}

	$title = $_title_updated;
	$body = '';
	if (md5(@join('', get_source($vars['refer']))) != $vars['digest']) {
		$title = $_title_comment_collided;
		$body  = $_msg_comment_collided . make_pagelink($vars['refer']);
	}

	page_write($vars['refer'], $postdata);

	$retvars['msg']  = $title;
	$retvars['body'] = $body;

	if ($vars['refpage']) {
		header("Location: $script?".rawurlencode($vars['refpage']));
		exit;
	}

	$vars['page'] = $vars['refer'];

	return $retvars;
}

function plugin_commentx_get_nick()
{
	global $vars;

	$name = (empty($vars['name'])) ? '' : $vars['name'];
	if (PKWK_READONLY != ROLE_AUTH) return array($name,$name,'');

	list($role,$name,$nick,$url) = auth::get_user_name();
	if (empty($nick)) return array($name,$name,'');
	if (auth::get_role_level() < ROLE_AUTH) return array($name,$name,'');
	$link = (empty($url)) ? $nick : $nick.'>'.$url;
	return array($nick, $link, "disabled=\"disabled\"");
}

// Cancel (Back to the page / Escape edit page)
function plugin_commentx_honeypot()
{
	// Logging for SPAM Report
	honeypot_write();

	// Same as "Cancel" action
	return array('msg'=>'', 'body'=>''); // Do nothing
}

function plugin_commentx_convert()
{
	global $vars, $digest;	//, $_btn_comment, $_btn_name, $_msg_comment;
	static $numbers = array();
	static $all_numbers = 0;

	$_btn_name    = _("Name: ");
	$_btn_comment = _("Post Comment");
	$_msg_comment = _("Comment: ");

	$auth_guide = '';
	if (PKWK_READONLY == ROLE_AUTH) { // Plus! 
		if (exist_plugin('login')) {
            $auth_guide = do_plugin_inline('login');
        }
	}

    if (is_callable(array('auth', 'check_role'))) { // Plus! 
        if (auth::check_role('readonly')) return $auth_guide;
    } else {
        if (PKWK_READONLY) return '';
    }
	if (! isset($numbers[$vars['page']])) $numbers[$vars['page']] = 0;
	$comment_no = $numbers[$vars['page']]++;
	$comment_all_no = $all_numbers++;

	$options = func_num_args() ? func_get_args() : array();
    $noname = in_array('noname', $options);
	$nodate = in_array('nodate', $options) ? '1' : '0';
	$above  = in_array('above',  $options) ? '1' :
		(in_array('below', $options) ? '0' : PLUGIN_COMMENTX_DIRECTION_DEFAULT);
    $textarea = in_array('textarea', $options) ? TRUE :
        (in_array('textfield', $options) ? FALSE : PLUGIN_COMMENTX_TEXTAREA);
    
	list($user, $link, $disabled) = plugin_commentx_get_nick();
    
	if ($noname) {
        $nametags = '<label for="_p_comment_comment_' . $comment_all_no . '">' .
			$_msg_comment . '</label>';
    } else {
        if ($textarea) {
            $nametags = '<label for="_p_comment_name_' . $comment_all_no . '">' .
                $_btn_name . '</label>' .
                '<input type="text" name="name" id="_p_comment_name_' .
                $comment_all_no .  '" size="' . PLUGIN_COMMENTX_SIZE_TEXTAREA_NAME .
                '" value="'.$user.'"'.$disabled.' /><br />' . "\n";
        } else {
            $nametags = '<label for="_p_comment_name_' . $comment_all_no . '">' .
                $_btn_name . '</label>' .
                '<input type="text" name="name" id="_p_comment_name_' .
                $comment_all_no .  '" size="' . PLUGIN_COMMENTX_SIZE_NAME .
                '" value="'.$user.'"'.$disabled.' />' . "\n";
        }
	}
    if ($textarea) {
		$comment_box = '<textarea name="msg" id="_p_comment_comment_{' . $comment_all_no . '}" rows="' . PLUGIN_COMMENTX_SIZE_TEXTAREA_ROWS . '" style="width:' . PLUGIN_COMMENTX_SIZE_TEXTAREA_COLS . ';" /></textarea>';
    } else {
		$comment_box = '<input type="text"   name="msg" id="_p_comment_comment_{' . $comment_all_no . '}" style="width:' . PLUGIN_COMMENTX_SIZE_MSG . ';" />';
    }

    if (function_exists('edit_form_assistant')) { // Plus!
        $helptags = edit_form_assistant();
    }
	$refpage = '';

	$script = get_script_uri();
	$s_page = htmlspecialchars($vars['page']);
    $r_page = htmlspecialchars(rawurlencode($vars['page']));

	$ticket = md5(MUTIME);
	if (function_exists('pkwk_session_start') && pkwk_session_start() != 0) {
		$keyword = $ticket;
		$_SESSION[$keyword] = md5(get_ticket() . $digest);
	}

	$string = <<<EOD
<br />
$auth_guide
<form action="$script?$r_page" method="post">
 <div class="commentform" onmouseup="pukiwiki_pos()" onkeyup="pukiwiki_pos()">
  <input type="hidden" name="refpage" value="$refpage" />
  <input type="hidden" name="plugin" value="commentx" />
  <input type="hidden" name="refer"  value="$s_page" />
  <input type="hidden" name="comment_no" value="$comment_no" />
  <input type="hidden" name="nodate" value="$nodate" />
  <input type="hidden" name="above"  value="$above" />
  <input type="hidden" name="digest" value="$digest" />
  <input type="hidden" name="ticket" value="$ticket" />
  $nametags
  $comment_box
  <input type="submit" name="comment" value="$_btn_comment" />
  $helptags
 </div>
</form>
EOD;

	return $string;
}


if (! function_exists('_')) {
    function &_($str)
    {
        return $str;
    }
}
        
?>
