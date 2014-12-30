<?php
require_once(dirname(__FILE__) . '/Compat.php');
require_once(dirname(__FILE__) . '/Compat/Function/str_split.php');
require_once(dirname(__FILE__) . '/Compat/Function/array_diff_key.php');
require_once(dirname(__FILE__) . '/Compat/Function/array_fill.php');
require_once(dirname(__FILE__) . '/Compat/Function/array_intersect_key.php');
require_once(dirname(__FILE__) . '/Compat/Function/array_slice.php');
require_once(dirname(__FILE__) . '/Compat/Function/file_get_contents.php');
require_once(dirname(__FILE__) . '/Compat/Function/file_put_contents.php');
require_once(dirname(__FILE__) . '/Compat/Function/mkdir.php');

/**
 * Namespace sonots (sonots' additional functions)
 *
 * @package    PluginSonots
 * @license    http://www.gnu.org/licenses/gpl.html GPL v2
 * @author     sonots <http://lsx.sourceforge.jp>
 * @link       http://lsx.sourceforge.jp/
 * @version    $Id: sonots.class.php,v 1.15 2008-08-15 11:14:46 sonots $
 * @uses       PHP_Compat rev 1.22
 */
class sonots
{
    ////////////// PukiWiki API Extension /////////
    /**
     * Compact List Levels
     *
     * PukiWiki API Extension
     *
     * Example)
     * <code>
     *  $levels = array(1,3,1,1,3,2,3);
     *  print_r(sonots::compact_list($levels));
     *  // array(1,2,1,1,2,2,3)
     *
     *  $levels = array(1,3,1,1,3,3,3);
     *  print_r(sonot::compact_list($levels));
     *  // array(1,2,1,1,2,2,2) // not array(1,2,1,1,2,3,3)
     * </code>
     *
     * @access public
     * @static
     * @param array $levels array of levels (positive numbers)
     * @return array compacted levels (keys, sequences are preserved)
     * @version $Id: v 1.0 2008-06-05 11:14:46 sonots $
     */
    function compact_list($levels)
    {
        // 1) simply fill in non-existing level
        // 1 3 1 1 3 3 1 => 1 2 1 1 2 2 1 (move 3 to 2 coz 2 was none)
        // 2 2 2 => 1 1 1

        // 1.1) unique sort
        // 3 1 1 3 3 1 => (uniq) 3 1 => (sort) 1 3
        $uniq = array_unique($levels);
        sort($uniq);
        // 1.2) construct mapper
        // 1 3 => 1 2, that is, 1 goes to 1, 3 goes to 2
        // which can be expressed as $mapper (1=>1,3=>2), to construct this, 
        // 1 3 == (0=>1,1=>3) => (exchange key/val) (1=>0,3=>1) 
        // => (add 1) (1=>1,3=>2)
        $mapper = array_flip($uniq);
        $mapper = array_map(create_function('$x', 'return $x+1;'), $mapper);
        // 1.3) mapping
        foreach ($levels as $i => $level) {
            $levels[$i] = $mapper[$level];
        }

        // 2) fill previous space
        // 1 3 2 => 1 2 2
        $prev = 0;
        foreach ($levels as $i => $level) {
            if ($level >= $prev + 2) {
                $levels[$i] = $prev + 1;
            }
            $prev = $levels[$i];
        }
        return $levels;
    }

    /**
     * Get result html of convert_html
     *
     * PukiWiki API Extension
     *
     * @access public
     * @static
     * @param string $page pagename
     * @param array $lines contents to be converted
     * @return string html
     * @version $Id: v 1.0 2008-06-05 11:14:46 sonots $
     */
    function get_convert_html($page, $lines)
    {
        global $vars, $get, $post;
        $tmp = $vars['page'];
        $get['page'] = $post['page'] = $vars['page'] = $page;
        if (function_exists('convert_filter')) { // plus
            $lines = convert_filter($lines); 
        }
        $html = convert_html($lines);
        $get['page'] = $post['page'] = $vars['page'] = $tmp;
        return $html;
    }

    /**
     * PukiWiki typical password form
     *
     * PukiWiki API Extension
     *
     * @access public
     * @static
     * @param string $action action url (htmlspecialchars will be performed)
     * @param string $message additional message (htmlspecialchars will be performed)
     * @param boolean $use_session use session log to know he is admin
     * @param boolean $use_authlog use Basic Auth log to know he is admin
     *   if he is admin, form does not show password textbox. 
     * @return string form html
     * @version $Id: v 1.0 2008-06-05 11:14:46 sonots $
     */
    function display_password_form($action, $message = "", $use_session = true, $use_authlog = true)
    {
        if ($message != '') {
            $message = '<p><b>' . htmlspecialchars($message) . '</b></p>';
        }

        $form = array();
        $form[] = '<form action="' . htmlspecialchars($action) . '" method="post">';
        $form[] = '<div>';
        if (! sonots::is_admin(null, $use_session, $use_authlog)) {
            $form[] = ' <input type="password" name="pass" size="24" value="" /> ' . _('Admin Password') . '<br />';
        }
        $form[] = ' <input type="submit" name="submit" value="Submit" /><br />';
        $form[] = '</div>';
        $form[] = '</form>';
        $form = implode("\n", $form);

        return $message . $form;
    }
    
    /**
     * PukiWiki simple option parser
     *
     * PukiWiki API Extension
     *
     * Example)
     * <code>
     *  $args = array('str=hoge','bool');
     *  $options = sonots::parse_options($args);
     *  var_export($options);
     *  // array('str'=>'hoge','bool'=>true);
     * 
     *  $conf_options = array('str'=>'foobar','bool'=>false); // default
     *  $args = array('bool','unknown=hoge');
     *  $options = sonots::parse_options($args, $conf_options);
     *  var_export($options);
     *  // array('str'=>'foobar','bool'=>true); // unknown is not set
     * </code>
     *
     * @access public
     * @static
     * @param array $args
     * @param array $conf_options
     * @param boolean $trim trim option key/val
     * @param string $sep key/val separator
     * @return array options
     * @version $Id: v 1.1 2008-06-05 11:14:46 sonots $
     */
    function parse_options($args, $conf_options = array(), $trim = false, $sep = '=')
    {
        $options = $conf_options;
        if (empty($conf_options)) {
            foreach ($args as $arg) {
                list($key, $val) = array_pad(explode($sep, $arg, 2), 2, true);
                $options[$key] = $val;
            }
        } else { // check option keys
            foreach ($args as $arg) {
                list($key, $val) = array_pad(explode($sep, $arg, 2), 2, true);
                if (array_key_exists($key, $conf_options)) {
                    $options[$key] = $val;
                }
            }
        }
        if ($trim) {
            $options = sonots::trim_array($options, true, true);
        }
        return $options;
    }

    /**
     * Get tree states of pages
     *
     * PukiWiki API Extension
     *
     * @access public
     * @static
     * @param array $pages
     * @return array $leafs 
     *   array whose keys are pagenames and values are boolean
     *   true if the page is a leaf in tree, false if not. 
     * @uses sonots::sort_filenames
     * @version $Id: v 1.0 2008-06-05 11:14:46 sonots $
     */
    function get_tree($pages)
    {
        sonots::sort_filenames($pages);
        $currpage = current($pages);
        $leafs = array();
        while ($nextpage = next($pages)) {
            if (strpos($nextpage, $currpage . '/') === false) {
                $leafs[$currpage] = true;
            } else {
                $leafs[$currpage] = false;
            }
            $currpage = $nextpage;
        }
        $leafs[$currpage] = true;
        return $leafs;
    }

    /**
     * Convert only inline plugins unlike make_link()
     *
     * PukiWiki API Extension
     *
     * This (Precisely, InlineConverter) does htmlspecialchars, too.
     *
     * @access public
     * @static
     * @param $string string
     * @param $page pagename, default is $vars['page']
     * @uses InlineConverter (PukiWiki lib/make_link.php)
     * @see make_link (PukiWiki lib/make_link.php)
     * @version $Id: v 1.0 2008-06-05 11:14:46 sonots $
     */
    function make_inline($string, $page = '')
    {
        global $vars;
        static $converter;
        
        if (! isset($converter)) $converter = new InlineConverter(array('plugin'));
        
        $clone = $converter->get_clone($converter);
        return $clone->convert($string, ($page != '') ? $page : $vars['page']);
    }

    /**
     * Remove inside of multiline plugin arguments. 
     * Keys are preserved. 
     *
     * PukiWiki API Extension
     *
     * @access public
     * @static
     * @param array $lines 
     * @return void
     * @version $Id: v 1.0 2008-06-05 11:14:46 sonots $
     */
    function remove_multilineplugin_lines(&$lines)
    {
        if(! (defined('PKWKEXP_DISABLE_MULTILINE_PLUGIN_HACK') && 
              PKWKEXP_DISABLE_MULTILINE_PLUGIN_HACK === 0)) {
            return $lines;
        }
        $multiline = 0;
        foreach ($lines as $i => $line) {
            $matches = array();
            if ($multiline < 2) {
                if(preg_match('/^#([^\(\{]+)(?:\(([^\r]*)\))?(\{*)/', $line, $matches)) {
                    $multiline  = strlen($matches[3]);
                }
            } else {
                if (preg_match('/^\}{' . $multiline . '}$/', $line, $matches)) {
                    $multiline = 0;
                }
                unset($lines[$i]);
                continue;
            }
        }
    }

