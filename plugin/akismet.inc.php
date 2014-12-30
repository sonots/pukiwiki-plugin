<?php
/**
 *  Akismet Spamfilter Plugin
 *
 *  @author     sonots
 *  @license    http://www.gnu.org/licenses/gpl.html GPL v2
 *  @link       http://lsx.sourceforge.jp/?Plugin%2Fakismet.inc.php
 *  @version    $Id: akismet.inc.php, v 1.13 2008-07-26 13:06:36Z sonots $
 *  @package    plugin
 */

//error_reporting(E_ALL);
if (defined('INIT_DIR') & file_exists(INIT_DIR . 'akismet.ini.php')) { // Plus!
    include_once(INIT_DIR . 'akismet.ini.php');
} elseif (file_exists(DATA_HOME . 'init/akismet.ini.php')) { // Official
    include_once(DATA_HOME . 'init/akismet.ini.php');
}

// Initial settings
if (! defined('PLUGIN_AKISMET_API_KEY')) {
    define('PLUGIN_AKISMET_API_KEY', '');
}
if (! defined('PLUGIN_AKISMET_RECAPTCHA_PUBLIC_KEY')) {
    define('PLUGIN_AKISMET_RECAPTCHA_PUBLIC_KEY', '');
}
if (! defined('PLUGIN_AKISMET_RECAPTCHA_PRIVATE_KEY')) {
    define('PLUGIN_AKISMET_RECAPTCHA_PRIVATE_KEY', '');
}

// log settings
if (! defined('PLUGIN_AKISMET_SPAMLOG_FILENAME')) {
    define('PLUGIN_AKISMET_SPAMLOG_FILENAME', 
           (defined('LOG_DIR') ? LOG_DIR : CACHE_DIR) . 'spamlog.txt'); // LOG_DIR (Plus!)
}
if (! defined('PLUGIN_AKISMET_SPAMLOG_DETAIL')) {
    define('PLUGIN_AKISMET_SPAMLOG_DETAIL', FALSE);
}
if (! defined('PLUGIN_AKISMET_ONELOG_DAYS')) {
    define('PLUGIN_AKISMET_ONELOG_DAYS', 10);
}
if (! defined('PLUGIN_AKISMET_KEEPLOG')) {
    define('PLUGIN_AKISMET_KEEPLOG', 3);
}
if (! isset($GLOBALS['PLUGIN_AKISMET_TABLE_ORDER'])) {
    $GLOBALS['PLUGIN_AKISMET_TABLE_ORDER'] =  array('time', 'cmd', 'page', 'ip', 'host', 'agent', 'body');
}
if (! defined('PLUGIN_AKISMET_SORTABLETABLE_URL')) {
    define('PLUGIN_AKISMET_SORTABLETABLE_URL', 
           (defined('SKIN_URI') ? SKIN_URI : SKIN_DIR) . 'sortabletable.js'); // SKIN_URI (Plus!)
}

// Set FALSE to use recaptcha without akismet (no log will be taken with FALSE)
if (! defined('PLUGIN_AKISMET_USE_AKISMET')) {
    define('PLUGIN_AKISMET_USE_AKISMET', TRUE);
}
// Set FALSE to use akismet without recaptcha
if (! defined('PLUGIN_AKISMET_USE_RECAPTCHA')) {
    define('PLUGIN_AKISMET_USE_RECAPTCHA', TRUE);
}
// Do not spam filter POST via these plugins (SPAM filter works only for POST, not GET)
if (! defined('PLUGIN_AKISMET_IGNORE_PLUGINS')) {
    define('PLUGIN_AKISMET_IGNORE_PLUGINS', 'read,vote,vote2');
}

// Do not require to captcha if the user is known as human
if (! defined('PLUGIN_AKISMET_THROUGH_IF_ADMIN')) { // Plus!
    define('PLUGIN_AKISMET_THROUGH_IF_ADMIN', TRUE);
}
if (! defined('PLUGIN_AKISMET_THROUGH_IF_ENROLLEE')) { // Plus!
    define('PLUGIN_AKISMET_THROUGH_IF_ENROLLEE', FALSE);
}
if (! defined('PLUGIN_AKISMET_USE_SESSION')) {
    define('PLUGIN_AKISMET_USE_SESSION', TRUE);
}

// Debug
if (! defined('PLUGIN_AKISMET_AUTOPOST_AFTER_SUBMITHAM')) {
    define('PLUGIN_AKISMET_AUTOPOST_AFTER_SUBMITHAM', TRUE);
}
if (! defined('PLUGIN_AKISMET_RECAPTCHA_LOG')) {
    define('PLUGIN_AKISMET_RECAPTCHA_LOG', FALSE);
}

class PluginAkismet
{
    function action()
    {
        global $vars;
        if (isset($vars['submitHam'])) {
            return $this->submitham_action();
        } else {
            $logfile = isset($vars['logfile']) ? $vars['logfile'] : PLUGIN_AKISMET_SPAMLOG_FILENAME;
            $body = $this->show_logfile_listbox($logfile);
            $body .= $this->show_spamlog($logfile);
            return array('msg'=>_('Spam Log'), 'body'=>$body);
        }
    }
    
