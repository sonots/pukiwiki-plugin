<?php
/**
 * Accesslog
 *
 * @author     sonots
 * @license    http://www.gnu.org/licenses/gpl.html GPL v2
 * @version    $Id: accesslog.inc.php,v 1.5 2008-04-13 11:14:46 sonots $
 * @package    plugin
 */

//error_reporting(E_ALL);
if (defined('INIT_DIR') & file_exists(INIT_DIR . 'accesslog.ini.php')) { // Plus!
    include_once(INIT_DIR . 'accesslog.ini.php');
} elseif (file_exists(DATA_HOME . 'init/accesslog.ini.php')) { // Official
    include_once(DATA_HOME . 'init/accesslog.ini.php');
}

if (! defined('PLUGIN_ACCESSLOG_FILENAME')) {
    define('PLUGIN_ACCESSLOG_FILENAME', 
           (defined('LOG_DIR') ? LOG_DIR : CACHE_DIR) . 'accesslog.txt'); // LOG_DIR (Plus!)
}
if (! defined('PLUGIN_ACCESSLOG_SORTABLETABLE_URL')) {
    define('PLUGIN_ACCESSLOG_SORTABLETABLE_URL', 
           (defined('SKIN_URI') ? SKIN_URI : SKIN_DIR) . 'sortabletable.js'); // SKIN_URI (Plus!)
}
if (! defined('PLUGIN_ACCESSLOG_KEEPLOG_DAYS')) {
    define('PLUGIN_ACCESSLOG_KEEPLOG_DAYS', 20);
}
if (! defined('PLUGIN_ACCESSLOG_EXCEPT_HOST')) {
    define('PLUGIN_ACCESSLOG_EXCEPT_HOST', 'crawl');
}
if (! defined('PLUGIN_ACCESSLOG_EXCEPT_AGENT')) {
    define('PLUGIN_ACCESSLOG_EXCEPT_AGENT', 'Googlebot|Twiceler|yetibot|Hatena|http\:\/\/');
}
if (! isset($GLOBALS['PLUGIN_ACCESSLOG_TABLE_ORDER'])) {
    $GLOBALS['PLUGIN_ACCESSLOG_TABLE_ORDER'] =  array('time', 'ip', 'host', 'page', 'cmd', 'referer', 'agent');
}
// Debug
if (! defined('PLUGIN_ACCESSLOG_THROUGH_IF_ADMIN')) {
    define('PLUGIN_ACCESSLOG_THROUGH_IF_ADMIN', TRUE);
}
if (! defined('PLUGIN_ACCESSLOG_USE_SESSION')) {
    define('PLUGIN_ACCESSLOG_USE_SESSION', TRUE);
}

class PluginAccesslog
{
    function action()
    {
        global $vars;
        $pass = (isset($vars['pass']) ? $vars['pass'] : NULL);
        if (PluginAccesslog::is_admin($pass, PLUGIN_ACCESSLOG_USE_SESSION, PLUGIN_ACCESSLOG_THROUGH_IF_ADMIN)) {
            $logfile = isset($vars['logfile']) ? $vars['logfile'] : PLUGIN_ACCESSLOG_FILENAME;
            $body = PluginAccesslog::show_logfile_listbox($logfile);
            $body .= PluginAccesslog::show_log($logfile);
        } else {
            $body = PluginAccesslog::get_form();
        }
        return array('msg'=>'Accesslog', 'body'=>$body);
    }
    
    function inline()
    {
        PluginAccesslog::write();
        return '';
    }

