<?php
/**
 * Markdon Syntax
 *
 * @author     sonots
 * @license    http://www.gnu.org/licenses/gpl.html GPL v2
 * @link       http://lsx.sourceforge.jp/?Plugin%2Fmarkdown.inc.php
 * @version    $Id: markdown.inc.php,v 1.2 2007-02-24 16:28:39Z sonots $
 * @package    plugin
 */

function plugin_markdown_convert()
{
    if (defined('PLUGIN_DIR') && file_exists(PLUGIN_DIR . 'markdown.php')) {
        $markdown = PLUGIN_DIR . 'markdown.php';
    } elseif (defined('EXT_PLUGIN_DIR') && file_exists(EXT_PLUGIN_DIR . 'markdown.php')) {
        $markdown = EXT_PLUGIN_DIR . 'markdown.php';
    } else {
        return "markdown(): markdown.php does not exist under " . PLUGIN_DIR . ' or ' . EXT_PLUGIN_DIR;
    }

    $args = func_get_args();
    $body = array_pop($args);
    $noskin = in_array("noskin", $args);
    global $vars;
    if (! (PKWK_READONLY > 0 or is_freeze($vars['page']) or plugin_markdown_is_edit_auth($vars['page']))) {
        $body = htmlspecialchars($body);
    }
    require_once($markdown);
    $body = Markdown($body);

    if ($noskin) {
        pkwk_common_headers();
        print $body;
        exit;
    }
    return $body;
}

function plugin_markdown_is_edit_auth($page, $user = '')
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