    function show_logfile_listbox($current = PLUGIN_AKISMET_SPAMLOG_FILENAME)
    {
        $form = '<form action="' . get_script_uri() . '?cmd=akismet" method="post">';
        $form .= '<div>' . "\n";
        $form .= ' <input type="hidden" name="pcmd" value="spamlog" />' . "\n";
        $form .= ' <select name="logfile">' . "\n";
        
        $logfile = PLUGIN_AKISMET_SPAMLOG_FILENAME;
        $form .= '  <option value="' . $logfile . '"' . 
            ($current == $logfile ? ' selected="selected"' : '') .
            '>' . basename($logfile) . '</option>' . "\n";
        
        for ($i = 1; $i <= PLUGIN_AKISMET_KEEPLOG; $i++) {
            $logfile = htmlspecialchars(PLUGIN_AKISMET_SPAMLOG_FILENAME . '.' . $i);
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

    function show_spamlog($logfile = PLUGIN_AKISMET_SPAMLOG_FILENAME)
    {
        $labels = array(
             'time'    => _('Time'), 
             'ip'      => _('IP'), 
             'host'    => _('Host'), 
             'agent'   => _('User Agent'),
             'page'    => _('Page'),
             'cmd'     => _('Cmd'), 
             'body'    => _('Body'), 
        );
        $sort_types = array(
             'time'    => 'String', 
             'ip'      => 'String', 
             'host'    => 'String', 
             'agent'   => 'String',
             'page'    => 'String', 
             'cmd'     => 'String', 
             'body'    => 'String', 
        );
        $table_id = 'akismet_spamlog';
        $ret = '';

        if (($lines = file($logfile)) === FALSE) {
            $ret = '<div>The log file, ' . $logfile . ' , does not exist.</div>';
            return $ret;
        }
        $logdate = rtrim(array_shift($lines));
        //if ($logdate != '') {
        //$ret .= '<h2>' . htmlspecialchars($logdate) . '</h2>' . "\n";
        //}

        $ret .= '<div class="ie5"><table id="' . $table_id . '" class="style_table" cellspacing="1" border="0">' . "\n";
        $ret .= '<thead>' . "\n";
        $ret .= ' <tr>';
        foreach ($GLOBALS['PLUGIN_AKISMET_TABLE_ORDER'] as $key) {
            $ret .= '<td class="style_td">' . $labels[$key] . '</td>';
        }
        $ret .= '</tr>' . "\n";
        $ret .= '</thead>' . "\n";

        $ret .= '<tbody>' . "\n";
        foreach ($lines as $line) {
            $line = rtrim($line);
            $logdata = unserialize($line);
            $logdata['body'] = str_replace('<br />', "\n", $logdata['body']);
            $ret .= ' <tr>';
            foreach ($GLOBALS['PLUGIN_AKISMET_TABLE_ORDER'] as $key) {
                $ret .= '<td class="style_td">' . htmlspecialchars($logdata[$key]) . '</td>'; 
            }
            $ret .= '</tr>' . "\n";
        }
        $ret .= '</tbody>' . "\n";
        $ret .= '</table></div>' . "\n";

        // sortabletable.js
        $sorts = array();
        foreach ($GLOBALS['PLUGIN_AKISMET_TABLE_ORDER'] as $key) {
            $sorts[] = $sort_types[$key];
        }
        global $head_tags;
        $head_tags[] = ' <script type="text/javascript" charset="utf-8" src="' . PLUGIN_AKISMET_SORTABLETABLE_URL . '"></script>';
        $ret .= '<script type="text/javascript">' . "\n";
        $ret .= '<!-- <![CDATA[' . "\n";
        $ret .= 'var st = new SortableTable(document.getElementById("' . $table_id . '"),["' . implode('","',$sorts) . '"]);' . "\n";
        $ret .= '//]]>-->' . "\n";
        $ret .= '</script>' . "\n";
        return $ret;
    }
    
    function submitham_action()
    {
        global $vars, $post, $get;

        $error = NULL;
        if (PLUGIN_AKISMET_USE_RECAPTCHA) {
            // was there a reCAPTCHA response?
            if (isset($post["recaptcha_response_field"])) {
                $resp = recaptcha_check_answer (PLUGIN_AKISMET_RECAPTCHA_PRIVATE_KEY,
                                                $_SERVER["REMOTE_ADDR"],
                                                $post["recaptcha_challenge_field"],
                                                $post["recaptcha_response_field"]);
                $error = $resp->error;
                $captcha_valid = $resp->is_valid;
            // If no response from reCAPTCHA, Assume as valid. 
            } else {
                $captcha_valid = TRUE;
                if (PLUGIN_AKISMET_RECAPTCHA_LOG) PluginAkismet::spamlog_write($vars, array('body'=>'reCaptcha invalid'), LOG_DIR . 'captchalog.txt');
            }
        }
        $comment = $vars['comment'];
        $vars    = $vars['vars'];
        if ($captcha_valid) {
            if (PLUGIN_AKISMET_RECAPTCHA_LOG) PluginAkismet::spamlog_write($vars, array('body'=>'break'), LOG_DIR . 'captchalog.txt');

            // Memorize the user is human because he could pass captcha
            $use_authlevel = PLUGIN_AKISMET_THROUGH_IF_ENROLLEE ? ROLE_AUTH :
                (PLUGIN_AKISMET_THROUGH_IF_ADMIN ? ROLE_ADM_CONTENTS : 0);
            is_human(TRUE, PLUGIN_AKISMET_USE_SESSION, $use_authlevel); // set to session

            // submitHam
            if (PLUGIN_AKISMET_USE_AKISMET) {
                $akismet = new Akismet(get_script_uri(), PLUGIN_AKISMET_API_KEY, $comment);
                $akismet->submitHam();
            }

            // autopost
            if (PLUGIN_AKISMET_AUTOPOST_AFTER_SUBMITHAM) {
                // throw to originally called plugin
                // refer lib/pukiwiki.php
                $cmd = isset($vars['cmd']) ? $vars['cmd'] : (isset($vars['plugin']) ? $vars['plugin'] : 'read');
                if (exist_plugin_action($cmd)) {
                    $post = $vars;
                    $get = array();
                    do_plugin_init($cmd);
                    return do_plugin_action($cmd);
                } else {
                    $msg = 'plugin=' . htmlspecialchars($cmd) . ' is not implemented.';
                    return array('msg'=>$msg,'body'=>$msg);
                }
            } else {
                $body = '<p>スパム取り消し報告を行いました。以下がスパムと判断された投稿内容です。再度投稿してください。</p>' . "\n";
                $body .= '<div class="ie5"><table class="style_table" cellspacing="1" border="0"><tbody>' . "\n";
                foreach ($vars as $key => $val) {
                    $body .= '<tr>' . "\n";
                    $body .= ' <td class="style_td">' . htmlspecialchars($key) . '<td>' . "\n";
                    $body .= ' <td class="style_td">' . htmlspecialchars($val) . '<td>' . "\n";
                    $body .= '</tr>' . "\n";
                }
                $body .= '</tbody></table></div>' . "\n";
                return array('msg'=>'キャプチャ認証', 'body'=>$body);
            }
        } else {
            $form = PluginAkismet::get_captcha_form($vars, $comment, $error);
            return array('msg'=>'キャプチャ認証', 'body'=>$form);
        }
    }

    // obsolete: should not be used
    function write_before()
    {
        global $vars;
        $args        = func_get_args();
        $page        = &$args[0];
        $postdata    = &$args[1];
        $notimestamp = &$args[2];
        $oldpostdata = &$args[3];
        $optargs     = &$args[4];

        $postlines = explode("\n", $postdata);
        $oldlines  = explode("\n", $oldpostdata);
        $difflines = array_diff($postlines, $oldlines);
        $body      = implode("\n", $difflines);
        $comment = array(
             'author'       => '',
             'email'        => '',
             'website'      => '',
             'body'         => $body,
             'permalink'    => '',
             'user_ip'      => $_SERVER['REMOTE_ADDR'],
             'user_agent'   => $_SERVER['HTTP_USER_AGENT'],
        );
        return PluginAkismet::spamfilter($comment);
    }

    // static
    function spamfilter($comment = null)
    {
        global $vars, $defaultpage;
        // Through if GET (Check only POST)
        if ($_SERVER['REQUEST_METHOD'] === 'GET') return;
        // Through if POST is from akismet plugin (submitHam)
        if (isset($vars['cmd']) && $vars['cmd'] == 'akismet') return;
        // Through if in IGNORE list
        $cmd = isset($vars['cmd']) ? $vars['cmd'] : (isset($vars['plugin']) ? $vars['plugin'] : 'read');
        if (defined('PLUGIN_AKISMET_IGNORE_PLUGINS')) {
            if (in_array($cmd, explode(',', PLUGIN_AKISMET_IGNORE_PLUGINS))) return;
        }

        // Through if already known he is a human
        $use_authlevel = PLUGIN_AKISMET_THROUGH_IF_ENROLLEE ? ROLE_AUTH :
            (PLUGIN_AKISMET_THROUGH_IF_ADMIN ? ROLE_ADM_CONTENTS : 0);
        if (is_human(NULL, PLUGIN_AKISMET_USE_SESSION, $use_authlevel)) return;

        // Initialize $comment
        if (! isset($comment)) {
            // special case (now only supports edit plugin)
            if ($vars['cmd'] === 'edit' || $vars['plugin'] === 'edit') {
                $body = $vars['msg'];
            } else {
                $body = implode("\n", $vars);
            }
            $comment = array(
                'author'       => '',
                'email'        => '',
                'website'      => '',
                'body'         => $body,
                'permalink'    => '',
                'user_ip'      => $_SERVER['REMOTE_ADDR'],
                'user_agent'   => $_SERVER['HTTP_USER_AGENT'],
            );
        }

        $is_spam = TRUE;
        if (PLUGIN_AKISMET_USE_AKISMET) {
            // Through if no body (Akismet recognizes as a spam if no body)
            if ($comment['body'] == '') return;

            // instantiate an instance of the class
            $akismet = new Akismet(get_script_uri(), PLUGIN_AKISMET_API_KEY, $comment);
            // test for errors
            if($akismet->errorsExist()) { // returns TRUE if any errors exist
                if($akismet->isError('AKISMET_INVALID_KEY')) {
                    die_message('akismet : APIキーが不正です.');
                } elseif($akismet->isError('AKISMET_RESPONSE_FAILED')) {
                    //die_message('akismet : レスポンスの取得に失敗しました');
                } elseif($akismet->isError('AKISMET_SERVER_NOT_FOUND')) {
                    //die_message('akismet : サーバへの接続に失敗しました.');
                }
                $is_spam = FALSE; // through if akismet.com is not available.
            } else {
                $is_spam = $akismet->isSpam();
            }

            if ($is_spam) {
                $detail = PLUGIN_AKISMET_SPAMLOG_DETAIL ? $comment : array();
                PluginAkismet::spamlog_write($vars, $detail, PLUGIN_AKISMET_SPAMLOG_FILENAME);
            }
        }
        if ($is_spam) {
            if (PLUGIN_AKISMET_RECAPTCHA_LOG) PluginAkismet::spamlog_write($vars, array('body'=>'hit'), LOG_DIR . 'captchalog.txt');
            $form = PluginAkismet::get_captcha_form($vars, $comment);
            // die_message('</strong>' . $form . '<strong>');
            $title = $page = 'キャプチャ認証';
            pkwk_common_headers();
            catbody($title, $page, $form);
            exit;
        }
    }

    // static
    function get_captcha_form(&$vars, &$comment, $error = null)
    {
        $form = '';
        $form .= '<form action="' . get_script_uri() . '" method="post">' . "\n";
        $form .= '<div>' . "\n";
        if (PLUGIN_AKISMET_USE_AKISMET) {
            $form .= ' 投稿はスパムと判断されました。スパム取り消し報告を行うには以下の２つの単語をタイプしてください。' . "\n";
        } else {
            $form .= ' キャプチャ認証を行います。以下の２つの単語をタイプしてください。' . "\n";
        }
        if (PLUGIN_AKISMET_USE_RECAPTCHA) {
            $form .= recaptcha_get_html(PLUGIN_AKISMET_RECAPTCHA_PUBLIC_KEY, $error);
        } else {
            if (isset($error)) {
                $form .= '<p>';
                $form .= 'reCAPTCHA error: ' . $error;
                $form .= '</p>';
            }
        }
        foreach ($comment as $key => $val) {
            $form .= ' <input type="hidden" name="comment[' . htmlspecialchars($key) . ']" value="' . htmlspecialchars($val) . '" />' . "\n";
        }
        foreach ($vars as $key => $val) {
            $form .= ' <input type="hidden" name="vars[' . htmlspecialchars($key) . ']" value="' . htmlspecialchars($val) . '" />' . "\n";
        }
        $form .= ' <input type="hidden" name="cmd" value="akismet">' . "\n";
        $form .= ' <input type="submit" name="submitHam" value="GO" /><br />' . "\n";
        $form .= '</div>' . "\n";
        $form .= '</form>' . "\n";
        return $form;
    }

    // static
    function spamlog_write($vars, $comment = array(), $filename = '')
    {
        if ($filename === '') $filename = PLUGIN_AKISMET_SPAMLOG_FILENAME;

        $page = isset($vars['refer']) ? $vars['refer'] :
            (isset($vars['page']) ? $vars['page'] : $defaultpage);
        $cmd  = isset($vars['cmd']) ? $vars['cmd'] : '';

        // logdata format
        $logdata = array();
        $logdata['time']  = strftime('%y/%m/%d %H:%M:%S');
        $logdata['ip']    = $_SERVER['REMOTE_ADDR'];
        $logdata['host']  = isset($_SERVER['REMOTE_HOST']) ? $_SERVER['REMOTE_HOST'] : gethostbyaddr($_SERVER['REMOTE_ADDR']);
        $logdata['agent'] = $_SERVER['HTTP_USER_AGENT'];
        $logdata['page']  = $page;
        $logdata['cmd']   = $cmd;
        $logdata['body'] =  isset($comment['body']) ? str_replace("\n", '<br />', $comment['body']) : '';
        $line = serialize($logdata) . "\n";

        $date = (int)(time() / 3600 / 24);
        // use localtime simply because time handling ways in pukiwiki plus! and official are different. 
        if (file_exists($filename)) {
            $logdate = rtrim(array_shift(file_head($filename, 1)));
            if ($date - PLUGIN_AKISMET_ONELOG_DAYS >= $logdate) {
                slide_rename($filename, PLUGIN_AKISMET_KEEPLOG, '.%d');
                @move($filename, $filename . '.1');
                file_put_contents($filename, $date . "\n");
            }
        } else {
            file_put_contents($filename, $date . "\n");
        }
        return file_put_contents($filename, $line, FILE_APPEND);
    }
}

/////// PukiWiki API Extension //////////////
if (! function_exists('is_human')) {
    /**
     * Human recognition using PukiWiki Auth methods
     *
     * @param boolean $is_human Tell this is a human (Use TRUE to store into session)
     * @param boolean $use_session Use Session log
     * @param int $use_rolelevel accepts users whose role levels are stronger than this
     * @return boolean
     */
    if (! defined('ROLE_AUTH')) define('ROLE_AUTH', 5); // define for PukiWiki Official
    if (! defined('ROLE_ENROLLEE')) define('ROLE_ENROLLEE', 4);
    if (! defined('ROLE_ADM_CONTENTS')) define('ROLE_ADM_CONTENTS', 3);
    if (! defined('ROLE_ADM')) define('ROLE_ADM', 2);
    if (! defined('ROLE_GUEST')) define('ROLE_GUEST', 0);
    function is_human($is_human = FALSE, $use_session = FALSE, $use_rolelevel = 0)
    {
        if (! $is_human) {
            if ($use_session) {
                session_start();
                $is_human = isset($_SESSION['pkwk_is_human']) && $_SESSION['pkwk_is_human'];
            }
        }
        if (! $is_human) {
            if (ROLE_GUEST < $use_rolelevel && $use_rolelevel <= ROLE_AUTH) {
                if (is_callable(array('auth', 'check_role'))) { // Plus!
                    $is_human = ! auth::check_role('role_auth');
                } else { // PukiWiki Official
                    $is_human = isset($_SERVER['PHP_AUTH_USER']);
                }
            }
        }
        if (! $is_human) {
            if (ROLE_GUEST < $use_rolelevel && $use_rolelevel <= ROLE_ADM_CONTENTS) {
                $is_human = is_admin(NULL, $use_session, TRUE);
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
}
if (! function_exists('is_admin')) {
    /**
     * PukiWiki admin login with session
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

/////////////// PHP Extesnion ///////////////
if (! function_exists('slide_rename')) {
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
        if ($fp === FALSE) {
            return FALSE;
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
    function &_($str)
    {
        return $str;
    }
}

function plugin_akismet_init()
{
    global $plugin_akismet_name;
    if (class_exists('PluginAkismetUnitTest')) {
        $plugin_akismet_name = 'PluginAkismetUnitTest';
    } elseif (class_exists('PluginAkismetUser')) {
        $plugin_akismet_name = 'PluginAkismetUser';
    } else {
        $plugin_akismet_name = 'PluginAkismet';
    }
}

function plugin_akismet_action()
{
    global $plugin_akismet, $plugin_akismet_name;
    $plugin_akismet = new $plugin_akismet_name();
    return call_user_func(array(&$plugin_akismet, 'action'));
}

function plugin_akismet_write_before()
{
    global $plugin_akismet_name; 
    $plugin_akismet = new $plugin_akismet_name();
    $args = func_get_args();
    return call_user_func_array(array(&$plugin_akismet, 'write_before'), $args);
}

/*
 * This is a PHP library that handles calling reCAPTCHA.
 *    - Documentation and latest version
 *          http://recaptcha.net/plugins/php/
 *    - Get a reCAPTCHA API Key
 *          http://recaptcha.net/api/getkey
 *    - Discussion group
 *          http://groups.google.com/group/recaptcha
 *
 * Copyright (c) 2007 reCAPTCHA -- http://recaptcha.net
 * AUTHORS:
 *   Mike Crawford
 *   Ben Maurer
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * The reCAPTCHA server URL's
 */
define("RECAPTCHA_API_SERVER", "http://api.recaptcha.net");
define("RECAPTCHA_API_SECURE_SERVER", "https://api-secure.recaptcha.net");
define("RECAPTCHA_VERIFY_SERVER", "api-verify.recaptcha.net");

/**
 * Encodes the given data into a query string format
 * @param $data - array of string elements to be encoded
 * @return string - encoded request
 */
function _recaptcha_qsencode ($data) {
        $req = "";
        foreach ( $data as $key => $value )
                $req .= $key . '=' . urlencode( stripslashes($value) ) . '&';

        // Cut the last '&'
        $req=substr($req,0,strlen($req)-1);
        return $req;
}



/**
 * Submits an HTTP POST to a reCAPTCHA server
 * @param string $host
 * @param string $path
 * @param array $data
 * @param int port
 * @return array response
 */
function _recaptcha_http_post($host, $path, $data, $port = 80) {

        $req = _recaptcha_qsencode ($data);

        $http_request  = "POST $path HTTP/1.0\r\n";
        $http_request .= "Host: $host\r\n";
        $http_request .= "Content-Type: application/x-www-form-urlencoded;\r\n";
        $http_request .= "Content-Length: " . strlen($req) . "\r\n";
        $http_request .= "User-Agent: reCAPTCHA/PHP\r\n";
        $http_request .= "\r\n";
        $http_request .= $req;

        $response = '';
        if( false == ( $fs = @fsockopen($host, $port, $errno, $errstr, 10) ) ) {
                die ('Could not open socket');
        }

        fwrite($fs, $http_request);

        while ( !feof($fs) )
                $response .= fgets($fs, 1160); // One TCP-IP packet
        fclose($fs);
        $response = explode("\r\n\r\n", $response, 2);

        return $response;
}



/**
 * Gets the challenge HTML (javascript and non-javascript version).
 * This is called from the browser, and the resulting reCAPTCHA HTML widget
 * is embedded within the HTML form it was called from.
 * @param string $pubkey A public key for reCAPTCHA
 * @param string $error The error given by reCAPTCHA (optional, default is null)
 * @param boolean $use_ssl Should the request be made over ssl? (optional, default is false)

 * @return string - The HTML to be embedded in the user's form.
 */
function recaptcha_get_html ($pubkey, $error = null, $use_ssl = false)
{
	if ($pubkey == null || $pubkey == '') {
		die ("To use reCAPTCHA you must get an API key from <a href='http://recaptcha.net/api/getkey'>http://recaptcha.net/api/getkey</a>");
	}
	
	if ($use_ssl) {
                $server = RECAPTCHA_API_SECURE_SERVER;
        } else {
                $server = RECAPTCHA_API_SERVER;
        }

        $errorpart = "";
        if ($error) {
           $errorpart = "&amp;error=" . $error;
        }
        return '<script type="text/javascript" src="'. $server . '/challenge?k=' . $pubkey . $errorpart . '"></script>

	<noscript>
  		<iframe src="'. $server . '/noscript?k=' . $pubkey . $errorpart . '" height="300" width="500" frameborder="0"></iframe><br/>
  		<textarea name="recaptcha_challenge_field" rows="3" cols="40"></textarea>
  		<input type="hidden" name="recaptcha_response_field" value="manual_challenge"/>
	</noscript>';
}




/**
 * A ReCaptchaResponse is returned from recaptcha_check_answer()
 */
class ReCaptchaResponse {
        var $is_valid;
        var $error;
}


/**
  * Calls an HTTP POST function to verify if the user's guess was correct
  * @param string $privkey
  * @param string $remoteip
  * @param string $challenge
  * @param string $response
  * @param array $extra_params an array of extra variables to post to the server
  * @return ReCaptchaResponse
  */
function recaptcha_check_answer ($privkey, $remoteip, $challenge, $response, $extra_params = array())
{
	if ($privkey == null || $privkey == '') {
		die ("To use reCAPTCHA you must get an API key from <a href='http://recaptcha.net/api/getkey'>http://recaptcha.net/api/getkey</a>");
	}

	if ($remoteip == null || $remoteip == '') {
		die ("For security reasons, you must pass the remote ip to reCAPTCHA");
	}

	
	
        //discard spam submissions
        if ($challenge == null || strlen($challenge) == 0 || $response == null || strlen($response) == 0) {
                $recaptcha_response = new ReCaptchaResponse();
                $recaptcha_response->is_valid = false;
                $recaptcha_response->error = 'incorrect-captcha-sol';
                return $recaptcha_response;
        }

        $response = _recaptcha_http_post (RECAPTCHA_VERIFY_SERVER, "/verify",
                                          array (
                                                 'privatekey' => $privkey,
                                                 'remoteip' => $remoteip,
                                                 'challenge' => $challenge,
                                                 'response' => $response
                                                 ) + $extra_params
                                          );

        $answers = explode ("\n", $response [1]);
        $recaptcha_response = new ReCaptchaResponse();

        if (trim ($answers [0]) == 'true') {
                $recaptcha_response->is_valid = true;
        }
        else {
                $recaptcha_response->is_valid = false;
                $recaptcha_response->error = $answers [1];
        }
        return $recaptcha_response;

}

/**
 * gets a URL where the user can sign up for reCAPTCHA. If your application
 * has a configuration page where you enter a key, you should provide a link
 * using this function.
 * @param string $domain The domain where the page is hosted
 * @param string $appname The name of your application
 */
function recaptcha_get_signup_url ($domain = null, $appname = null) {
	return "http://recaptcha.net/api/getkey?" .  _recaptcha_qsencode (array ('domain' => $domain, 'app' => $appname));
}

function _recaptcha_aes_pad($val) {
	$block_size = 16;
	$numpad = $block_size - (strlen ($val) % $block_size);
	return str_pad($val, strlen ($val) + $numpad, chr($numpad));
}

/* Mailhide related code */

function _recaptcha_aes_encrypt($val,$ky) {
	if (! function_exists ("mcrypt_encrypt")) {
		die ("To use reCAPTCHA Mailhide, you need to have the mcrypt php module installed.");
	}
	$mode=MCRYPT_MODE_CBC;   
	$enc=MCRYPT_RIJNDAEL_128;
	$val=_recaptcha_aes_pad($val);
	return mcrypt_encrypt($enc, $ky, $val, $mode, "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0");
}


function _recaptcha_mailhide_urlbase64 ($x) {
	return strtr(base64_encode ($x), '+/', '-_');
}

/* gets the reCAPTCHA Mailhide url for a given email, public key and private key */
function recaptcha_mailhide_url($pubkey, $privkey, $email) {
	if ($pubkey == '' || $pubkey == null || $privkey == "" || $privkey == null) {
		die ("To use reCAPTCHA Mailhide, you have to sign up for a public and private key, " .
		     "you can do so at <a href='http://mailhide.recaptcha.net/apikey'>http://mailhide.recaptcha.net/apikey</a>");
	}
	

	$ky = pack('H*', $privkey);
	$cryptmail = _recaptcha_aes_encrypt ($email, $ky);
	
	return "http://mailhide.recaptcha.net/d?k=" . $pubkey . "&c=" . _recaptcha_mailhide_urlbase64 ($cryptmail);
}

/**
 * gets the parts of the email to expose to the user.
 * eg, given johndoe@example,com return ["john", "example.com"].
 * the email is then displayed as john...@example.com
 */
function _recaptcha_mailhide_email_parts ($email) {
	$arr = preg_split("/@/", $email );

	if (strlen ($arr[0]) <= 4) {
		$arr[0] = substr ($arr[0], 0, 1);
	} else if (strlen ($arr[0]) <= 6) {
		$arr[0] = substr ($arr[0], 0, 3);
	} else {
		$arr[0] = substr ($arr[0], 0, 4);
	}
	return $arr;
}

/**
 * Gets html to display an email address given a public an private key.
 * to get a key, go to:
 *
 * http://mailhide.recaptcha.net/apikey
 */
function recaptcha_mailhide_html($pubkey, $privkey, $email) {
	$emailparts = _recaptcha_mailhide_email_parts ($email);
	$url = recaptcha_mailhide_url ($pubkey, $privkey, $email);
	
	return htmlentities($emailparts[0]) . "<a href='" . htmlentities ($url) .
		"' onclick=\"window.open('" . htmlentities ($url) . "', '', 'toolbar=0,scrollbars=0,location=0,statusbar=0,menubar=0,resizable=0,width=500,height=300'); return false;\" title=\"Reveal this e-mail address\">...</a>@" . htmlentities ($emailparts [1]);

}

//////// akismet.class.php //////////////////////////
/**
 * 01.26.2006 12:29:28est
 * 
 * Akismet PHP4 class
 * 
 * <b>Usage</b>
 * <code>
 *    $comment = array(
 *           'author'    => 'viagra-test-123',
 *           'email'     => 'test@example.com',
 *           'website'   => 'http://www.example.com/',
 *           'body'      => 'This is a test comment',
 *           'permalink' => 'http://yourdomain.com/yourblogpost.url',
 *        );
 *
 *    $akismet = new Akismet('http://www.yourdomain.com/', 'YOUR_WORDPRESS_API_KEY', $comment);
 *
 *    if($akismet->isError()) {
 *        echo"Couldn't connected to Akismet server!";
 *    } else {
 *        if($akismet->isSpam()) {
 *            echo"Spam detected";
 *        } else {
 *            echo"yay, no spam!";
 *        }
 *    }
 * </code>
 * 
 * @author Bret Kuhns {@link www.miphp.net}
 * @link http://www.miphp.net/blog/view/php4_akismet_class/
 * @version 0.3.3
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */



// Error constants
define("AKISMET_SERVER_NOT_FOUND",    0);
define("AKISMET_RESPONSE_FAILED",    1);
define("AKISMET_INVALID_KEY",        2);



// Base class to assist in error handling between Akismet classes
class AkismetObject {
    var $errors = array();
    
    
    /**
     * Add a new error to the errors array in the object
     *
     * @param    String    $name    A name (array key) for the error
     * @param    String    $string    The error message
     * @return void
     */ 
    // Set an error in the object
    function setError($name, $message) {
        $this->errors[$name] = $message;
    }
    

    /**
     * Return a specific error message from the errors array
     *
     * @param    String    $name    The name of the error you want
     * @return mixed    Returns a String if the error exists, a false boolean if it does not exist
     */
    function getError($name) {
        if($this->isError($name)) {
            return $this->errors[$name];
        } else {
            return false;
        }
    }
    
    
    /**
     * Return all errors in the object
     *
     * @return String[]
     */ 
    function getErrors() {
        return (array)$this->errors;
    }
    
    
    /**
     * Check if a certain error exists
     *
     * @param    String    $name    The name of the error you want
     * @return boolean
     */ 
    function isError($name) {
        return isset($this->errors[$name]);
    }
    
    
    /**
     * Check if any errors exist
     *
     * @return boolean
     */
    function errorsExist() {
        return (count($this->errors) > 0);
    }
    
    
}





// Used by the Akismet class to communicate with the Akismet service
class AkismetHttpClient extends AkismetObject {
    var $akismetVersion = '1.1';
    var $con;
    var $host;
    var $port;
    var $apiKey;
    var $blogUrl;
    var $errors = array();
    
    
    // Constructor
    function AkismetHttpClient($host, $blogUrl, $apiKey, $port = 80) {
        $this->host = $host;
        $this->port = $port;
        $this->blogUrl = $blogUrl;
        $this->apiKey = $apiKey;
    }
    
    
    // Use the connection active in $con to get a response from the server and return that response
    function getResponse($request, $path, $type = "post", $responseLength = 1160) {
        $this->_connect();
        
        if($this->con && !$this->isError(SERVER_NOT_FOUND)) {
            $request  = 
                strToUpper($type)." /{$this->akismetVersion}/$path HTTP/1.0\r\n" .
                "Host: ".((!empty($this->apiKey)) ? $this->apiKey."." : null)."{$this->host}\r\n" .
                "Content-Type: application/x-www-form-urlencoded; charset=utf-8\r\n" .
                "Content-Length: ".strlen($request)."\r\n" .
                "User-Agent: Akismet PHP4 Class\r\n" .
                "\r\n" .
                $request
                ;
            $response = "";

            @fwrite($this->con, $request);

            while(!feof($this->con)) {
                $response .= @fgets($this->con, $responseLength);
            }

            $response = explode("\r\n\r\n", $response, 2);
            return $response[1];
        } else {
            $this->setError(AKISMET_RESPONSE_FAILED, "The response could not be retrieved.");
        }
        
        $this->_disconnect();
    }
    
    
    // Connect to the Akismet server and store that connection in the instance variable $con
    function _connect() {
        if(!($this->con = @fsockopen($this->host, $this->port))) {
            $this->setError(AKISMET_SERVER_NOT_FOUND, "Could not connect to akismet server.");
        }
    }
    
    
    // Close the connection to the Akismet server
    function _disconnect() {
        @fclose($this->con);
    }
    
    
}





// The controlling class. This is the ONLY class the user should instantiate in
// order to use the Akismet service!
class Akismet extends AkismetObject {
    var $apiPort = 80;
    var $akismetServer = 'rest.akismet.com';
    var $akismetVersion = '1.1';
    var $http;
    
    var $ignore = array(
                        'HTTP_COOKIE',
                        'HTTP_X_FORWARDED_FOR',
                        'HTTP_X_FORWARDED_HOST',
                        'HTTP_MAX_FORWARDS',
                        'HTTP_X_FORWARDED_SERVER',
                        'REDIRECT_STATUS',
                        'SERVER_PORT',
                        'PATH',
                        'DOCUMENT_ROOT',
                        'SERVER_ADMIN',
                        'QUERY_STRING',
                        'PHP_SELF'
                        );
    
    var $blogUrl = "";
    var $apiKey  = "";
    var $comment = array();
    
    
    /**
     * Constructor
     * 
     * Set instance variables, connect to Akismet, and check API key
     * 
     * @param    String    $blogUrl    The URL to your own blog
     * @param     String    $apiKey        Your wordpress API key
     * @param     String[]    $comment    A formatted comment array to be examined by the Akismet service
     */
    function Akismet($blogUrl, $apiKey, $comment) {
        $this->blogUrl = $blogUrl;
        $this->apiKey  = $apiKey;
        
        // Populate the comment array with information needed by Akismet
        $this->comment = $comment;
        $this->_formatCommentArray();
        
        if(!isset($this->comment['user_ip'])) {
            $this->comment['user_ip'] = ($_SERVER['REMOTE_ADDR'] != getenv('SERVER_ADDR')) ? $_SERVER['REMOTE_ADDR'] : getenv('HTTP_X_FORWARDED_FOR');
        }
        if(!isset($this->comment['user_agent'])) {
            $this->comment['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        }
        if(!isset($this->comment['referrer'])) {
            $this->comment['referrer'] = $_SERVER['HTTP_REFERER'];
        }
        $this->comment['blog'] = $blogUrl;
        
        // Connect to the Akismet server and populate errors if they exist
        $this->http = new AkismetHttpClient($this->akismetServer, $blogUrl, $apiKey);
        if($this->http->errorsExist()) {
            $this->errors = array_merge($this->errors, $this->http->getErrors());
        }
        
        // Check if the API key is valid
        if(!$this->_isValidApiKey($apiKey)) {
            $this->setError(AKISMET_INVALID_KEY, "Your Akismet API key is not valid.");
        }
    }
    
    
    /**
     * Query the Akismet and determine if the comment is spam or not
     * 
     * @return    boolean
     */
    function isSpam() {
        $response = $this->http->getResponse($this->_getQueryString(), 'comment-check');
        
        return ($response == "true");
    }
    
    
    /**
     * Submit this comment as an unchecked spam to the Akismet server
     * 
     * @return    void
     */
    function submitSpam() {
        $this->http->getResponse($this->_getQueryString(), 'submit-spam');
    }
    
    
    /**
     * Submit a false-positive comment as "ham" to the Akismet server
     *
     * @return    void
     */
    function submitHam() {
        $this->http->getResponse($this->_getQueryString(), 'submit-ham');
    }
    
    
    /**
     * Check with the Akismet server to determine if the API key is valid
     *
     * @access    Protected
     * @param    String    $key    The Wordpress API key passed from the constructor argument
     * @return    boolean
     */
    function _isValidApiKey($key) {
        $keyCheck = $this->http->getResponse("key=".$this->apiKey."&blog=".$this->blogUrl, 'verify-key');
            
        return ($keyCheck == "valid");
    }
    
    
    /**
     * Format the comment array in accordance to the Akismet API
     *
     * @access    Protected
     * @return    void
     */
    function _formatCommentArray() {
        $format = array(
                        'type' => 'comment_type',
                        'author' => 'comment_author',
                        'email' => 'comment_author_email',
                        'website' => 'comment_author_url',
                        'body' => 'comment_content'
                        );
        
        foreach($format as $short => $long) {
            if(isset($this->comment[$short])) {
                $this->comment[$long] = $this->comment[$short];
                unset($this->comment[$short]);
            }
        }
    }
    
    
    /**
     * Build a query string for use with HTTP requests
     *
     * @access    Protected
     * @return    String
     */
    function _getQueryString() {
        foreach($_SERVER as $key => $value) {
            if(!in_array($key, $this->ignore)) {
                if($key == 'REMOTE_ADDR') {
                    $this->comment[$key] = $this->comment['user_ip'];
                } else {
                    $this->comment[$key] = $value;
                }
            }
        }

        $query_string = '';

        foreach($this->comment as $key => $data) {
            $query_string .= $key . '=' . urlencode(stripslashes($data)) . '&';
        }

        return $query_string;
    }
    
    
}
?>
