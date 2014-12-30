<?php
/**
 * Dynamic Wiki Pages into Static HTMLs
 * 
 * @author     sonots
 * @license    http://www.gnu.org/licenses/gpl.html GPL v2
 * @link       http://lsx.sourceforge.jp/?Plugin%2Fstatichtml.inc.php
 * @version    $Id: statichtml.inc.php,v 1.4 2008-08-28 16:53:22Z sonots $
 * @package    plugin
 * @uses       rewritemap plugin
 */

// Debug
if (! defined('PLUGIN_STATICHTML_DEBUG')) {
    define('PLUGIN_STATICHTML_DEBUG', FALSE);
}
if (! defined('PLUGIN_STATICHTML_USE_SESSION')) {
    define('PLUGIN_STATICHTML_USE_SESSION', TRUE);
}
if (! defined('PLUGIN_STATICHTML_THROUGH_IF_ADMIN')) {
    define('PLUGIN_STATICHTML_THROUGH_IF_ADMIN', TRUE);
}
if (! defined('PLUGIN_STATICHTML_DUMP_PAGEWRITE')) {
    define('PLUGIN_STATICHTML_DUMP_PAGEWRITE', TRUE);
}
if (! defined('PLUGIN_STATICHTML_DUMP_ONREAD')) {
    define('PLUGIN_STATICHTML_DUMP_ONREAD', FALSE);
}
if (! defined('PLUGIN_STATICHTML_REDIRECT_ONREAD')) {
    define('PLUGIN_STATICHTML_REDIRECT_ONREAD', TRUE);
}

/**
 *  statichtml plugin class
 *
 *  @author     sonots
 *  @license    http://www.gnu.org/licenses/gpl.html GPL v2
 *  @link       http://lsx.sourceforge.jp/?Plugin%2Fstatichtml.inc.php
 */
class PluginStatichtml
{
    function PluginStatichtml()
    {
        // static
        static $CONF = array();
        $this->CONF = &$CONF;
        if (empty($this->CONF)) {
            global $whatsnew, $whatsdeleted;
            $this->CONF['DUMPDIR']       = '';
            $this->CONF['POSTFIX']       = '.html';
            $this->CONF['SPECIAL_PAGES'] = array($whatsnew, $whatsdeleted);
            $this->CONF['readauth']      = TRUE;
            $this->CONF['username']      = '';
            $this->CONF['userpass']      = '';
            // config for action plugin
            $this->CONF['ADMINONLY']     = TRUE;
            $this->CONF['WAITTIME']      = 200000; // waittime for batch dumping
            $this->CONF['overwrite']     = FALSE;  // force to overwrite
        }
        static $default_action_options = array();
        $this->default_action_options = &$default_action_options;
        if (empty($this->default_action_options)) {
            $this->default_action_options['filter']     = '';
            $this->default_action_options['page']       = '';
            $this->default_action_options['overwrite']  = FALSE;
        }

        // init
        $this->action_options  = $this->default_action_options;
        $this->action_view     = new PluginStatichtmlActionView($this);
    }

    // static
    var $CONF;
    var $default_action_options;
    // var
    var $error = '';
    var $plugin = 'statichtml';
    var $action_options;
    var $action_view;

    /**
     * Action Plugin Main Function
     */
    function action()
    {
        set_time_limit(0);
        global $vars;
        $this->set_action_options($vars);
        if (isset($vars['pcmd']) && $vars['pcmd'] == 'dump') {
            if (! $this->CONF['ADMINONLY'] ||
                is_admin($vars['pass'], PLUGIN_STATICHTML_USE_SESSION, 
                         PLUGIN_STATICHTML_THROUGH_IF_ADMIN)) {
                if ($this->action_options['page'] != '') {
                    $pages = (array)$this->action_options['page'];
                } else {
                    $pages = get_existpages();
                    if ($this->action_options['filter'] != '') {
                        $pages = ereg_grep($this->action_options['filter'], $pages);
                    }
                }
                $msg = $this->dump_pages($pages);
            } else {
                $msg = "<p><b>The password is wrong. </b></p>\n";
            }
        } else {
            $msg = "";
        }
        $body = $this->action_view->get_form($msg);
        return array('msg'=>'Dump PukiWiki Output to HTML', 'body'=>$body);
    }
    
