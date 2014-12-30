<?php
//error_reporting(E_ALL);

/**
 * Html Insert Plugin
 *
 * @author     sonots
 * @license    http://www.gnu.org/licenses/gpl.html GPL v2
 * @link       http://lsx.sourceforge.jp/?Plugin%2Fhtmlinsert.inc.php
 * @version    $Id: htmlinsert.inc.php,v 1.13 2008-07-18 11:09:19Z sonots $
 * @package    plugin
 */

class PluginHtmlinsert
{
    function PluginHtmlinsert()
    {
        static $conf = array(); if (empty($conf)) $conf = array(
            'INSERT_DIR'         => DATA_HOME . 'htmlinsert',
            'INSERT_PAGE_PREFIX' => ':HTML',
            'SCRIPT_DIR'         => dirname(__FILE__) . '/htmlinsert',
            'SCRIPT_PAGE_PREFIX' => ':HTMLSCRIPT',
        );
        static $defoptions = array(
            'transitional'     => FALSE,
            'content-type' => 'text/html',
        );
        static $syntax = array(); if (empty($syntax)) $syntax = array(
            'freeze'       => '/^(?:#freeze(?!\w)\s*)+/im', // see edit.inc.php
        );
        $this->conf = &$conf;
        $this->syntax  = &$syntax;
        $this->defoptions = &$defoptions;
    }

    // static
    var $conf;
    var $syntax;
    var $defoptions;
    var $plugin = "htmlinsert";
    // var

    /**
     * Action Plugin Main Function
     */
    function action()
    {
        global $vars;
        $page = $vars['page']; unset($vars['page']);
        if (! isset($page) || $page == '') { 
            return array('msg'=>$this->plugin, 'body'=> '<p>' . $this->error_message(5) . '</p>');
        }
        $argoptions = $vars; unset($argoptions['cmd']);
        list($options, $variables) = $this->evaluate_options($argoptions, $this->defoptions);
        $source = $this->htmlinsert($page, $variables);
        if (! is_string($source)) {
            return array('msg'=>$this->plugin, 'body'=> '<p>' . $this->error_message($source) . '</p>');
        }
        // no skin
        pkwk_common_headers(); 
        if (! empty($options['content_type'])) {
            header('Content-Type: ' . htmlspecialchars($options['content_type']));
        }
        print $source;
        exit;
    }
    
    /**
     * Inline Plugin Main Function
     */
    function inline()
    {
        $args = func_get_args(); array_pop($args); // drop {}
        $page = array_shift($args);
        if (! isset($page) || $page == '') {
            return '<span>' . $this->error_message(5) . '</span>';
        }
        $argoptions = $this->parse_options($args);
        list($options, $variables) = $this->evaluate_options($argoptions, $this->defoptions);
        $source = $this->htmlinsert($page, $variables);
        if (! is_string($source)) {
            return '<span>' . $this->error_message($source) . '</span>';
        }
        if ($options['transitional']) {
            $this->html_transitional();
        }
        return $source;
    }
    
    /**
     * Block Plugin Main Function
     */
    function convert()
    {
        $args = func_get_args();
        $page = array_shift($args);
        if (! isset($page) || $page == '') {
            return '<p>' . $this->error_message(5) . '</p>';
        }
        // multiline argument options (one line is one argument)
        if (substr(end($args), -1) == "\r") {
            $multiline = array_pop($args);
        }
        if (isset($multiline)) {
            $args = array_merge($args, explode("\r", $multiline));
            array_pop($args); // always get empty element at the end
        }
        $argoptions = $this->parse_options($args);
        list($options, $variables) = $this->evaluate_options($argoptions, $this->defoptions);
        $source = $this->htmlinsert($page, $variables);
        if (! is_string($source)) {
            return '<p>' . $this->error_message($source) . '</p>';
        }
        if ($options['transitional']) {
            $this->html_transitional();
        }
        return $source;
    }
    
    /**
     * Split args of inline and convert plugin into option form
     *
     * @param array $args
     * @return array
     */
    function parse_options($args)
    {
        $argoptions = array();
        foreach($args as $arg) {
            list($key, $val) = array_pad(explode('=', $arg, 2), 2, TRUE);
            $argoptions[$key] = $val;
        }
        return $argoptions;
    }

    
    /**
     * Evaluate argumented options
     *
     * @param array $argoptions
     * @param array $defoptions
     * @return array array($options, $unknowns)
     */
    function evaluate_options($argoptions, $defoptions)
    {
        $options = $defoptions;
        $unknowns = array();
        foreach ($argoptions as $key => $val) {
            if (isset($options[$key])) {
                $options[$key] = $val;
            } else {
                $unknowns[$key] = htmlspecialchars($val);
            }
        }
        return array($options, $unknowns);
    }

    /**
     * Htmlinsert Main Function
     *
     * @param string $page page(file)name
     * @param array $variables
     * @return string|int contents or errno
     */
    function htmlinsert($page, $variables)
    {
        if(strpos($page, $this->conf['INSERT_PAGE_PREFIX'] . "/") !== FALSE) {
            $source = $this->get_wikipage($page);
        } else {
            $source = $this->get_localfile($page);
            if (! is_string($source)) {
                $tmp = $this->get_wikipage($this->conf['INSERT_PAGE_PREFIX'] . "/" . $page);
                if (is_string($tmp)) $source = $tmp; // do not want to overwrite errno
            }
        }
        if (! is_string($source)) return $source; // return error no. 
        if (count($variables) > 0) {
            $source = $this->replace_variables($source, $variables);
            if (! is_string($source)) return $source; // return error no. 
        }
        return $source;
    }