    function show_logfile_listbox($current = PLUGIN_ACCESSLOG_FILENAME)
    {
        $form = '<form action="' . get_script_uri() . '?cmd=accesslog" method="post">';
        $form .= '<div>' . "\n";
        $form .= ' <input type="hidden" name="pcmd" value="show" />' . "\n";
        $form .= ' <input type="hidden" name="pass" value="' . $GLOBALS['vars']['pass'] . '" />' . "\n";
        $form .= ' <select name="logfile">' . "\n";

        $logfile = PLUGIN_ACCESSLOG_FILENAME;
        $form .= '  <option value="' . $logfile . '"' . 
            ($current == $logfile ? ' selected="selected"' : '') .
            '>' . basename($logfile) . '</option>' . "\n";

        for ($i = 1; $i <= PLUGIN_ACCESSLOG_KEEPLOG_DAYS; $i++) {
            $logfile = htmlspecialchars(PLUGIN_ACCESSLOG_FILENAME . '.' . $i);
            $form .= '  <option value="' . $logfile . '"' . 
                ($current == $logfile ? ' selected="selected"' : '') .
                '>' . basename($logfile) . '</option>' . "\n";
        }

        $form .= ' </select>' . "\n";
        $form .= ' <input type="submit" name="submit" value="Submit" />' . "\n";
        $form .= '</div>' . "\n";
        $form .= '</form>' . "\n";
        return $form;
    }
    function show_log($logfile = PLUGIN_ACCESSLOG_FILENAME)
    {
        $labels = array
            (
             'time'    => _('Time'), 
             'ip'      => _('IP'), 
             'host'    => _('Host'), 
             'referer' => _('Referer'), 
             'agent'   => _('User Agent'),
             'page'    => _('Page'),
             'cmd'     => _('Cmd'), 
             );
        $sort_types = array
            (
             'time'    => 'String', 
             'ip'      => 'String', 
             'host'    => 'String', 
             'referer' => 'String', 
             'agent'   => 'String',
             'page'    => 'String', 
             'cmd'     => 'String', 
             );
        $table_id = 'accesslog';
        $ret = '';
    
        if (($lines = file($logfile)) === FALSE) {
            $ret = '<div>The log file, ' . $logfile . ' , does not exist.</div>';
            return $ret;
        }
        $logdate = rtrim(array_shift($lines));
        if ($logdate != '') {
            $ret .= '<h2>' . htmlspecialchars($logdate) . '</h2>' . "\n";
        }

        $ret .= '<div class="ie5"><table id="' . $table_id . '" class="style_table" cellspacing="1" border="0">' . "\n";
        $ret .= '<thead>' . "\n";
        $ret .= ' <tr>';
        foreach ($GLOBALS['PLUGIN_ACCESSLOG_TABLE_ORDER'] as $key) {
            $ret .= '<td class="style_td">' . $labels[$key] . '</td>';
        }
        $ret .= '</tr>' . "\n";
        $ret .= '</thead>' . "\n";
    
        $ret .= '<tbody>' . "\n";
        foreach ($lines as $line) {
            $line = rtrim($line);
            $logdata = unserialize($line);
            $logdata['referer'] = PluginAccesslog::analyze_referer($logdata['referer']);
            if ($logdata['referer'] === NULL) continue;
            $ret .= ' <tr>';
            foreach ($GLOBALS['PLUGIN_ACCESSLOG_TABLE_ORDER'] as $key) {
                $ret .= '<td class="style_td">' . $logdata[$key] . '</td>'; 
            }
            $ret .= '</tr>' . "\n";
        }
        $ret .= '</tbody>' . "\n";
        $ret .= '</table></div>' . "\n";
    
        // sortabletable.js
        $sorts = array();
        foreach ($GLOBALS['PLUGIN_ACCESSLOG_TABLE_ORDER'] as $key) {
            $sorts[] = $sort_types[$key];
        }
        global $head_tags;
        $head_tags[] = ' <script type="text/javascript" charset="utf-8" src="' . PLUGIN_ACCESSLOG_SORTABLETABLE_URL . '"></script>';
        $ret .= '<script type="text/javascript">' . "\n";
        $ret .= '<!-- <![CDATA[' . "\n";
        $ret .= 'var st = new SortableTable(document.getElementById("' . $table_id . '"),["' . implode('","',$sorts) . '"]);' . "\n";
        $ret .= '//]]>-->' . "\n";
        $ret .= '</script>' . "\n";
        return $ret;
    }