    /**
     * Set action_options using $vars 
     *
     * PukiWiki Extension
     *
     * @param array &$args argument options (default $vars)
     * @return void
     */
    function &set_action_options(&$args)
    {
        if (is_null($args)) $args = &$GLOBALS['vars'];
        foreach ($this->action_options as $key => $val) {
            if (is_bool($val)) { // radio
                $this->action_options[$key] = isset($args[$key]);
            } elseif (isset($args[$key])) {
                $this->action_options[$key] = $args[$key];
            }
        }
        $this->CONF['overwrite'] = $this->action_options['overwrite'];
    }

    /**
     * Dump pages
     *
     * @param array &$pages
     * @retrun string message
     */
    function dump_pages(&$pages)
    {
        $files = array();
        $msg = '';
        foreach ($pages as $page) {
            $file = $this->get_dump_filename($page);
            $url  = $this->get_dump_url($page);
            $dump = $this->dump_page($page, $file, $this->CONF['overwrite']);

            if ($dump === TRUE) {
                $msg .= '<a href="' . $url . '">' . $file . '</a><br />';
            } elseif ($dump === -1) {
                $msg .= '<a href="' . $url . '">' . $file . '</a> already new<br />';
            } elseif ($dump === -2) {
                $msg .= '<a href="' . $url . '">' . $file . '</a> is read-restricted<br />';
            } elseif ($this->error != "") { 
                $msg .= '<a href="' . $url . '">' . $file . '</a> ' . htmlspecialchars($this->error) . '<br />';
            }
        }
        return '<p>' . $msg . '</p>';
    }

    /**
     * Dump the PukiWiki output of a page into a html file
     *
     * @param string $page Pagename
     * @param string $file Filename to be dumped. Default is computed from $page. 
     * @param boolean $overwrite Force to overwrite. Default overwrites if $page is newer than $file
     * @param boolean $notimestamp Do not change timestamp for dumped file
     * @return mixed
     *   TRUE : Success
     *   FALSE: Failure
     *   -1   : It is already up2date
     *   -2   : Exit by read-restriction
     *   -3   : Exit because statichtml USER_AGENT called statichtml again (infinite loop)
     */
    function dump_page($page, $file = null, $overwrite = FALSE, $notimestamp = FALSE)
    {
        // statichtml USER_AGENT should not call statichtml again (avoid infinite loop)
        if (isset($GLOBALS['vars'][$this->plugin])) {
            return -3;
        }

        // Initialization
        if (! isset($file)) {
            $file = $this->get_dump_filename($page);
        }
        if (! is_page($page)) {
            if (file_exists($file)) {
                pkwk_chown($file);
                @unlink($file);
            }
            return TRUE;
		}
        // Up2date?
        if (! $overwrite && ! is_page_newer($page, $file)) {
            return -1;
        }

        // Try to create dir
        $dir = dirname($file);
        if (isset($GLOBALS['PLUGIN_STATICHTML_MKDIR_CGI'])) {
            $error = file_get_contents($GLOBALS['PLUGIN_STATICHTML_MKDIR_CGI'] . '&mode=0777&dir=' . $dir);
            if ($error != '1') {
                $this->error = 'Failed to create ' . $dir . ' directory.'; return FALSE;
            }
        } else {
            if (r_mkdir($dir) === FALSE) {
                $this->error = 'Failed to create ' . $dir . ' directory.'; return FALSE;
            }
        }
        
        // Get contents
        if (is_read_auth($page) && ! $this->CONF['readauth']) {
            return -2; // Do not read read-restriction pages
        }
        if (($contents = $this->http_pkwk_output($page)) === FALSE) {
            return -2; // HTTP GET failure (mostly because of read-restriction)
        }

        // Write
        $filemtime = (file_exists($file) && $notimestamp) ? filemtime($file) : FALSE;
        if (! file_put_contents($file, $contents)) {
            $this->error = 'Failed to create ' . $file; return FALSE;
        }
        if ($notimestamp) pkwk_touch_file($file, $filemtime, $filemtime);

        return TRUE;
    }