    /**
     * Get contents of a localfile
     *
     * @param string $filename
     * @return string|int contents or errno
     */
    function get_localfile($filename)
    {
        if (preg_match("#^/?\.\./#", $filename)) return 3;
        $localname = $this->conf['INSERT_DIR'] . "/" . $filename;
        if (is_readable($localname)) return file_get_contents($localname);
        $localname = $this->conf['SCRIPT_DIR'] . "/" . $filename;
        if (is_readable($localname)) return file_get_contents($localname);
        return 4;
    }

    /**
     * Get contents of a wiki page
     *
     * @param string $page
     * @return string|int contents or errno
     */
    function get_wikipage($page)
    {
        if (! is_page($page)) return 1; 
        if (! (PKWK_READONLY > 0 or $this->is_edit_auth($page) or is_freeze($page))) return 2;
        $lines = get_source($page);
        if (is_freeze($page) && preg_match($this->syntax['freeze'], $lines[0])) { // remove #freeze
            array_shift($lines);
        }
        $source = implode('', $lines);
        return $source;
    }

    /**
     * Print the error message
     *
     * @param int $errno error number
     * @param string error message
     */
    function error_message($errno)
    {
        switch ($errno) {
        case 1:
            return 'htmlinsert(): The given wiki page does not exist.';
        case 2:
            return 'htmlinsert(): The given wiki page must be edit_authed or frozen or whole system must be PKWK_READONLY.';
        case 3:
            return 'htmlinsert(): The filename should not include .. which means an upper directory.';
        case 4:
            return 'htmlinsert(): The given local file does not exist or is not readable.';
        case 5:
            return 'htmlinsert(): No page or file was given.';
        case 6:
            return 'htmlinsert(): The given page does not have the given htmlinsert variable(s).';
        }
    }

    /**
     * Replace variables in the text
     *
     * strings such as ${enc:key=defval} are replaced. 
     *
     * @param string $text 
     * @param array $variables key is the variable name and the value is the variable value
     * @return string|int replaced text or errno
     */
    function replace_variables($text, $variables)
    {
        preg_match_all('/\$\{(?:(raw|enc|utf8|euc|sjis|jis):)?([^=}]+)=([^}]*)\}/', $text, $matches, PREG_PATTERN_ORDER);
        $search = &$matches[0];
        $encs   = &$matches[1];
        $keys   = &$matches[2];
        $values = &$matches[3];
        foreach ($variables as $key => $val) {
            if (($idx = array_search($key, $keys)) !== FALSE) {
                $values[$idx] = $val;
            } else {
                return 6;
            }
        }
        foreach ($values as $idx => $value) {
            switch ($encs[$idx]) {
            case 'enc':
                $values[$idx] = rawurlencode($value);
                break;
            case 'utf8':
                $value = mb_convert_encoding($value, 'UTF-8', SOURCE_ENCODING);
                $values[$idx] = rawurlencode($value);
                break;
            case 'euc':
                $value = mb_convert_encoding($value, 'EUC', SOURCE_ENCODING);
                $values[$idx] = rawurlencode($value);
                break;
            case 'sjis':
                $value = mb_convert_encoding($value, 'SJIS', SOURCE_ENCODING);
                $values[$idx] = rawurlencode($value);
                break;
            case 'jis':
                $value = mb_convert_encoding($value, 'JIS', SOURCE_ENCODING);
                $values[$idx] = rawurlencode($value);
                break;
            case '':
            case 'raw':
            default:
                break;
            }
        }
        return str_replace($search, $values, $text);    
    }
    
    /**
     * Set PukiWiki DTD to XHTML 1.0 Transitional
     *
     * PukiWIki API Extension
     *
     * @return void
     */
    function html_transitional() 
    {
        global $pkwk_dtd; //1.4.4 or above
        global $html_transitional; //1.4.3
        $pkwk_dtd = PKWK_DTD_XHTML_1_0_TRANSITIONAL;
        $html_transitional = 1;
    }

    /**
     * Check if the page is edit_authed or not
     *
     * PukiWiki API Extension
     *
     * @param string $page
     * @param string $user
     * @return bool
     */
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

///////////////////////////////////////////
function plugin_htmlinsert_init()
{
    global $plugin_htmlinsert_name;
    if (class_exists('PluginHtmlinsertUnitTest')) {
        $plugin_htmlinsert_name = 'PluginHtmlinsertUnitTest';
    } elseif (class_exists('PluginHtmlinsertUser')) {
        $plugin_htmlinsert_name = 'PluginHtmlinsertUser';
    } else {
        $plugin_htmlinsert_name = 'PluginHtmlinsert';
    }
}
function plugin_htmlinsert_action()
{
    global $plugin_htmlinsert, $plugin_htmlinsert_name;
    $plugin_htmlinsert = new $plugin_htmlinsert_name();
    return call_user_func(array(&$plugin_htmlinsert, 'action'));
}
function plugin_htmlinsert_convert()
{
    global $plugin_htmlinsert, $plugin_htmlinsert_name;
    $plugin_htmlinsert = new $plugin_htmlinsert_name();
    $args = func_get_args();
    return call_user_func_array(array(&$plugin_htmlinsert, 'convert'), $args);
}

function plugin_htmlinsert_inline()
{
    global $plugin_htmlinsert, $plugin_htmlinsert_name;
    $plugin_htmlinsert = new $plugin_htmlinsert_name();
    $args = func_get_args();
    return call_user_func_array(array(&$plugin_htmlinsert, 'inline'), $args);
}

?>