    /**
     * Analyze referer url and return the analyzed result
     *
     * @param string $referer url
     * @return string a link
     */
    function analyze_referer($referer)
    {
        $parsed = parse_url($referer);
        // ToDo: Use InterWiki page as a kind of dictionary
        switch ($parsed['host']) {
        case 'www.google.co.jp':
        case 'www.google.com':
            parse_str($parsed['query'], $queries);
            $q = rawurldecode($queries['q']);
            // ToDo: conv into UTF-8
            $linkstr = 'google:' . htmlspecialchars($q);
            return '<a href=' . $referer . '>' . $linkstr . '</a>';
            break;
        case 'pukiwiki.sourceforge.jp':
            parse_str($parsed['query'], $queries);
            if (count($queries) === 1) {
                $str = rawurldecode($parsed['query']);
            } elseif ($queries['cmd'] === 'read') {
                $str = rawurldecode($queries['page']);
            } else {
                $queries = array_map('rawurldecode', $queries);
                $str = '';
                foreach($queries as $key => $val) {
                    $str .= ($key . '=' . $val . '&');
                }
            }
            $linkstr = 'pukiwiki:' . htmlspecialchars($str);
            return '<a href=' . $referer . '>' . $linkstr . '</a>';
            break;
        case 'lsx.sourceforge.jp'://$_SERVER['SERVER_NAME']:
            return NULL;
        default:
            return htmlspecialchars($referer);
            break;
        }
    }
    /**
     * Get form
     *
     * @param $msg error message or some messages
     * @global $vars;
     * @return string
     */
    function get_form($message = "")
    {
        global $vars;
        $form = array();
        $form[] = '<form action="' . get_script_uri() . '?cmd=accesslog" method="post">';
        $form[] = '<div>';
        $form[] = ' <input type="hidden" name="pcmd" value="show" />';
        $form[] = ' <input type="password" name="pass" size="24" value="" /> ' . _('Admin Password') . '<br />';
        $form[] = ' <input type="submit" name="submit" value="Submit" /><br />';
        $form[] = '</div>';
        $form[] = '</form>';
        $form = implode("\n", $form);
   
        if ($message != '') {
            $message = '<p><b>' . htmlspecialchars($message) . '</b></p>';
        }
        return $message . $form;
    }

    function write()
    {
        if (PluginAccesslog::is_admin(NULL, PLUGIN_ACCESSLOG_USE_SESSION, PLUGIN_ACCESSLOG_THROUGH_IF_ADMIN)) return;

        global $vars, $defaultpage;
        $page = isset($vars['refer']) ? $vars['refer'] :
            (isset($vars['page']) ? $vars['page'] : $defaultpage);
        $cmd  = isset($vars['cmd']) ? $vars['cmd'] : (isset($vars['plugin']) ? $vars['plugin'] : '');

        // logdata format
        $logdata = array();
        $logdata['time']    = strftime('%y/%m/%d %H:%M:%S');
        $logdata['ip']      = $_SERVER['REMOTE_ADDR'];
        $logdata['host']    = isset($_SERVER['REMOTE_HOST']) ? $_SERVER['REMOTE_HOST'] : gethostbyaddr($_SERVER['REMOTE_ADDR']);
        $logdata['referer'] = $_SERVER['HTTP_REFERER'];
        $logdata['agent']   = $_SERVER['HTTP_USER_AGENT'];
        $logdata['page']    = $page;
        $logdata['cmd']     = $cmd;
        $line = serialize($logdata) . "\n";

        if (ereg(PLUGIN_ACCESSLOG_EXCEPT_AGENT, $logdata['agent'])) {
            return TRUE;
        }
        if (ereg(PLUGIN_ACCESSLOG_EXCEPT_HOST, $logdata['host'])) {
            return TRUE;
        }

        $date = date('Ymd', time()); 
        // use localtime simply because time handling ways in pukiwiki plus! and official are different. 
        if (file_exists(PLUGIN_ACCESSLOG_FILENAME)) {
            $logdate = rtrim(array_shift(file_head(PLUGIN_ACCESSLOG_FILENAME, 1)));
            if ($logdate != $date) {
                slide_rename(PLUGIN_ACCESSLOG_FILENAME, PLUGIN_ACCESSLOG_KEEPLOG_DAYS, '.%d');
                @move(PLUGIN_ACCESSLOG_FILENAME, PLUGIN_ACCESSLOG_FILENAME . '.1');
                file_put_contents(PLUGIN_ACCESSLOG_FILENAME, $date . "\n");
            }
        } else {
            file_put_contents(PLUGIN_ACCESSLOG_FILENAME, $date . "\n");
        }
        return file_put_contents(PLUGIN_ACCESSLOG_FILENAME, $line, FILE_APPEND);

    }