    /**
     * Get the PukiWiki output externally via http
     *
     * @param string $page
     * @return string pukiwiki skin output
     */
    function http_pkwk_output($page)
    {
        usleep($this->CONF['WAITTIME']);
        // edit.php
        if ((defined('EDIT_OK') && EDIT_OK) && defined('PKWK_SCRIPT_FILENAME')) {
             $script = get_pkwk_topurl() .
                 ($GLOBALS['script_directory_index'] == PKWK_SCRIPT_FILENAME ? '' : PKWK_SCRIPT_FILENAME); 
        } else {
             $script = get_script_uri();
        }
        $url = $script . '?cmd=read&page=' . rawurlencode($page); 
        // cmd=read to support old versions such as 1.4.3
        $url .= '&statichtml';
        // cmd=read to support old versions such as 1.4.3
        $url .= '&' . $this->plugin;
        // $this->plugin flag can be used like USER_AGENT to know agent is the statichtml plugin.
        if (is_includable('HTTP/Request.php') && $this->CONF['username'] != '') {
            require_once('HTTP/Request.php');
            $req = new HTTP_Request($url);
            $req->setMethod(HTTP_REQUEST_METHOD_GET);
            //$req->addPostData('pass', $GLOBALS['ADMINPASS']);
            $req->setBasicAuth($this->CONF['username'], $this->CONF['userpass']);
            $response = $req->sendRequest();
            // ToDo: How to find BasicAuth was failed? 
            if (PEAR::isError($response)) {
                //echo $response->getMessage();
                return FALSE;
            } else {
                $html = $req->getResponseBody();
            }
        } else {
            $html = file_get_contents($url);
        }
        return $html;
    }

    /**
     * Encode a multibyte characters keeping directory tree structure
     * 
     * Example)
     * UTF8/UTF8/test -> F7-6F-G9-/F7-6F-G9-/test
     *
     * @param string $str
     * @return string
     * @see decode
     */
    function encode($str)
    {
        $encode = rawurlencode($str);
        $encode = str_replace('-', '%2D', $encode); # rawurlencode(chr(hexdec('2D'))) == '-'
        $encode = str_replace('%2F', '/', $encode);
        $encode = preg_replace('/%([0-9a-f][0-9a-f])/i', '\\1-', $encode);
        return $encode;
    }
    /**
     * Decode an encoded string by encode()
     *
     * @param string $encode
     * @return string
     * @see encode
     */
    function decode($encode)
    {
        $decode = preg_replace('/([0-9a-f][0-9a-f])-/i', '%\\1', $encode);
        $decode = str_replace('%2D', '-', $decode);
        $decode = rawurldecode($decode);
        return $decode;
    }

    /**
     * Get the filename of dumped html
     *
     * @param string $page
     * @return string 
     */
    function get_dump_filename($page)
    {
        if ($page === $GLOBALS['defaultpage'] || $page === '') {
            $file = 'index';
        } else {
            $file = $this->encode($page);
        }
        return $this->CONF['DUMPDIR'] . $file . $this->CONF['POSTFIX'];
    }

    /**
     * Get the url of dumped html
     *
     * @param string $page
     * @param string $topurl
     * @return string 
     */
    function get_dump_url($page, $topurl = '')
    {
        if ($topurl === '') $topurl = get_pkwk_topurl();
        if ($page === $GLOBALS['defaultpage'] || $page === '') {
            return $topurl;
        }
        return $topurl . $this->get_dump_filename($page);
    }

    /**
     * Experiment: On Read Plugin Main Function
     *
     * @param string $page
     * @return void
     */
    function onread()
    {
        $args = func_get_args();
        $page = $args[0];
        global $vars;
        if (PLUGIN_STATICHTML_DUMP_ONREAD && ! defined('EDIT_OK') && 
            $vars['cmd'] == 'read' && ! isset($vars[$this->plugin])) {
            if (! $this->dump_page($page, null, TRUE)) {
                die_message($this->plugin . '() failure: ' . htmlspecialchars($this->error));
            }
        }
        if (PLUGIN_STATICHTML_REDIRECT_ONREAD && ! defined('EDIT_OK') && 
            $vars['cmd'] == 'read' && ! isset($vars[$this->plugin])) {
            $htmlfile = $this->get_dump_filename($page);
            if (file_exists($htmlfile)) {
                header('HTTP/1.0 301 Moved Permanently');
                header('Location: ' . $this->get_dump_url($page));
                exit;
            }
        }
    }

