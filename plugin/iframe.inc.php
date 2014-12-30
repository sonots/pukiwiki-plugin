<?php
/**
 * Inline Frame Plugin
 *
 * @author     sonots
 * @license    http://www.gnu.org/licenses/gpl.html GPL v2
 * @link       http://lsx.sourceforge.jp/?Plugin%2Fiframe.inc.php
 * @version    $Id: iframe.inc.php,v 1.11 2007-06-05 07:23:17Z sonots $
 * @package    plugin
 */

class PluginIframe
{
    function PluginIframe()
    {
        static $accept_url = array(
            'http://pukiwiki.sourceforge.jp',
        );
        static $accept_regurl;
        if (! isset($accept_regurl)) $accept_regurl = array(
            '^' . preg_quote('http://www.google.com') . '$',
            '^' . preg_quote('http://pukiwiki.sourceforge.jp/dev/'),
        );
        $this->accept_regurl = & $accept_regurl;
        $this->accept_url    = & $accept_url;
    }
    
    var $accept_regurl;
    var $accept_url;
    var $plugin = 'iframe';

    function convert()
    {
        if (func_num_args() == 0) { return '<p>$this->plugin(): no argument(s). </p>'; }
        global $vars;

        $args = func_get_args();
        $url  = array_shift($args);
        if (! is_url($url) && is_interwiki($url)) {
            list($interwiki, $page) = explode(':', $url, 2);
            $url = get_interwiki_url($interwiki, $page);
        }
        $page = $vars['page'];
        if (! (PKWK_READONLY > 0 or is_freeze($page) or $this->is_edit_auth($page))) {
            if (! $this->accept($url)) {
                return "<p>$this->plugin(): The specified url, $url, is not allowed, modify iframe.inc.php<br />" .
                    "Or, restrict editing of current page using freeze or edit_auth or PKWK_READONLY.</p>";
            }
        }
        $url = htmlspecialchars($url); 

        $options = array();
        foreach ($args as $arg) {
            list($key, $val) = array_pad(explode('=', $arg, 2), 2, TRUE);
            $options[$key] = htmlspecialchars($val);
        }

        $style = isset($options['style']) ? $options['style'] : NULL;
        if (preg_match("/MSIE (3|4|5|6|7)/", getenv("HTTP_USER_AGENT"))) {
            $style = isset($options['iestyle']) ? $options['iestyle'] : $style;
            return $this->show_iframe($url, $style);
        } else {
            return $this->show_object($url, $style);
        }
    }

    function show_iframe($url, $style)
    {
        global $pkwk_dtd; //1.4.4 or above
        global $html_transitional; //1.4.3
        $pkwk_dtd = PKWK_DTD_XHTML_1_0_TRANSITIONAL;
        $html_transitional = 1;
    
        $ret  = '<iframe frameborder="0" class="iframe"';
        $ret .= isset($style) ? ' style="' . $style . '"' : '';
        $ret .= ' src="' . $url . '">';
        $ret .= '<p>Your borwser is not supporting iframe tag. ' . 
            'Please use one of the latest browsers.<br />' .
            'Go to <a href="' . $url . '">' . $url . '</a></p>';
        $ret .= '</iframe>';
        return $ret;
    }

    function show_object($url, $style)
    {
        $ret  = '<object class="iframe" type="text/html"';
        $ret .= isset($style) ? ' style="' . $style . '"' : '';
        $ret .= ' data="' . $url . '">';
        $ret .= '<p>Your borwser is not supporting object tag. ' . 
            'Please use one of the latest browsers.<br />' .
            'Go to <a href="' . $url . '">' . $url . '</a></p>';
        $ret .= '</object>';
        return $ret;
    }

    function accept($url)
    {	
        foreach ($this->accept_url as $val) {
            if ($val == $url) { return TRUE; }
        }
        foreach ($this->accept_regurl as $val) {
            if (preg_match('/' . str_replace('/', '\/', $val) . '/', $url)) { return TRUE; }
        }
        return FALSE;
    }

    function is_edit_auth($page, $user = '')
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
}

/////////////////////////////////////////////////
function plugin_iframe_common_init()
{
    global $plugin_iframe;
    if (class_exists('PluginIframeUnitTest')) {
        $plugin_iframe = new PluginIframeUnitTest();
    } elseif (class_exists('PluginIframeUser')) {
        $plugin_iframe = new PluginIframeUser();
    } else {
        $plugin_iframe = new PluginIframe();
    }
}

function plugin_iframe_convert()
{
    global $plugin_iframe; plugin_iframe_common_init();
    $args = func_get_args();
    return call_user_func_array(array(&$plugin_iframe, 'convert'), $args);
}

if (! defined('INIT_DIR')) // if not Plus! 
    if (file_exists(DATA_HOME . 'init/iframe.ini.php')) 
        include_once(DATA_HOME . 'init/iframe.ini.php');

?>