    /**
     * Check if string is InterWiki syntax or not
     *
     * PukiWiki API Extension
     *
     * @access public
     * @static
     * @param string $str
     * @return boolean
     * @uses is_url (PukiWiki lib/func.php)
     * @uses is_interwiki (PukiWiki lib/func.php)
     * @version $Id: v 1.0 2008-06-05 11:14:46 sonots $
     */
    function is_interwiki($str)
    {
        return ! is_url($str) && is_interwiki($str);
    }

    /**
     * Resolve InterWiki name
     *
     * PukiWiki API Extension
     *
     * @access public
     * @static
     * @param string $interwiki InterWiki string such as pukiwiki:PageName
     * @return string url
     * @uses is_url (PukiWiki lib/func.php)
     * @uses is_interwiki (PukiWiki lib/func.php)
     * @uses get_interwiki_url (PukiWiki lib/func.php)
     * @version $Id: v 1.0 2008-06-05 11:14:46 sonots $
     */
    function get_interwiki_url($interwiki)
    {
        if (is_url($interwiki) || ! is_interwiki($interwiki)) return false;
        list($interwiki, $page) = explode(':', $interwiki, 2);
        $url = get_interwiki_url($interwiki, $page);
        return $url;
    }

    /**
     * Output contents without skin
     *
     * PukiWiki API Extension
     *
     * @access public
     * @static
     * @param string $body html
     * @param string $content_type e.g., 'text/html', 'text/css', 'text/javascript'
     * @return void exit
     * @uses pkwk_common_headers (PukiWiki lib/html.php)
     * @version $Id: v 1.0 2008-06-05 11:14:46 sonots $
     */
    function pkwk_output_noskin($body, $content_type = 'text/html') // text/css, text/javascript
    {
        pkwk_common_headers();
        header('Content-Type: ' . $content_type);
        print $body;
        exit;
    }

    /**
     * get uri of a page
     *
     * PukiWiki API Extension
     *
     * @access public
     * @static
     * @param string $page
     * @param string $query query word if needs
     * @return string uri
     * @version $Id: v 1.0 2008-06-05 11:14:46 sonots $
     */
    function get_page_uri($page, $query = '')
    {
        if (function_exists('get_script_uri')) { // from pukiwiki 1.4
            $url = get_script_uri() . '?' . rawurlencode($page);
        } else {
            global $script;
            $url = $script . '?' . rawurlencode($page);
        }
        if ($query != '') {
            $url .= '&amp;' . $query;
        }
        return $url;
    }

    /**
     * get existing pages with prefix restriction
     *
     * PukiWiki API Extension
     *
     * @access public
     * @static
     * @param string $prefix
     * @return array
     * @uses get_existpages() (PukiWiki lib/file.php)
     * @version $Id: v 1.0 2008-06-05 11:14:46 sonots $
     */
    function get_existpages($prefix = '')
    {
        $pages = get_existpages();
        if ($prefix === '') return $pages;
        foreach ($pages as $i => $page) {
            if (strpos($page, $prefix) !== 0) {
                unset($pages[$i]);
            }
        }
        return $pages;
    }

