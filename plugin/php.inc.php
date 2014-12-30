<?php
/**
 * Evaluate text as a php code
 * 
 * Current page must be edit_authed or frozen or whole system must be PKWK_READONLY.Tag Plugin
 *
 * Example: 
 *  #php{{
 *  return 1+1;
 *  }}
 *
 * @author     sonots
 * @license    http://www.gnu.org/licenses/gpl.html GPL v2
 * @version    $Id: php.inc.php,v 1.1 2007-02-24 16:28:39Z sonots $
 * @package    plugin
 */

function plugin_php_convert()
{
	global $vars;
	$page = $vars['page'];
	if (! (PKWK_READONLY > 0 or is_freeze($page) or plugin_php_is_edit_auth($page))) {
		return "<p>php(): Current page, $page, must be edit_authed or frozen or whole system must be PKWK_READONLY.</p>";
	}

	$args   = func_get_args();
	//ob_start();
	$body = eval(array_pop($args));
	//$body = ob_get_contents();
	//ob_end_clean();    
	return $body;
}

function plugin_php_is_edit_auth($page, $user = '')
{
	global $edit_auth, $edit_auth_pages, $auth_method_type;
	if (! $edit_auth) {
		return FALSE;
	}
	// Checked by:
	$target_str = '';
	if ($auth_method_type == 'pagename') {
		$target_str = $page; // Page name
	} else if ($auth_method_type == 'contents') {
		$target_str = join('', get_source($page)); // Its contents
	}

	foreach($edit_auth_pages as $regexp => $users) {
		if (preg_match($regexp, $target_str)) {
			if ($user == '' || in_array($user, explode(',', $users))) {
				return TRUE;
			}
		}
	}
	return FALSE;
}
?>