    /**
     * Experiment: Write After Plugin Main Function
     *
     * @param string $page
     * @param string &$postdata
     * @param string $notimestamp
     * @param boolean &$oldpostdata
     * @return void or exit
     */
    function write_after()
    {
        if (! PLUGIN_STATICHTML_DUMP_PAGEWRITE) return;
        global $vars;
        $args = func_get_args();
        $page = $args[0]; 
        if (isset($args[2])) $notimestamp = $args[2];
        if ($vars['page'] != $page) return;
        foreach ($this->CONF['SPECIAL_PAGES'] as $spage) {
            if (is_page($spage)) {
                $this->dump_page($spage, null, TRUE, $notimestamp);
            }
        }
        if (! $this->dump_page($page, null, TRUE, $notimestamp)) {
            die_message($this->plugin . '() failure: ' . htmlspecialchars($this->error));
        }
    }
}

/**
 *  statichtml plugin view class for action plugin
 *
 *  @author     sonots
 *  @license    http://www.gnu.org/licenses/gpl.html GPL v2
 *  @link       http://lsx.sourceforge.jp/?Plugin%2Fstatichtml.inc.php
 */
class PluginStatichtmlActionView
{
    var $CONF;
    var $action_options;
    var $model;

    function PluginStatichtmlActionView(&$model)
    {
        $this->CONF = &$model->CONF;
        $this->action_options = &$model->action_options;
        $this->model = &$model;
    }

    function get_form($msg = "")
    {
        foreach ($this->action_options as $key => $val) {
            ${$key} = $val;
        }
        static $true = ' checked="checked"';
        $overwrite   = $overwrite  ? $true : '';

        $body = $msg;
        $body .= '<form action="' . get_script_uri() . '?cmd=' . $this->model->plugin . '" method="post">' . "\n";
        $body .= '<div>' . "\n";
        $body .= ' <input type="hidden"   name="pcmd"  value="dump" />' . "\n";
        if ($this->CONF['ADMINONLY'] && ! is_admin(null, PLUGIN_STATICHTML_USE_SESSION, PLUGIN_STATICHTML_THROUGH_IF_ADMIN)) {
            $body .= ' <input type="password" name="pass" size="12" value="" />Admin Pasword<br />' . "\n";
        }
        $body .= ' <input type="text"  name="filter" size="24" value="' . $filter . '" />Filter Pages by ereg (Leave blank for all)<br />' . "\n";
        $body .= ' <input type="text"  name="page" size="24" value="' . $page . '" />A Page<br />' . "\n";
        $body .= ' <input type="checkbox" name="overwrite" id="overwrite" value="1"' . $overwrite . ' /><label for="overwrite">Force to Overwrite</label><br />' . "\n";
        $body .= ' <input type="submit"   name="submit"   value="Submit" /><br />' . "\n";
        $body .= '</div>' . "\n";
        $body .= '</form>' . "\n";
        return $body;
    } 
}

////////// PukiWiki API Extension ///////////////////////////
if (! function_exists('is_admin')) {
    /**
     * PukiWiki admin login with session
     *
     * @param string $pass
     * @param boolean $use_session Use Session log
     * @param boolean $use_basicauth Use BasicAuth log
     * @return boolean
     */
    function is_admin($pass = null, $use_session = false, $use_basicauth = false)
    {
        $is_admin = FALSE;
        if ($use_basicauth) {
            if (is_callable(array('auth', 'check_role'))) { // Plus!
                $is_admin = ! auth::check_role('role_adm_contents');
            }
        }
        if (! $is_admin && isset($pass)) {
            $is_admin = function_exists('pkwk_login') ? pkwk_login($pass) : 
                md5($pass) === $GLOBALS['adminpass']; // 1.4.3
        }
        if ($use_session) {
            session_start();
            if ($is_admin) $_SESSION['is_admin'] = TRUE;
            return isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
        } else {
            return $is_admin;
        }
    }
}

if (! function_exists('get_pkwk_topurl')) {
    /**
     * Get PukiWiki Top URL (Base URI) (without index.php)
     *
     * @return string topurl
     */
    function get_pkwk_topurl()
    {
        static $topurl = '';
        if ($topurl !== '') return $topurl;
        $topurl = get_script_uri();
        if (($pos = strrpos($topurl, '/')) !== FALSE) {
            $topurl = substr($topurl, 0, $pos + 1);
        }
        return $topurl;
    }
}

if (! function_exists('is_read_auth')) {
    /**
     * Check if the page requires the read-authentication
     *
     * @param $page pagename
     * @param $user check if it is possible for this page to be read by the user
     * @return boolean
     */
    function is_read_auth($page, $user = '')
    {
        global $read_auth, $read_auth_pages, $auth_method_type;
        if (! $read_auth) {
            return FALSE;
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
                    return TRUE;
                }
            }
        }
        return FALSE;
    }
}