    /**
     * Check if a page is configured to require the read-authentication
     *
     * PukiWiki API Extension
     *
     * @access public
     * @static
     * @global boolean read_auth
     * @global array read_auth_pages
     * @global string auth_method_type
     * @param $page pagename
     * @param $user if want to check whether this user possibly can get permission
     * @return boolean
     * @version $Id: v 1.0 2008-06-05 11:14:46 sonots $
     */
    function is_read_auth($page, $user = '')
    {
        global $read_auth, $read_auth_pages, $auth_method_type;
        if (! $read_auth) {
            return false;
        }
        // Checked by:
        $target_str = '';
        if ($auth_method_type == 'pagename') {
            $target_str = $page; // Page name
        } else if ($auth_method_type == 'contents') {
            $target_str = join('', get_source($page)); // Its contents
        }
       
        foreach($read_auth_pages as $regexp => $users) {
            if (preg_match($regexp, $target_str)) {
                if ($user == '' || in_array($user, explode(',', $users))) {
                    return true;
                }
            }
        }
        return false;
    }
    /**
     * Check if a page is configured to require authentication
     *
     * PukiWiki API Extension
     *
     * @access public
     * @static
     * @global boolean edit_auth
     * @global array edit_auth_pages
     * @global string auth_method_type
     * @param string $page
     * @param string $user if want to check whether this user possibly can get permission
     * @return boolean
     * @version $Id: v 1.0 2008-06-05 11:14:46 sonots $
     */
    function is_edit_auth($page, $user = '')
    {
        global $edit_auth, $edit_auth_pages, $auth_method_type;
        if (! $edit_auth) {
            return false;
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
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Check if a page is restricted to edit or not
     *
     * PukiWiki API Extension
     *
     * @access public
     * @static
     * @param string $page
     * @return boolean
     * @uses sonots::is_edit_auth
     * @uses is_freeze (PukiWiki lib/func.php)
     * @version $Id: v 1.0 2008-06-05 11:14:46 sonots $
     */
    function is_edit_restrict($page)
    {
        return PKWK_READONLY > 0 or is_freeze($page) or sonots::is_edit_auth($page);
    }

    /**
     * Execute (convert_html) all pages
     *
     * PukiWiki API Extension
     *
     * @access public
     * @static
     * @param string $prefix restrict pages by prefix condition
     * @param string $regexp execute only matched lines (preg_grep)
     * @return array executed pages
     * @uses sonots::get_existpages
     * @uses get_source (PukiWiki lib/file.php)
     * @uses convert_html (PukiWiki lib/convert_html.php)
     * @version $Id: v 1.0 2008-06-05 11:14:46 sonots $
     */
    function exec_existpages($prefix = '', $regexp = null)
    {
        global $vars, $get, $post;
        $pages = sonots::get_existpages($prefix);
        $exec_pages = array();
        $tmp_page = $vars['page'];
        $tmp_cmd  = $vars['cmd'];
        $vars['cmd'] = $get['cmd'] = $post['cmd'] = 'read';
        foreach ($pages as $page) {
            $vars['page'] = $get['page'] = $post['page'] = $page;
            $lines = get_source($page);
            if (isset($regexp)) {
                $lines = preg_grep($regexp, $lines);
            }
            if (empty($lines)) continue;
            convert_html($lines);
            $exec_pages[] = $page;
        }
        $vars['page'] = $get['page'] = $post['page'] = $tmp_page;
        $vars['cmd'] = $get['cmd'] = $post['cmd'] = $tmp_cmd;
        return $exec_pages;
    }

    /**
     * Execute (convert_html) this page
     *
     * PukiWiki API Extension
     *
     * @access public
     * @static
     * @param string $page
     * @param string $regexp execute only matched lines (preg_grep)
     * @return boolean executed or not
     * @uses get_source (PukiWiki lib/file.php)
     * @uses convert_html (PukiWiki lib/convert_html.php)
     * @version $Id: v 1.0 2008-06-05 11:14:46 sonots $
     */
    function exec_page($page, $regexp = null)
    {
        global $vars, $get, $post;
        $lines = get_source($page);
        if (isset($regexp)) {
            $lines = preg_grep($regexp, $lines);
        }
        if (empty($lines)) return false;
        $tmp_page = $vars['page'];
        $tmp_cmd  = $vars['cmd'];
        $vars['cmd'] = $get['cmd'] = $post['cmd'] = 'read';
        $vars['page'] = $get['page'] = $post['page'] = $page;
        convert_html($lines);
        $vars['page'] = $get['page'] = $post['page'] = $tmp_page;
        $vars['cmd'] = $get['cmd'] = $post['cmd'] = $tmp_cmd;
        return true;
    }

    /**
     * Human recognition using PukiWiki Auth methods
     *
     * PukiWiki API Extension
     *
     * @access public
     * @static
     * @param boolean $is_human Tell this is a human (Use true to store into session)
     * @param boolean $use_session Use Session log
     * @param int $use_rolelevel accepts users whose role levels are stronger than this
     * @return boolean
     * @uses sonots::is_admin
     * @uses auth::check_role (PukiWiki Plus! lib/auth.php) if available
     * @version $Id: v 1.0 2008-06-05 11:14:46 sonots $
     */
    function is_human($is_human = false, $use_session = false, $use_rolelevel = 0)
    {
        if (! $is_human) {
            if ($use_session) {
                session_start();
                $is_human = isset($_SESSION['pkwk_is_human']) && $_SESSION['pkwk_is_human'];
            }
        }
        if (! $is_human) {
            if (ROLE_GUEST < $use_rolelevel && $use_rolelevel <= ROLE_ENROLLEE) {
                if (is_callable(array('auth', 'check_role'))) { // Plus!
                    $is_human = ! auth::check_role('role_enrollee');
                } else { // In PukiWiki Official, enrollees are all auth_users (ROLE_AUTH && BasicAuth in Plus!)
                    $is_human = isset($_SERVER['PHP_AUTH_USER']);
                }
            }
        }
        if (! $is_human) {
            if (ROLE_GUEST < $use_rolelevel && $use_rolelevel <= ROLE_ADM_CONTENTS) {
                $is_human = sonots::is_admin(null, $use_session, true);
                // In PukiWiki Official, username 'admin' is the Admin
            }
        }
        if ($use_session) {
            session_start();
            $_SESSION['pkwk_is_human'] = $is_human;
        } else {
            global $vars;
            $vars['pkwk_is_human'] = $is_human;
        }
        return $is_human;
    }

    /**
     * PukiWiki admin login with session
     *
     * PukiWiki API Extension
     *
     * @access public
     * @static
     * @param string $pass Password. Use null when to get current session state. 
     * @param boolean $use_session Use Session log
     * @param boolean $use_authlog Use Auth log. 
     *  Username 'admin' is deemed to be Admin in PukiWiki Official. 
     *  PukiWiki Plus! has role management, roles ROLE_ADM and ROLE_ADM_CONTENTS are deemed to be Admin. 
     * @return boolean
     * @version $Id: v 1.0 2008-06-05 11:14:46 sonots $
     */
    function is_admin($pass = null, $use_session = false, $use_authlog = false)
    {
        $is_admin = false;
        if (! $is_admin) {
            if ($use_session) {
                session_start();
                $is_admin = isset($_SESSION['pkwk_is_admin']) && $_SESSION['pkwk_is_admin'];
            }
        }
        // BasicAuth (etc) login
        if (! $is_admin) {
            if ($use_authlog) {
                if (is_callable(array('auth', 'check_role'))) { // Plus!
                    $is_admin = ! auth::check_role('role_adm_contents');
                } else {
                    $is_admin = (isset($_SERVER['PHP_AUTH_USER']) && $_SERVER['PHP_AUTH_USER'] === 'admin');
                }
            }
        }
        // PukiWiki Admin login
        if (! $is_admin) {
            if (isset($pass)) {
                $is_admin = function_exists('pkwk_login') ? pkwk_login($pass) : 
                    md5($pass) === $GLOBALS['adminpass']; // 1.4.3
            }
        }
        if ($use_session) {
            session_start();
            if ($is_admin) $_SESSION['pkwk_is_admin'] = true;
        } else {
            global $vars;
            $vars['pkwk_is_admin'] = $is_admin;
        }
        return $is_admin;
    }

    /**
     * make page edit link showing edit icon
     *
     * PukiWiki API Extension
     *
     * @access public
     * @static
     * @param string $page
     * @return string html
     * @version $Id: v 1.0 2008-06-05 11:14:46 sonots $
     * @todo refine more
     */
    function make_pageeditlink_icon($page) {
        $r_page = rawurlencode($page);
        $link  = '<a class="anchor_super" href="' . get_script_uri() . '?cmd=edit&amp;page=' . $r_page . '">';
        $link .= '<img class="paraedit" src="' . IMAGE_DIR . 'edit.png" alt="Edit" title="Edit" />';
        $link .= '</a>';
        return $link;
    }

    /**
     * make page aname link showing anchor icon (symbol)
     *
     * PukiWiki API Extension
     *
     * @access public
     * @static
     * @param string $page
     * @return string html
     * @see aname.inc.php (aname allows only fiexed_anchors such as x83dvkd8)
     * @version $Id: v 1.1 2008-06-07 11:14:46 sonots $
     * @todo refine more
     */
    function make_pageanamelink_icon($page) {
        global $_symbol_anchor;
        global $pkwk_dtd;
        
        $id = sonots::make_pageanchor($page);

        // from aname
        if (isset($pkwk_dtd) && $pkwk_dtd < PKWK_DTD_XHTML_1_1) {
            $attr_id = ' id="' . $id . '" name="' . $id . '"';
        } else {
            $attr_id = ' id="' . $id . '"';
        }
        $attr_href  = ' href="#' . $id . '"';
        $attr_title = ' title="' . $id . '"';
        $attr_class = ' class="anchor_super"';
        $link  = '<a' . $attr_class . $attr_id . $attr_href . $attr_title . '>' . $_symbol_anchor . '</a>';
        return $link;
    }

    /**
     * make page anchor
     *
     * PukiWiki API Extension
     *
     * @access public
     * @static
     * @param string $page
     * @return string anchor (no starting #)
     * @version $Id: v 1.0 2008-06-05 11:14:46 sonots $
     */
    function make_pageanchor($page)
    {
        $anchor = 'z' . md5($page);
        $anchor = htmlspecialchars($anchor);
        return $anchor;
    }

    /**
     * make a link to pukiwiki top url
     *
     * PukiWiki API Extension
     *
     * @access public
     * @static
     * @param string $alias <a href="">alias</a>
     * @param string $anchor anchor starting from #
     * @return string $link 
     * @version $Id: v 1.0 2008-08-15 11:14:46 sonots $
     */
    function make_toplink($alias = '', $anchor = '')
    {
        if (function_exists('get_script_uri')) {
            $script = get_script_uri();
        } else {
                $script = $GLOBALS['script'];
        }
        return '<a href="' . $script . $anchor . '">' . $alias . '</a>';
    }

    /**
     * make_pagelink without passage
     *
     * PukiWiki API Extension
     *
     * @access public
     * @static
     * @param string $page pagename to be used to create link
     * @param string $alias <a href="">alias</a>
     * @param string $anchor anchor starting from #
     * @param string $refer
     * @param boolean $isautolink true changes looks of link
     * @return string $link 
     * @version $Id: v 1.0 2008-06-05 11:14:46 sonots $
     */
    function make_pagelink_nopg($page, $alias = '', $anchor = '', 
                                $refer = '', $isautolink = false)
    {
        global $show_passage;
        $tmp = $show_passage; $show_passage = 0;
        $link = make_pagelink($page, $alias, $anchor, $refer, $isautolink);
        $show_passage = $tmp;
        return $link;
    }
    /**
     * Check if page is newpage
     *
     * PukiWiki API Extension
     *
     * @access public
     * @static
     * @param string $page
     * @return boolean
     * @version $Id: v 1.0 2008-06-05 11:14:46 sonots $
     */
    function is_newpage($page)
    {
        // pukiwiki trick
        return ! _backup_file_exists($page);
    }
    /**
     * Check if the page timestamp is newer than the file timestamp
     *
     * PukiWiki API Extension
     *
     * @access public
     * @static
     * @param string $page pagename
     * @param string $file filename
     * @param bool $ignore_notimestamp see true editted time
     * @return boolean
     * @version $Id: v 1.1 2008-07-16 11:14:46 sonots $
     */
    function is_page_newer($page, $file, $ignore_notimestamp = false)
    {
        $filestamp = file_exists($file) ? filemtime($file) : 0;
        $pagestamp = 0;
        if ($ignore_notimestamp) { // See the diff file. PukiWiki Trick. 
            $difffile = DIFF_DIR . encode($page) . '.txt';
            if (file_exists($difffile)) $pagestamp = filemtime($difffile);
        }
        if ($pagestamp === 0) {
            if (is_page($page)) $pagestamp = filemtime(get_filename($page));
        }    
        return $pagestamp > $filestamp;
    }
    /**
     * Get page created time
     *
     * PukiWiki API Extension
     *
     * @access public
     * @static
     * @param string $page pagename
     * @return int timestamp
     * @see get_filetime($page)
     * @version $Id: v 1.0 2008-06-05 11:14:46 sonots $
     */
    function get_filecreatetime($page)
    {
        if (_backup_file_exists($page)) { // PukiWiki Trick
            // This is not a created time exactly, but the closest time
            $backup = get_backup($page, 1); // 1st age
            return $backup['time'];
        } else {
            return get_filetime($page);
        }
    }

    /**
     * Get heading strings from a wiki source line
     *
     * PukiWiki API Extension
     *
     * <code>
     * *** Heading Strings ((footnotes)) [id]
     *   -> array("Heading Strings", "id")
     * </code>
     *
     * @access public
     * @static
     * @param string $line a wiki source line
     * @param bool   $strip cut footnotes
     * @return array [0] heading string [1] a fixed-heading anchor
     * @uses lib/html.php#make_heading
     * @version $Id: v 1.1 2008-06-05 11:14:46 sonots $
     */
    function make_heading($line, $strip = true)
    {
        global $NotePattern;
        $id = make_heading($line, false); // $line is modified inside
        if ($strip) {
            $line = preg_replace($NotePattern, '', $line); // cut footnotes
        }
        $line = trim($line);
        return array($line, $id);
    }

    /**
     * Get absolute path
     *
     * PukiWiki API Extension
     *
     * This preserves last slash unlike lib/make_link.php@get_fullname
     *
     * Example)
     * <code>
     *  get_fullname('./a', 'b/c') => 'b/c/a'
     *  get_fullname('../a', 'b/c') => 'b/a'
     *  get_fullname('../a/', 'b/c') => 'b/a'
     *  sonots::get_fullname('../a/', 'b/c') => 'b/a/'
     * </code>
     *
     * @access public
     * @static
     * @param string $name path syntax
     * @param string $refer current place
     * @return string absolute path
     * @version $Id: v 1.0 2008-06-05 11:14:46 sonots $
     */
    function get_fullname($name, $refer)
    {
        global $defaultpage;
        
        // 'Here'
        if ($name == '' || $name == './') return $refer;
        
        // Absolute path
        if ($name{0} == '/') {
            $name = substr($name, 1);
            return ($name == '') ? $defaultpage : $name;
        }
        
        // Relative path from 'Here'
        if (substr($name, 0, 2) == './') {
            $arrn    = preg_split('#/#', $name, -1); //, PREG_SPLIT_NO_EMPTY);
            $arrn[0] = $refer;
            return join('/', $arrn);
        }
        
        // Relative path from dirname()
        if (substr($name, 0, 3) == '../') {
            $arrn = preg_split('#/#', $name,  -1); //, PREG_SPLIT_NO_EMPTY);
            $arrp = preg_split('#/#', $refer, -1, PREG_SPLIT_NO_EMPTY);
            
            while (! empty($arrn) && $arrn[0] == '..') {
                array_shift($arrn);
                array_pop($arrp);
            }
            $name = ! empty($arrp) ? join('/', array_merge($arrp, $arrn)) :
                (! empty($arrn) ? $defaultpage . '/' . join('/', $arrn) : $defaultpage);
        }
        
        return $name;
    }

    /**
     * get readings of pages
     *
     * PukiWiki API Extension
     *
     * arguments $pages version of lib/file.php get_reading
     *
     * @access public
     * @static
     * @param mixed $pages array of pages or a pagename
     *   if not given, get readings of all existing pages
     * @return array readings
     * @version $Id: v 1.0 2008-06-05 11:14:46 sonots $
     */
    function get_readings($pages = array())
    {
        global $pagereading_enable, $pagereading_kanji2kana_converter;
        global $pagereading_kanji2kana_encoding, $pagereading_chasen_path;
        global $pagereading_kakasi_path, $pagereading_config_page;
        global $pagereading_config_dict;
        
        $pages = (array)$pages;
        if (empty($pages)) {
            $pages = get_existpages();
        }

        $readings = array();
        foreach ($pages as $page) 
            $readings[$page] = '';

        $deletedPage = false;
        $matches = array();
        foreach (get_source($pagereading_config_page) as $line) {
            $line = chop($line);
            if(preg_match('/^-\[\[([^]]+)\]\]\s+(.+)$/', $line, $matches)) {
                if(isset($readings[$matches[1]])) {
                    // This page is not clear how to be pronounced
                    $readings[$matches[1]] = $matches[2];
                } else {
                    // This page seems deleted
                    $deletedPage = true;
                }
            }
        }

        // If enabled ChaSen/KAKASI execution
        if($pagereading_enable) {

            // Check there's non-clear-pronouncing page
            $unknownPage = false;
            foreach ($readings as $page => $reading) {
                if($reading == '') {
                    $unknownPage = true;
                    break;
                }
            }

            // Execute ChaSen/KAKASI, and get annotation
            if($unknownPage) {
                switch(strtolower($pagereading_kanji2kana_converter)) {
                case 'chasen':
                    if(! file_exists($pagereading_chasen_path))
                        die_message('ChaSen not found: ' . $pagereading_chasen_path);

                    $tmpfname = tempnam(realpath(CACHE_DIR), 'PageReading');
                    $fp = fopen($tmpfname, 'w') or
                        die_message('Cannot write temporary file "' . $tmpfname . '".' . "\n");
                    foreach ($readings as $page => $reading) {
                        if($reading != '') continue;
                        fputs($fp, mb_convert_encoding($page . "\n",
                                                       $pagereading_kanji2kana_encoding, SOURCE_ENCODING));
                    }
                    fclose($fp);

                    $chasen = "$pagereading_chasen_path -F %y $tmpfname";
                    $fp     = popen($chasen, 'r');
                    if($fp === false) {
                        unlink($tmpfname);
                        die_message('ChaSen execution failed: ' . $chasen);
                    }
                    foreach ($readings as $page => $reading) {
                        if($reading != '') continue;

                        $line = fgets($fp);
                        $line = mb_convert_encoding($line, SOURCE_ENCODING,
                                                    $pagereading_kanji2kana_encoding);
                        $line = chop($line);
                        $readings[$page] = $line;
                    }
                    pclose($fp);

                    unlink($tmpfname) or
                        die_message('Temporary file can not be removed: ' . $tmpfname);
                    break;

                case 'kakasi':	/*FALLTHROUGH*/
                case 'kakashi':
                    if(! file_exists($pagereading_kakasi_path))
                        die_message('KAKASI not found: ' . $pagereading_kakasi_path);

                    $tmpfname = tempnam(realpath(CACHE_DIR), 'PageReading');
                    $fp       = fopen($tmpfname, 'w') or
                        die_message('Cannot write temporary file "' . $tmpfname . '".' . "\n");
                    foreach ($readings as $page => $reading) {
                        if($reading != '') continue;
                        fputs($fp, mb_convert_encoding($page . "\n",
                                                       $pagereading_kanji2kana_encoding, SOURCE_ENCODING));
                    }
                    fclose($fp);

                    $kakasi = "$pagereading_kakasi_path -kK -HK -JK < $tmpfname";
                    $fp     = popen($kakasi, 'r');
                    if($fp === false) {
                        unlink($tmpfname);
                        die_message('KAKASI execution failed: ' . $kakasi);
                    }

                    foreach ($readings as $page => $reading) {
                        if($reading != '') continue;

                        $line = fgets($fp);
                        $line = mb_convert_encoding($line, SOURCE_ENCODING,
                                                    $pagereading_kanji2kana_encoding);
                        $line = chop($line);
                        $readings[$page] = $line;
                    }
                    pclose($fp);

                    unlink($tmpfname) or
                        die_message('Temporary file can not be removed: ' . $tmpfname);
                    break;

                case 'none':
                    $patterns = $replacements = $matches = array();
                    foreach (get_source($pagereading_config_dict) as $line) {
                        $line = chop($line);
                        if(preg_match('|^ /([^/]+)/,\s*(.+)$|', $line, $matches)) {
                            $patterns[]     = $matches[1];
                            $replacements[] = $matches[2];
                        }
                    }
                    foreach ($readings as $page => $reading) {
                        if($reading != '') continue;

                        $readings[$page] = $page;
                        foreach ($patterns as $no => $pattern)
                            $readings[$page] = mb_convert_kana(mb_ereg_replace($pattern,
                                                                               $replacements[$no], $readings[$page]), 'aKCV');
                    }
                    break;

                default:
                    die_message('Unknown kanji-kana converter: ' . $pagereading_kanji2kana_converter . '.');
                    break;
                }
            }

            if($unknownPage || $deletedPage) {

                asort($readings); // Sort by pronouncing(alphabetical/reading) order
                $body = '';
                foreach ($readings as $page => $reading)
                    $body .= '-[[' . $page . ']] ' . $reading . "\n";

                page_write($pagereading_config_page, $body);
            }
        }

        // Pages that are not prounouncing-clear, return pagenames of themselves
        foreach ($pages as $page) {
            if($readings[$page] == '')
                $readings[$page] = $page;
        }

        return $readings;
    }

    ////////////////// PHP API Extension ///////////////
    /**
     * Grep out array of objects by speific members
     *
     * @access public
     * @static
     * @param array $objs
     * @param string $meta name of meta information to be greped
     * @param string $func func name
     *  - preg     : grep by preg
     *  - ereg     : grep by ereg
     *  - mb_ereg  : grep by mb_ereg
     *  - prefix   : remains if prefix matches (strpos)
     *  - mb_prefix: (mb_strpos)
     *  - eq       : remains if equality holds
     *  - ge       : remains if greater or equal to
     *  - le       : remains if less or equal to
     * @param mixed $pattern
     * @param boolean $inverse grep -v
     * @return void
     */
    function grep_by(&$objs, $meta, $func, $pattern, $inverse = FALSE)
    {
        $metas = sonots::get_members($objs, $meta);
        $metas = sonots::grep_array($pattern, $metas, $func);
        if (! $inverse) {
            $objs = array_intersect_key($objs, $metas);
        } else {
            $objs = array_diff_key($objs, $metas);
        }
    }

    /**
     * Grep array
     *
     * PHP API Extension
     *
     * @access public
     * @static
     * @param string $pattern
     * @param array $array
     * @param string $func func name
     *  - preg     : grep by preg
     *  - ereg     : grep by ereg
     *  - mb_ereg  : grep by mb_ereg
     *  - strpos   : grep by string
     *  - mb_strpos: grep by multibyte string
     *  - prefix   : grep by prefix match
     *  - mb_prefix: grep by multibyte prefix match
     *  - eq       : grep by equality (identity, ===)
     *  - ge       : grep by greater or equal to
     *  - le       : grep by less or equal to
     * @param boolean $preserve_keys
     * @param boolean $inverse grep -v
     * @return array
     * @see array_diff use array_diff to get inverse
     * @version $Id: v 1.0 2008-06-05 11:14:46 sonots $
     * @todo refine more
     */
    function grep_array($pattern, $array, $func, $preserve_keys = true, $inverse = false)
    {
        if ($inverse) $original = $array;
        switch ($func) {
        case 'preg':
            $array = preg_grep($pattern, $array);
            break;
        case 'ereg':
        case 'mb_ereg':
            foreach ($array as $i => $val) {
                if (! call_user_func($func, $pattern, $val)) 
                    unset($array[$i]);
            }
            break;
        case 'strpos':
        case 'mb_strpos':
            foreach ($array as $i => $val) {
                if (call_user_func($func, $pattern, $val) === false) 
                    unset($array[$i]);
            }
            break;
        case 'prefix':
            foreach ($array as $i => $val) {
                if (strpos($val, $pattern) !== 0) 
                    unset($array[$i]);
            }
            break;
        case 'mb_prefix':
            foreach ($array as $i => $val) {
                if (mb_strpos($val, $pattern) !== 0) 
                    unset($array[$i]);
            }
            break;
        case 'eq':
            foreach ($array as $i => $val) {
                if ($pattern !== $val) unset($array[$i]);
            }
        case 'ge':
            foreach ($array as $i => $val) {
                if ($val < $pattern) unset($array[$i]);
            }
            break;
        case 'le':
            foreach ($array as $i => $val) {
                if ($val > $pattern) unset($array[$i]);
            }
            break;
        }
        if ($inverse) $array = array_diff($original, $array);
        if ($preserve_keys) {
            return $array;
        } else {
            $outarray = array();
            foreach ($array as $val) $outarray[] = $val;
            return $outarray;
        }
    }
    
    /**
     * Reads heads of file into an array
     *
     * PHP API Extension
     *
     * @access public
     * @static
     * @param string  $file filename
     * @param int     $count number of executed fgets, usually equivalent to number of lines
     * @param boolean $lock use lock or not 
     * @param int     $buffer number of bytes to be read in one fgets
     * @return array
     * @version $Id: v 1.0 2008-06-05 11:14:46 sonots $
     */
    function file_head($file, $count = 1, $lock = true, $buffer = 8192)
    {
        $array = array();
        
        $fp = @fopen($file, 'r');
        if ($fp === false) return false;
        set_file_buffer($fp, 0);
        if ($lock) @flock($fp, LOCK_SH);
        rewind($fp);
        $index = 0;
        while (! feof($fp)) {
            $line = fgets($fp, $buffer);
            if ($line != false) $array[] = $line;
            if (++$index >= $count) break;
        }
        if ($lock) @flock($fp, LOCK_UN);
        if (! fclose($fp)) return false;
        
        return $array;
    }

    /**
     * Find positions of occurrence of a string
     *
     * PHP API Extension
     *
     * @access public
     * @static
     * @param string $str
     * @param string $substr
     * @return array positions
     * @version $Id: v 1.0 2008-06-05 11:14:46 sonots $
     */
    function r_strpos($str, $substr)
    {
        $r_pos = array();
        while(true) {
            $pos = strpos($str, $substr);
            if ($pos === false) break;
            array_push($r_pos, $pos);
            $str = substr($str, $pos + 1);
        }
        return $r_pos;
    }

    /**
     * Display ul list
     *
     * PHP API Extension
     *
     * PukiWiki outputs ul lists as 
     * <code>
     * <ul><li style="padding-left:16*2px;margin-left:16*2px">.
     * </code>
     * I do not like it. This codes output as 
     * <code>
     * <ul><li style="list-type:none"><ul><li>. 
     * </code>
     * This is also XHTML 1.1 valid. Furthermore, this codes print no return 
     * because some browsers create spaces by returns in ul list. 
     *
     * @access public
     * @static
     * @param array $items strings of items to be displayed
     * @param array $levels list levels of items
     * @param string $cssclass css class name
     * @return string list html
     * @version $Id: v 1.0 2008-06-05 11:14:46 sonots $
     */
    function display_list($items, $levels, $cssclass = '')
    {
        /* Following codes work as the right to compose the left HTML
         * 
         * <ul>                <ul><li>1
         *  <li>1</li>         </li><li>1
         *  <li>1              <ul><li>2
         *   <ul>              </li></ul></li><li>1
         *    <li>2</li>       </li><li>1
         *   </ul>        =>   <ul><li style="list-type:none"><ul><li>3
         *  </li>              </li></ul></li></ul></li></ul>
         *  <li>1</li>
         *  <li>1
         *   <ul>
         *    <li style="list-type:none">
         *     <ul>
         *      <li>3</li>
         *     </ul>
         *    </li>
         *   </ul>
         *  </li>
         * <ul>
         */
        $ul = $pdepth = 0; $html = '';
        foreach ($items as $i => $item) {
            $depth = $levels[$i];
            $extra = isset($extras[$i]) ? $extras[$i] : '';
            if ($depth > $pdepth) {
                $diff = $depth - $pdepth;
                $html .= str_repeat('<ul><li style="list-style:none">', $diff - 1);
                if ($depth == 1) { // first flag
                    $html .= '<ul' . (isset($cssclass) ? ' class="' . $cssclass . '"' : '') . '><li>';
                } else {
                    $html .= '<ul><li>';
                }
                $ul += $diff;
            } elseif ($depth == $pdepth) {
                $html .= '</li><li>';
            } elseif ($depth < $pdepth) {
                $diff = $pdepth - $depth;
                $html .= str_repeat('</li></ul>', $diff);
                $html .= '</li><li>';
                $ul -= $diff;
            }
            $pdepth = $depth;

            $html .= $item;
        }
        $html .= str_repeat('</li></ul>', $ul);
        return $html;
    }

    /**
     * Move a file (rename does not overwrite if $newname exists on Win, but move does)
     *
     * PHP API Extension
     *
     * @access public
     * @static
     * @param string $oldname
     * @param string $newname
     * @return boolean
     * @version $Id: v 1.0 2008-06-05 11:14:46 sonots $
     */
    function move($oldname, $newname) {
        if (! rename($oldname, $newname)) {
            if (copy ($oldname, $newname)) {
                unlink($oldname);
                return true;
            }
            return false;
        }
        return true;
    }

    /**
     * Grep an array by ereg expression
     *
     * PHP API Extension
     *
     * @access public
     * @static
     * @param string $pattern
     * @param array $input
     * @param int $flags
     * @return array
     * @see preg_grep
     */
    function &ereg_grep($pattern, $input, $flags = 0)
        {
            if ($flag & EREG_GREP_INVERT) {
                foreach ($input as $i => $string) {
                    if (ereg($pattern, $string)) {
                        unset($input[$i]); // unset rather than stack for memory saving
                    }
                }
            } else {
                foreach ($input as $i => $string) {
                    if (! ereg($pattern, $string)) {
                        unset($input[$i]);
                    }
                }
            }
            return $input;
        }

    /**
     * Initialize members of array of class objects
     *
     * PHP API Extension
     *
     * @access public
     * @static
     * @param array &$objects array of objects
     * @param string $name member name
     * @param mixed $value initialization value
     * @return void
     * @version $Id: v 1.0 2008-06-10 11:14:46 sonots $
     * @since  1.10
     */
    function init_members(&$objects, $name, $value)
    {
        foreach ($objects as $i => $object) {
            $objects[$i]->$name = $value;
        }
    }

    /**
     * Set array into members of array of class objects
     *
     * PHP API Extension
     *
     * @access public
     * @static
     * @param array &$objects array of objects
     * @param string $name member name
     * @param array $members array of member variables, 
     *   size and keys must be same with $objects.
     * @return void
     * @version $Id: v 1.0 2008-06-05 11:14:46 sonots $
     */
    function set_members(&$objects, $name, &$members)
    {
        foreach ($objects as $i => $object) {
            $objects[$i]->$name = $members[$i];
        }
    }

    /**
     * Get specific members from array of class objects
     *
     * PHP API Extension
     *
     * @access public
     * @static
     * @param array &$objects array of objects
     * @param string $name member name
     * @return array array of members, keys are preserved. 
     * @version $Id: v 1.0 2008-06-05 11:14:46 sonots $
     */
    function &get_members(&$objects, $name)
    {
        $array = array();
        foreach ($objects as $i => $object) {
            $array[$i] = $object->$name;
        }
        return $array;
    }

    /**
     * Applies the callback to the members of the given array objects
     *
     * PHP API Extension
     *
     * @access public
     * @static
     * @param array &$objects array of objects
     * @param string $name member name
     * @param callback $callback
     * @return void
     * @version $Id: v 1.0 2008-06-10 11:14:46 sonots $
     * @since  1.10
     */
    function map_members(&$objects, $name, $callback)
    {
        $members = sonots::get_members($objects, $name);
        $members = array_map($callback, $members);
        sonots::set_members($objects, $name, $members);
    }

    /**
     * Get list of files in a directory
     *
     * PHP Extension
     *
     * @access public
     * @static
     * @param string $dir Directory Name
     * @param string $ext File Extension
     * @param bool $recursive Traverse Recursively
     * @return array array of filenames
     * @uses is_dir()
     * @uses opendir()
     * @uses readdir()
     * @version $Id: v 1.0 2008-06-05 11:14:46 sonots $
     */
    function &get_existfiles($dir, $ext = '', $recursive = false) 
        {
            if (($dp = @opendir($dir)) == false)
                return false;
            $pattern = '/' . preg_quote($ext, '/') . '$/';
            $dir = ($dir[strlen($dir)-1] == '/') ? $dir : $dir . '/';
            $dir = ($dir == '.' . '/') ? '' : $dir;
            $files = array();
            while (($file = readdir($dp)) !== false ) {
                if($file != '.' && $file != '..' && is_dir($dir . $file) && $recursive) {
                    $files = array_merge($files, get_existfiles($dir . $file, $ext, $recursive));
                } else {
                    $matches = array();
                    if (preg_match($pattern, $file, $matches)) {
                        $files[] = $dir . $file;
                    }
                }
            }
            closedir($dp);
            return $files;
        }

    /**
     * Get the dirname of a path
     *
     * PHP API Extension
     *
     * PHP's dirname works as
     * <code>
     *  'Page/' => '.', 'Page/a' => 'Page', 'Page' => '.'
     * </code>
     * This function works as
     * <code>
     *  'Page/' => 'Page', 'Page/a' => 'Page', 'Page' => ''
     * </code>
     *
     * @access public
     * @static
     * @param string $path
     * @return string dirname
     * @version $Id: v 1.0 2008-06-05 11:14:46 sonots $
     */
    function get_dirname($path)
    {
        if (($pos = strrpos($path, '/')) !== false) {
            return substr($path, 0, $pos);
        } else {
            return '';
        }
    }

    /**
     * Get the basename of a path
     *
     * PHP API Extension
     *
     * PHP's basename works as
     * <code>
     *  'Page/' => 'Page', 'Page/a' => 'a', 'Page' => 'Page'
     * </code>
     * This function works as
     * <code>
     *  'Page/' => '', 'Page/a' => 'a', 'Page' => 'Page'
     * </code>
     *
     * @access public
     * @static
     * @param string $path
     * @param string $suffix cut suffix of the basename
     * @return string basename
     * @version $Id: v 1.0 2008-06-05 11:14:46 sonots $
     */
    function get_basename($path, $suffix = '')
    {
        if (($pos = strrpos($path, '/')) !== false) {
            $basename = substr($path, $pos + 1);
        } else {
            $basename = $path;
        }
        if (($pos = strrpos($basename, $suffix)) !== false) {
            $basename = substr($basename, 0, $pos);
        }
        return $basename;
    }

    /**
     * Urlencode only given specific chars
     *
     * PHP API Extension
     *
     * @access public
     * @static
     * @param string $string
     * @param string $chars this string is splitted into chars. 
     *               If null, urlencode is performed. 
     * @return string
     * @version $Id: v 1.0 2008-07-15 11:14:46 sonots $
     */
    function urlencode($string, $chars = null)
    {
        if (is_null($chars)) return urlencode($string);
        $chars = str_split($chars);
        array_unshift($chars, '%');
        $chars = array_unique($chars);
        foreach ($chars as $char) {
            $string = str_replace($char, '%' . strtoupper(bin2hex($char)), $string);
        }
        return $string;
    }

    /**
     * Urldecode only given specific chars
     *
     * PHP API Extension
     *
     * @access public
     * @static
     * @param string $string
     * @param string $chars this string is splitted into chars.
     *               If null, urldecode is performed. 
     * @return string
     * @version $Id: v 1.0 2008-07-15 11:14:46 sonots $
     */
    function urldecode($string, $chars = null)
    {
        if (is_null($chars)) return urldecode($string);
        $chars = str_split($chars);
        array_unshift($chars, '%');
        $chars = array_unique($chars);
        foreach ($chars as $char) {
            $string = str_replace('%' . strtoupper(bin2hex($char)), $char, $string);
        }
        return $string;
    }

    /**
     * Convert an array to a string. 
     *
     * PHP API Extension
     * 
     * Example
     * <code>
     * $arr = array('A', 'B', 'indC' => 'C', array('D', 'E'), 'indF'=>'F');
     * echo array_to_string($arr);
     * </code>
     * Output:
     * <code>
     * A,B,indC:C,(D,E),indF:F
     * </code>
     *
     * @access public
     * @static
     * @param array $array
     * @param string $hashsep A character to be used as a hash key and val seperator
     * @param string $elemsep A character to be used as a elememt separator
     * @param string $openarray A character to be used as an open bracket of an array
     * @param string $closearray A character to be used as a close bracket of an array
     * @param boolean $encode Performe encode for key/val or not
     *    Note: encoding is usually necessary especially when you want 
     *    to use delimiter characters in keys and values
     * @return string
     * @see string_to_array
     * @version $Id: v 1.3 2008-07-15 11:14:46 sonots $
     */
    function array_to_string($array, $hashsep = ':', $elemsep = ',', 
                             $openarray = '(', $closearray = ')', $encode = true)
    {
        $string = "";
        $delims = $hashsep . $elemsep . $openarray . $closearray;
        foreach($array as $key => $value){
            if(is_array($value)){
                $value = sonots::array_to_string($value, $hashsep, $elemsep, 
                                                 $openarray, $closearray, $encode);
                $value = $openarray . $value . $closearray;
            } else {
                $value = $encode ? sonots::urlencode($value, $delims) : $value;
            }
            if (is_int($key)) {
                $string .= $elemsep . $value;
            } else {
                $key = $encode ? sonots::urlencode($key, $delims) : $key;
                $string .= $elemsep . $key . $hashsep . $value;
            }
        }
        $string = substr($string, 1);
        return $string;
    }

    /**
     * Restore a string to an array. 
     *
     * PHP API Extension
     * 
     * Example
     * <code>
     * $string = 'A,B,indC:C,(0:D,1:E),indF:F'
     * $array = string_to_array($string)
     * <code>
     * Output:
     * <code>
     * array('A', 'B', 'indC' => 'C', array('D', 'E'), 'indF'=>'F');
     * </code>
     *
     * @access public
     * @static
     * @param string $string
     * @param string $hashsep A character to be used as a hash key and val seperator
     * @param string $elemsep A character to be used as a elememt separator
     * @param string $openarray A character to be used as an open bracket of an array
     * @param string $closearray A character to be used as a close bracket of an array
     * @param boolean $decode Performe decode key/val
     * @return array
     * @see array_to_string
     * @version $Id: v 1.3 2008-07-15 11:14:46 sonots $
     */
    function string_to_array($string, $hashsep = ':', $elemsep = ',', 
                             $openarray = '(', $closearray = ')', $decode = true)
    {
        $result = array();
        $delims = $hashsep . $elemsep . $openarray . $closearray;
        if ($string === '') return $result;
        /// parse the first element
        $hashsep_pos = strpos($string, $hashsep);
        $elemsep_pos = strpos($string, $elemsep);
        $openarray_pos = strpos($string, $openarray);
        // there is a key or not for the 1st element
        if ($hashsep_pos !== false &&
            ($elemsep_pos === false || $hashsep_pos < $elemsep_pos) &&
            ($openarray_pos === false || $hashsep_pos < $openarray_pos)) {
            $key = substr($string, 0, $hashsep_pos);
            $key = $decode ? sonots::urldecode($key, $delims) : $key;
            $string = trim(substr($string , $hashsep_pos+1));
        } else {
            $key = null;
        }
        $openarray_pos = strpos($string, $openarray);
        if ($openarray_pos === false || $openarray_pos > 0) { // hash val is not an array
            $elemsep_pos = strpos($string, $elemsep);
            if ($elemsep_pos === false) {
                $val = $decode ? sonots::urldecode($string, $delims) : $string;
                $string = "";
            }else{
                $val = substr($string, 0, $elemsep_pos);
                $val = $decode ? sonots::urldecode($val, $delims) : $val;
                $string = substr($string, $elemsep_pos+1);
            }
        } elseif ($openarray_pos == 0) { // hash val is an array
            $string = substr($string, 1);
            $num_openarray = 1;
            // search where is a corresponding closet
            $string_char_array = str_split($string);
            for($index = 0; count($string_char_array); $index++) {
                if ($string_char_array[$index] == $openarray) {
                    $num_openarray++;
                }else if ($string_char_array[$index] == $closearray) {
                    $num_openarray--;
                }
                if ($num_openarray == 0) {
                    break;
                }
            }
            $val = sonots::string_to_array(substr($string, 0, $index), 
                $hashsep, $elemsep, $openarray, $closearray, $decode);
            $string = substr($string, $index+2);
        }
        if (is_null($key)) {
            $result[] = $val;
        } else {
            $result[$key] = $val;
        }
        /// next element
        if (strlen($string) != 0) {
            $result = array_merge($result, sonots::string_to_array($string, 
                                                                   $hashsep, $elemsep, $openarray, $closearray, $decode));
        }
    
        return $result;
    }

    /**
     * trim elements of array
     *
     * PHP API Extension
     *
     * @access public
     * @static
     * @param array $array
     * @param boolean $recursive recursively
     * @param boolean $trimkey trim key too
     * @return array
     * @version $Id: v 1.0 2008-06-05 11:14:46 sonots $
     */
    function trim_array($array, $recursive = false, $trimkey = false)
    {
        $outarray = array();
        foreach ($array as $key => $val) {
            unset($array[$key]); // save memory
            if ($recursive && is_array($val)) {
                $val = sonots::trim_array($val, $recursive, $trimkey);
            } elseif (is_string($val)) {
                $val = trim($val);
            }
            if ($trimkey && is_string($key)) {
                $key = trim($key);
            }
            $outarray[$key] = $val;
        }
        return $outarray;
    }
        
    /**
     * reverse parse_str
     *
     * PHP API Extension
     *
     * Note: parse_str does rawurldecode and convert . into _ for keys
     *
     * @access public
     * @static
     * @param array $queries outputs by parse_str
     * @return string reversed parse_str
     * @see parse_str()
     * @version $Id: v 1.0 2008-06-05 11:14:46 sonots $
     */
    function glue_str($queries)
    {
        if (! is_array($queries))
            return false;
    
        $url_query = array();
        foreach ($queries as $key => $value) {
            $arg = ($value === '') ? rawurlencode($key) : 
                rawurlencode($key) . '=' . rawurlencode($value);
            array_push($url_query, $arg);
        }
        return implode('&', $url_query);
    }


    /**
     * reverse parse_url
     *
     * PHP Extension
     *
     * @access public
     * @static
     * @param array $parsed outputs by parse_url
     * @return string reversed parse_url
     * @see parse_url()
     * @version $Id: v 1.0 2008-06-05 11:14:46 sonots $
     */
    function glue_url($parsed) 
    {
        if (!is_array($parsed)) return false;
        $uri = isset($parsed['scheme']) ? $parsed['scheme'].':'.((strtolower($parsed['scheme']) == 'mailto') ? '' : '//') : '';
        $uri .= isset($parsed['user']) ? $parsed['user'].(isset($parsed['pass']) ? ':'.$parsed['pass'] : '').'@' : '';
        $uri .= isset($parsed['host']) ? $parsed['host'] : '';
        $uri .= isset($parsed['port']) ? ':'.$parsed['port'] : '';
        if(isset($parsed['path'])) {
            $uri .= (substr($parsed['path'], 0, 1) == '/') ? $parsed['path'] : ('/'.$parsed['path']);
        }
        $uri .= isset($parsed['query']) ? '?'.$parsed['query'] : '';
        $uri .= isset($parsed['fragment']) ? '#'.$parsed['fragment'] : '';
        return $uri;
    }

    /**
     * Undo htmlspecialchars
     *
     * PHP API Extension
     *
     * @access public
     * @static
     * @param string 
     * @return string Undone htmlspecialchars
     * @see htmlspecialchars()
     * @example unhtmlspecialchars.php
     * @version $Id: v 1.0 2008-06-05 11:14:46 sonots $
     */
    function &unhtmlspecialchars($string)
        {
            $string = str_replace('&amp;' , '&' , $string);
            $string = str_replace('&#039;', '\'', $string);
            $string = str_replace('&quot;', '"', $string);
            $string = str_replace('&lt;'  , '<' , $string);
            $string = str_replace('&gt;'  , '>' , $string);
            return $string;
        }

    /**
     * Get absolute URL
     *
     * PHP Extension
     *
     * @access public
     * @static
     * @param string $base base url
     * @param string $url relative url
     * @return string absolute url
     * @see parse_url()
     * @see realpath()
     * @uses glue_url()
     * @version $Id: v 1.0 2008-06-05 11:14:46 sonots $
     */
    function realurl($base, $url)
    {
        if (! strlen($base)) return $url;
        if (! strlen($url)) return $base;
        
        if (preg_match('!^[a-z]+:!i', $url)) return $url;
        $base = parse_url($base);
        if ($url{0} == "#") { 
            // fragment
            $base['fragment'] = substr($url, 1);
            return sonots::glue_url($base);
        }
        unset($base['fragment']);
        unset($base['query']);
        if (substr($url, 0, 2) == "//") {
            // FQDN
            $base = array(
                          'scheme'=>$base['scheme'],
                          'path'=>substr($url,2),
                          );
            return sonots::glue_url($base);
        } elseif ($url{0} == "/") {
            // absolute path reference
            $base['path'] = $url;
        } else {
            // relative path reference
            $path = explode('/', $base['path']);
            $url_path = explode('/', $url);
            // drop file from base
            array_pop($path);
            // append url while removing "." and ".." from
            // the directory portion
            $end = array_pop($url_path);
            foreach ($url_path as $segment) {
                if ($segment == '.') {
                    // skip
                } elseif ($segment == '..' && $path && $path[sizeof($path)-1] != '..') {
                    array_pop($path);
                } else {
                    $path[] = $segment;
                }
            }
            // remove "." and ".." from file portion
            if ($end == '.') {
                $path[] = '';
            } elseif ($end == '..' && $path && $path[sizeof($path)-1] != '..') {
                $path[sizeof($path)-1] = '';
            } else {
                $path[] = $end;
            }
            $base['path'] = join('/', $path);
        }
        return sonots::glue_url($base);
    }

    /**
     * Special sort function for filenames (/ has a special meaning)
     *
     * PHP API Extension
     *
     * This function makes sure that files under a directory
     * are followed by the directory as
     * <code>
     *  Foo
     *  Foo/Bar
     *  FooBar
     * </code>
     * not
     * <code>
     *  Foo
     *  FooBar
     *  Foo/Bar
     * </code>
     * This function is especially useful when filenames include multi-byte words
     *
     * @access public
     * @static
     * @param array &$filenames
     * @version $Id: v 1.0 2008-06-05 11:14:46 sonots $
     */
    function sort_filenames(&$filenames)
    {
        $filenames = str_replace('/', "\0", $filenames);
        sort($filenames, SORT_STRING);
        $filenames = str_replace("\0", '/', $filenames);
    }

    /**
     * Special natcasesort function for filenames (/ has a special meaning)
     *
     * PHP API Extension
     *
     * This function makes sure that files under a directory
     * are followed by the directory as
     * <code>
     *  Foo
     *  Foo/Bar
     *  FooBar
     * </code>
     * not
     * <code>
     *  Foo
     *  FooBar
     *  Foo/Bar
     * </code>
     * This function is especially useful when filenames include multi-byte words
     *
     * @access public
     * @uses sonots::r_strnatcasecmp
     * @see sonots::sort_filenames
     * @static
     * @param array &$filenames (maintain index association)
     * @version $Id: v 1.0 2008-08-15 11:14:46 sonots $
     */
    function natcasesort_filenames(&$filenames)
    {
        $dirnames = array();
        foreach ($filenames as $i => $filename) {
            $dirnames[$i] = explode('/', $filename);
        }
        uasort($dirnames, array('sonots', 'r_strnatcasecmp'));
        $outarray = array();
        foreach ($dirnames as $i => $dirname) {
            $outarray[$i] = implode('/', $dirname);
        }
        $filenames = $outarray;
    }

    /*
     * Recursive strnatcasecmp
     *
     * See sonots::r_cmp. This uses 'strnatcasecmp' as $cmpfunc. 
     *
     * @access public
     * @static
     * @param array $a
     * @param array $b
     * @return int
     * @see sonots::r_cmp
     * @version $Id: v 1.0 2008-08-15 11:14:46 sonots $
     */
    function r_strnatcasecmp($a, $b)
    {
        return sonots::r_cmp($a, $b, 'strnatcasecmp');
    }

    /*
     * Recursive strnatcmp
     *
     * See sonots::r_cmp. This uses 'strnatcmp' as $cmpfunc. 
     *
     * @access public
     * @static
     * @param array $a
     * @param array $b
     * @return int
     * @see sonots::r_cmp
     * @version $Id: v 1.0 2008-08-15 11:14:46 sonots $
     */
    function r_strnatcmp($a, $b)
    {
        return sonots::r_cmp($a, $b, 'strnatcmp');
    }

    /*
     * Recursive comparison (Use this to create a cmp function)
     *
     * PHP API Extension
     *
     * This function recursively applys a comparison function
     * for each element of an array and its respective element of another array.
     * For example, for
     * <code>
     * $a = array('foo', 'bar');
     * $b = array('foo', 'bor', 'hoge');
     * </code>
     * compare $a[0] and $b[0], $a[1] and $b[1], ... until difference is found. 
     * If they are same, and one array does not have enough elements to be
     * compared more on, the array comes front. 
     *
     * usort accepts a function whose number of arguments is two ($a, $b). 
     * Wrap this function to create a comparison function for usort as
     * create_function('$a,$b', 'return sonots::r_cmp($a,$b,"strnatcmp");')
     *
     * @access public
     * @static
     * @param array $a
     * @param array $b
     * @param string $cmpfunc comparison func
     * @return negative if $a < $b, positive if $a > $b, 0 if $a == $b
     * @see usort, array_multisort
     * @version $Id: v 1.0 2008-08-15 11:14:46 sonots $
     */
    function r_cmp($a, $b, $cmpfunc = 'strcmp')
    {
        $keys = array_intersect(array_keys($a), array_keys($b));
        foreach ($keys as $key) {
            $aval = $a[$key];
            $bval = $b[$key];
            $cmp = $cmpfunc($aval, $bval);
            if ($cmp != 0) return $cmp;
        }
        if (count($a) == count($b)) return 0;
        return (count($a) < count($b)) ? -1 : 1;
    }

    /**
     * sort array in the given key sequence maintaining key association
     *
     * PHP API Extension
     *
     * Example)
     * <code>
     * $japanese  = array('orange'=>'mikan', 'apple'=>'ringo');
     * $price = array('orange'=> 100, 'apple'= 50);
     * asort($price, SORT_NUMERIC); // array('apple'=> 50, 'orange'= 100);
     * array_asort_key($favor, $price); // array('orange'=>'ringo', 'apple'=>'apple');
     * </code>
     *
     * @access public
     * @static
     * @param array &$array array to be sorted
     * @param array &$sorted array having keys in sorting sequence. 
     *   keys of $array and $sorted must be all common, i.e.,
     *   count($array) == count(array_intersect_key($array, $sorted))
     * @return void
     * @version $Id: v 1.0 2008-06-05 11:14:46 sonots $
     */
    function array_asort_key(&$array, &$sorted)
    {
        $outarray = array();
        foreach ($sorted as $key=> $tmp) {
            $outarray[$key] = $array[$key]; // change the pointer sequences
            unset($array[$key]);
        }
        $array = $outarray;
    }
    /**
     * sort array in the given key sequence 
     *
     * PHP API Extension
     *
     * Example)
     * <code>
     * $fruits  = array(0 => 'orange', 1 => 'apple');
     * $price   = array(0 => 100, 1 => 50);
     * asort($price, SORT_NUMERIC); // array(1 => 50, 0 => 100)
     * array_sort_key($fruits, $price); // array(0 => 'apple', 1 => 'orange')
     * </code>
     *
     * @access public
     * @static
     * @param array &$array array to be sorted
     * @param array &$sorted array having keys in sorting sequence. 
     *   keys of $array and $sorted must be all common, i.e.,
     *   count($array) == count(array_intersect_key($array, $sorted))
     * @return void
     * @version $Id: v 1.0 2008-06-05 11:14:46 sonots $
     */
    function array_sort_key(&$array, &$sorted)
    {
        $outarray = array();
        foreach ($sorted as $key=> $tmp) {
            $outarray[] = $array[$key]; // rebuild array
            unset($array[$key]);
        }
        $array = $outarray;
    }

    /**
     * Error Handling in PHP4. throw an error message
     *
     * PHP5)
     * <code>
     * function a_function_throw_inside()
     * {
     *   throw(new Exception('Throw Error'));
     * }
     * try {
     *   a_function_throw_inside();
     *   echo 'Never Executed';
     * } catch (Exception $e) {
     *   echo $e->getMessage() . "\n";
     * }
     * </code>
     *
     * This)
     * <code>
     * function a_function_throw_inside()
     * {
     *   sonots::mythrow('Throw Error'); return;
     * }
     * sonots::init_error(); do { // try
     *   a_function_throw_inside();
     *   if (sonots::mycatch()) break; // burdensome, though
     *   echo 'Never Executed';
     * } while (false);
     * if (sonots::mycatch()) { // catch
     *   echo sonots::mycatch();
     * }
     * </code>
     *
     * @access public
     * @static
     * @param string $errmsg
     * @return void
     * @see init_myerror
     * @see mycatch
     * @version $Id: v 1.0 2008-06-05 11:14:46 sonots $
     */
    function mythrow($errmsg)
    {
        set_error_handler(create_function('$errno, $errstr, $errfile, $errline', 
                                          '$GLOBALS["php_errmsg"] = $errstr;'));
        @trigger_error($errmsg, E_USER_ERROR);
        restore_error_handler();
    }
    /**
     * Error Handling in PHP4. catch an error message
     *
     * @access public
     * @static
     * @global string php_errmsg
     * @return string err_msg
     * @see init_myerror
     * @see mythrow
     * @version $Id: v 1.0 2008-06-05 11:14:46 sonots $
     */
    function mycatch()
    {
        global $php_errmsg;
        return $php_errmsg;
    }

    /**
     * Error Handling in PHP4. init error states
     *
     * @access public
     * @static
     * @global string php_errmsg
     * @see mythrow
     * @see mycatch
     * @version $Id: v 1.0 2008-06-05 11:14:46 sonots $
     * @since v 1.4
     */
    function init_myerror()
    {
        global $php_errmsg;
        $php_errmsg = '';
    }

    ///////////////// PHP Compat /////////////
    /**
     * Extract a slice of the array
     *
     * PHP Compat
     *
     * $length == null means up to end of array. Now,
     * sonots::array_slice($array, $offset, null, true) is possible.
     * <code>
     * array_slice($array, $offset, null (or 0 or -0)) returns array(),
     * </code>
     * thus, there was no way to slice up to end of array with preserving keys. 
     *
     * @access public
     * @static
     * @param array $array The input array.
     * @param int $offset If offset is non-negative, the sequence will start at that offset in the array. 
     *     If offset is negative, the sequence will start that far from the end of the array.
     * @param mixed $length If length is given and is positive, then the sequence will have that many elements in it. 
     *     If length is given and is negative then the sequence will stop that many elements from the end of the array. 
     *     If it is omitted or NULL (add), then the sequence will have everything from offset up until the end of the array .
     * @param boolean $preserve_keys Note that array_slice() will reorder and reset the array indices by default. 
     *     You can change this behaviour by setting preserve_keys to true.
     * @return array
     * @version $Id: v 1.0 2008-06-07 11:14:46 sonots $
     * @since v 1.7
     */
    function array_slice($array, $offset, $length = null, $preserve_keys = false)
    {
        if (is_null($length)) {
            if (! $preserve_keys) {
                return array_slice($array, $offset);
            } else {
                $keys = array_slice(array_keys($array), $offset);
                $ret = array();
                foreach ($keys as $key) {
                    $ret[$key] = $array[$key];
                }
                return $ret;
            }
        } else {
            return php_compat_array_slice($array, $offset, $length, $preserve_keys);
        }
    }
      
    /**
     * Write a string to a file (PHP5 has this function)
     *
     * PHP Compat
     *
     * @access public
     * @static
     * @param string $filename
     * @param string $data
     * @param int $flags
     * @return int the amount of bytes that were written to the file, or FALSE if failure
     * @see php_compat_file_put_contents (better)
     * @version $Id: v 1.0 2008-06-05 11:14:46 sonots $
     */
    function file_put_contents($filename, $data, $flags = 0)
    {
        $mode = ($flags & FILE_APPEND) ? 'a' : 'w';
        $fp = fopen($filename, $mode);
        if ($fp === false) {
            return false;
        }
        if (is_array($data)) $data = implode('', $data);
        if ($flags & LOCK_EX) flock($fp, LOCK_EX);
        $bytes = fwrite($fp, $data);
        if ($flags & LOCK_EX) flock($fp, LOCK_UN);
        fclose($fp);
        return $bytes;
    }

    /**
     * mkdir recursively (mkdir of PHP5 has recursive flag)
     *
     * PHP Compat
     *
     * @access public
     * @static
     * @param string $dir
     * @param int $mode
     * @return boolean success or failure
     * @see php_compat_mkdir($dir, $mode, $recurstive, $context)
     * @version $Id: v 1.0 2008-06-05 11:14:46 sonots $
     */
    function r_mkdir($dir, $mode = 0755)
    {
        if (is_dir($dir) || @mkdir($dir,$mode)) return true;
        if (! r_mkdir(dirname($dir),$mode)) return false;
        return @mkdir($dir,$mode);
    }

    /**
     * Create a clone of object
     *
     * PHP Compat
     *
     * @access public
     * @static
     * @param object $object
     * @return object cloned object
     * @see php_compat_clone
     * @version $Id: v 1.0 2008-06-05 11:14:46 sonots $
     */
    function create_clone($object) {
        if (version_compare(phpversion(), '5.0') < 0) {
            return $object;
        } else {
            return @clone($object);
        }
    }
}

//////////// php compat //////////////
if (! function_exists('_')) {
    /**
     * PHP Compat for i18n gettext
     *
     * @param string $str
     * @return string
     * @version $Id: v 1.0 2008-06-05 11:14:46 sonots $
     */
    // Let me write this here because gettext must have short function name
    function &_($str)
        {
            return $str;
        }
 }

/////////// define ///////////////////
// is_human
if (! defined('ROLE_AUTH')) define('ROLE_AUTH', 5); // define for PukiWiki Official
if (! defined('ROLE_ENROLLEE')) define('ROLE_ENROLLEE', 4);
if (! defined('ROLE_ADM_CONTENTS')) define('ROLE_ADM_CONTENTS', 3);
if (! defined('ROLE_ADM')) define('ROLE_ADM', 2);
if (! defined('ROLE_GUEST')) define('ROLE_GUEST', 0);
// ereg_grep
if (! defined('EREG_GREP_INVERT')) define('EREG_GREP_INVERT', PREG_GREP_INVERT);
// file_put_contents
if (! defined('FILE_APPEND')) define('FILE_APPEND', 8);
if (! defined('FILE_USE_INCLUDE_PATH')) define('FILE_USE_INCLUDE_PATH', 1);

?>
