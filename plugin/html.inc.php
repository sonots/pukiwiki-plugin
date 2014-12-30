<?php
/**
 * Write HTML
 *
 * @author     sonots
 * @license    http://www.gnu.org/licenses/gpl.html GPL v2
 * @link       http://lsx.sourceforge.jp/?Plugin%2Fhtml.inc.php
 * @version    $Id: html.inc.php,v 2.2 2007-03-20 23:44:19Z sonots $
 * @package    plugin
 */

function plugin_html_convert()
{
    $args = func_get_args();
    $body = array_pop($args);
    if (substr($body, -1) != "\r") {
        return '<p>html(): no argument(s).</p>';
    }
    $page = $GLOBALS['vars']['page'];
    if (! plugin_html_is_edit_restricted($page)) {
        return "<p>html(): Current page, $page, must be edit_authed or frozen or whole system must be PKWK_READONLY.</p>";
    }

    $noskin = in_array("noskin", $args);
    if ($noskin) {
        pkwk_common_headers();
        print $body;
        exit;
    }
    return $body;
}

function plugin_html_is_edit_restricted($page)
{
    return PKWK_READONLY > 0 or 
        is_freeze($page) or
        //in_array($page, $GLOBALS['cantedit']) or
        plugin_html_is_edit_auth($page);
}

function plugin_html_is_edit_auth($page, $user = '')
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