if (! function_exists('is_page_newer')) {
    /**
     * Check if the page timestamp is newer than the file timestamp
     *
     * PukiWiki API Extension
     *
     * @param string $page pagename
     * @param string $file filename
     * @param bool $ignore_notimestamp Ignore notimestamp edit and see the real time editted
     * @return boolean
     */
    function is_page_newer($page, $file, $ignore_notimestamp = TRUE)
    {
        $filestamp = file_exists($file) ? filemtime($file) : 0;
        if ($ignore_notimestamp) { // See the diff file. PukiWiki Trick. 
            $pagestamp  = is_page($page) ? filemtime(DIFF_DIR . encode($page) . '.txt') : 0;
        } else {
            $pagestamp  = is_page($page) ? filemtime(get_filename($page)) : 0;
        }    
        return $pagestamp > $filestamp;
    }
}
////////// PHP API Extension ////////////////////////////////
if (! function_exists('ereg_grep')) {   
    /**
     * Grep an array by ereg expression
     *
     * @static
     * @param string $pattern
     * @param array $input
     * @param int $flags
     * @return array
     */
    if (! defined('EREG_GREP_INVERT')) define('EREG_GREP_INVERT', PREG_GREP_INVERT);
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
}

if (! function_exists('is_includable')) {
    /**
     * Check if file is includable
     *
     * @param string $filename
     * @param boolean $returnpaths return all paths where $filename is includable
     * @return boolean (or array if $returnpaths is true)
     */
    function is_includable($filename, $returnpaths = false) {
        $include_paths = explode(PATH_SEPARATOR, ini_get('include_path'));
        foreach ($include_paths as $path) {
            $include = $path . DIRECTORY_SEPARATOR . $filename;
            if (is_file($include) && is_readable($include)) {
                if ($returnpaths == true) {
                    $includable_paths[] = $path;
                } else {
                    return true;
                }
            }
        }
        return (isset($includeable_paths) && $returnpaths == true) ?
            $includeable_paths : false;
    }
}

if (! function_exists('r_mkdir')) {
    /**
     * mkdir recursively (mkdir of PHP5 has recursive flag)
     *
     * @param string $dir
     * @param int $mode
     * @return boolean success or failure
     */
    function r_mkdir($dir, $mode = 0755)
    {
        if (is_dir($dir) || @mkdir($dir,$mode)) return TRUE;
        if (! r_mkdir(dirname($dir),$mode)) return FALSE;
        return @mkdir($dir,$mode);
    }
}

if (! function_exists('file_put_contents')) {
    /**
     * Write a string to a file (PHP5 has this function)
     *
     * @param string $filename
     * @param string $data
     * @param int $flags
     * @return int the amount of bytes that were written to the file, or FALSE if failure
     */
    if (! defined('FILE_APPEND')) define('FILE_APPEND', 8);
    if (! defined('FILE_USE_INCLUDE_PATH')) define('FILE_USE_INCLUDE_PATH', 1);
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
}


///////////////////////////////////////////
function plugin_statichtml_init()
{
    global $plugin_statichtml_name;
    if (class_exists('PluginStatichtmlUnitTest')) {
        $plugin_statichtml_name = 'PluginStatichtmlUnitTest';
    } elseif (class_exists('PluginStatichtmlUser')) {
        $plugin_statichtml_name = 'PluginStatichtmlUser';
    } else {
        $plugin_statichtml_name = 'PluginStatichtml';
    }
}

function plugin_statichtml_action()
{
    global $plugin_statichtml_name;
    $plugin_statichtml = new $plugin_statichtml_name();
    return $plugin_statichtml->action();
}

function plugin_statichtml_onread()
{
    global $plugin_statichtml_name; 
    $plugin_statichtml = new $plugin_statichtml_name();
    $args = func_get_args();
    return call_user_func_array(array(&$plugin_statichtml, 'onread'), $args);
}

function plugin_statichtml_write_after()
{
    global $plugin_statichtml_name; 
    $plugin_statichtml = new $plugin_statichtml_name();
    $args = func_get_args();
    return call_user_func_array(array(&$plugin_statichtml, 'write_after'), $args);
}

if (! defined('INIT_DIR')) // if not Plus! 
    if (file_exists(DATA_HOME . 'init/statichtml.ini.php')) 
        include_once(DATA_HOME . 'init/statichtml.ini.php');

?>
