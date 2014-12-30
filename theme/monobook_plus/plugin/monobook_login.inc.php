<?php
/**
 * @author     lunt
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GPL 2
 * @version    $Id: monobook_login.inc.php 300 2008-01-06 01:51:59Z lunt $
 */

function plugin_monobook_login_init()
{
	$messages['_monobook_login_messages'] = array(
		'login'       => 'ログインまたはアカウント作成',
		'auth_failed' => '認証に失敗しました',
	);
	set_plugin_messages($messages);
}

function plugin_monobook_login_action()
{
	global $vars, $auth_users, $_msg_auth, $_monobook_login_messages;

	if (! isset($_SERVER['PHP_AUTH_USER']) && ! isset($_SERVER['PHP_AUTH_PW']) &&
		isset($_SERVER['HTTP_AUTHORIZATION']))
	{
		list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) =
			explode(':', base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));
	}

	if (auth::check_role('readonly') || ! isset($_SERVER['PHP_AUTH_USER']) ||
		! isset($auth_users[$_SERVER['PHP_AUTH_USER']]) || ! isset($_SERVER['PHP_AUTH_PW']) ||
		pkwk_hash_compute($_SERVER['PHP_AUTH_PW'], $auth_users[$_SERVER['PHP_AUTH_USER']]) !==
		$auth_users[$_SERVER['PHP_AUTH_USER']])
	{
			pkwk_common_headers();
			header('WWW-Authenticate: Basic realm="' . $_msg_auth . '"');
			header('HTTP/1.0 401 Unauthorized');
			$msg = $_monobook_login_messages['auth_failed'];
			return array('msg' => $msg, 'body' => '<p>' . $msg . '</p>');
	} elseif (isset($vars['refer']) && is_page($vars['refer'])) {
		header('Location: ' . get_script_uri() . '?' . rawurlencode($vars['refer']));
	}

	return;
}

function plugin_monobook_login_inline()
{
	global $vars, $_monobook_login_messages;

	if (! isset($_SERVER['PHP_AUTH_USER']) && ! isset($_SERVER['PHP_AUTH_PW']) &&
		isset($_SERVER['HTTP_AUTHORIZATION']))
	{
		list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) =
			explode(':', base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));
	}

	$auth_usr = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : '';
	$r_page   = empty($vars['page']) ? '' : '&amp;page=' . rawurlencode($vars['page']);

	$list_id    = $auth_usr ? ' id="userpage"' : ' id="login"';
	$list_class = ($auth_usr && $auth_usr === $vars['page']) ? ' class="active"' : '';
	$a_class    = ($auth_usr && ! is_page($auth_usr)) ? ' class="new"' : '';
	$title      = $auth_usr ? htmlspecialchars($auth_usr) : $_monobook_login_messages['login'];
	$uri = get_script_uri() . '?' .
		($auth_usr ? rawurlencode($auth_usr) : 'cmd=monobook_login' . $r_page);

	return '<li' . $list_id . $list_class . '><a' . $a_class . ' href="' . $uri . '">' .
		$title . '</a></li>';
}
?>