    /**
     * PukiWiki admin login with session
     *
     * PukiWiki API Extension
     *
     * @param string $pass Password. Use NULL when to get current session state. 
     * @param boolean $use_session Use Session log
     * @param boolean $use_authlog Use Auth log. 
     *  Username 'admin' is deemed to be Admin in PukiWiki Official. 
     *  PukiWiki Plus! has role management, roles ROLE_ADM and ROLE_ADM_CONTENTS are deemed to be Admin. 
     * @return boolean
     */
    function is_admin($pass = NULL, $use_session = FALSE, $use_authlog = FALSE)
    {
        $is_admin = FALSE;
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
            if ($is_admin) $_SESSION['pkwk_is_admin'] = TRUE;
        } else {
            global $vars;
            $vars['pkwk_is_admin'] = $is_admin;
        }
        return $is_admin;
    }
}

if (! function_exists('slide_rename')) {
    /**
     * Slide filenames (Count-up)
     *
     * PHP Extension
     *
     * @param $basename base file name
     * @param $max max number
     * @param $extfmt extension format
     * @return void
     */
    function slide_rename($basename, $max, $extfmt = '.%d') {
        for ($i = $max - 1; $i >= 1; $i--) {
            if (file_exists($basename . sprintf($extfmt, $i))) {
                $max = $i;
                break;
            }
        }
        for ($i = $max; $i >= 1; $i--) {
            @move($basename . sprintf($extfmt, $i), $basename . sprintf($extfmt, $i+1));
        }
    }
}
if (! function_exists('move')) {
    /**
     * Move a file (rename does not overwrite if $newname exists on Win)
     *
     * PHP Extension
     *
     * @param string $oldname
     * @param string $newname
     * @return boolean
     */
    function move($oldname, $newname) {
        if (! rename($oldname, $newname)) {
            if (copy ($oldname, $newname)) {
                unlink($oldname);
                return TRUE;
            }
            return FALSE;
        }
        return TRUE;
    }
}
if (! function_exists('file_put_contents')) {
    /**
     * Write a string to a file (PHP5 has this function)
     *
     * PHP Compat
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
if (! function_exists('_')) {
    /**
     * PHP Compat
     *
     * @param $str string
     * @return string
     */
    function &_($str)
    {
        return $str;
    }
}

function plugin_accesslog_init()
{
    global $plugin_accesslog_name;
    if (class_exists('PluginAccesslogUnitTest')) {
        $plugin_accesslog_name = 'PluginAccesslogUnitTest';
    } elseif (class_exists('PluginAccesslogUser')) {
        $plugin_accesslog_name = 'PluginAccesslogUser';
    } else {
        $plugin_accesslog_name = 'PluginAccesslog';
    }
}

function plugin_accesslog_action()
{
    global $plugin_accesslog, $plugin_accesslog_name;
    $plugin_accesslog = new $plugin_accesslog_name();
    return call_user_func(array(&$plugin_accesslog, 'action'));
}

function plugin_accesslog_inline()
{
    global $plugin_accesslog, $plugin_accesslog_name;
    $plugin_accesslog = new $plugin_accesslog_name();
    $args = func_get_args();
    return call_user_func_array(array(&$plugin_accesslog, 'inline'), $args);
}

?>
