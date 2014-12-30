<?php
/**
 * Replace Page Contents with Regular Expression
 *
 * @author     sonots
 * @license    http://www.gnu.org/licenses/gpl.html GPL v2
 * @link       http://lsx.sourceforge.jp/?Plugin%2Fregexp.inc.php
 * @version    $Id: regexp.inc.php,v 1.4 2008-07-20 07:23:17Z sonots $
 * @package    plugin
 */

class PluginRegexp
{
    function PluginRegexp()
    {
        // modify here for default values
        static $conf = array(
            'ignore_freeze' => TRUE,
            'adminonly'     => TRUE,
        );
        static $default_options = array(
            'pcmd'     => '',
            'pass'     => '',
            'filter'   => '',
            'except'   => '',
            'page'    => '',
            'search'   => '',
            'replace'  => '',
            'regexp'   => TRUE,
            'msearch'  => '',
            'mreplace' => '',
            'notimestamp' => TRUE,
        );
        $this->conf = & $conf;
        $this->default_options = & $default_options;

        // init
        $this->options = $this->default_options;
        $this->view =  new PluginRegexpView($this);
    }

    // static
    var $conf;
    var $default_values;
    // var
    var $error = '';
    var $plugin = 'regexp';
    var $options = array();
    var $view;
    var $preg_replace;
    var $str_replace;

    function action()
    {
        set_time_limit(0);
        global $vars;
        foreach ($this->options as $key => $val) {
            $this->options[$key] = isset($vars[$key]) ? $vars[$key] : '';
        }
        foreach ($this->options as $key => $val) {
            ${$key} = $val;
        }
        if ($pcmd == '') {
            $body = $this->view->showform();
        } elseif ($search == '' && $msearch == '') {
            $body = $this->view->showform('No search.');
        } elseif (! $this->view->login()) { // auth::check_role('role_adm_contents')
            $body = $this->view->showform('The password is wrong.');
        } elseif ($pcmd == 'preview') {
            $body = $this->do_preview();
        } elseif ($pcmd == 'replace') {
            $pages = $this->do_replace_all();
            $body = $this->view->result($pages);
        }
        return array('msg'=>$this->plugin, 'body'=>$body);
    }

    function do_preview()
    {
        foreach ($this->options as $key => $val) {
            ${$key} = $val;
        }
        $diff = '';
        $pages = $this->get_pages($filter, $except, $page);
        foreach ($pages as $apage) {
            $replaced = $this->replace($apage, $search, $replace, $msearch, $mreplace, $regexp);
            if (is_null($replaced)) continue;
            $source = implode("", get_source($apage));
            $diff = do_diff($source, $replaced);
            break;
        }
        $body = $this->view->preview($apage, $diff, $pages);
        return $body;
    }


    function do_replace_all()
    {
        foreach ($this->options as $key => $val) {
            ${$key} = $val;
        }
        $pages = $this->get_pages($filter, $except, $page);
        $replaced_pages = array();
        foreach ($pages as $apage) {
            $replaced = $this->replace($apage, $search, $replace, $msearch, $mreplace, $regexp);
            if (is_null($replaced)) continue;
            $GLOBALS['cycle'] = 0;
            page_write($apage, $replaced, $notimestamp);
            $replaced_pages[] = $apage;
        }
        return $replaced_pages;
    }

    /**
     * Replace contents of a page
     *
     * @param string $page
     * @param string $search
     * @param string $replace
     * @param string $msearch multiline search
     * @param string $mreplace
     * @param boolean $regexp
     * @param string|null replaced contents. null if no replace occurred
     */
    function replace($page, $search, $replace, $msearch, $mreplace, $regexp = TRUE)
    {
        if (! $this->is_editable($page)) return null;
        if ($regexp) {
            // mb_preg_replace usually does not exist.
            $replace_func = function_exists('mb_preg_replace') ? 'mb_preg_replace' : 'preg_replace';
            if ($msearch != '') $msearch = '/' . str_replace('/', '\/', $msearch) . '/D';
            // Memo: refer http://us.php.net/manual/ja/reference.pcre.pattern.modifiers.php for D.
            if ($search != '')  $search  = '/' . str_replace('/', '\/', $search)  . '/';
        } else {
            // mb_str_replace usually does not exist.
            $replace_func = function_exists('mb_str_replace') ? 'mb_str_replace' : 'str_replace';
        }
        $lines = get_source($page);
        $source = implode("", $lines);
        if ($msearch != '') {
            $msearch = str_replace("\r", "\n", str_replace("\r\n", "\n", $msearch));
            $replace = call_user_func($replace_func, $msearch, $mreplace, $source);
        } elseif ($search != '') {
            $replace_lines = array();
            foreach ($lines as $line) {
                $line = rtrim($line, "\n");
                $replace_lines[] = call_user_func($replace_func, $search, $replace, $line) . "\n";
            }
            $replace = implode("", $replace_lines);
        }
        if ($source == $replace) return null;
        return $replace;
    }

    /**
     * Get filtered pages (not all existpages)
     *
     * @param string $filter regexp filter
     * @param string $except regexp except filter
     * @param string $onepage one exact page name
     */
    function get_pages($filter = '', $except = '', $onepage = '')
    {
        if (! empty($onepage)) {
            return (array)$onepage;
        }
        if (method_exists('auth', 'get_existpages')) { // plus!
            $pages = auth::get_existpages();
        } else {
            $pages = get_existpages();
        }
        if (! empty($filter)) {
            $pregfilter = '/' . str_replace('/', '\/', $filter) . '/';
            foreach($pages as $file => $page) {
                if (! preg_match($pregfilter, $page)) {
                    unset($pages[$file]);
                }
            }
        }
        if (! empty($except)) {
            $pregexcept = '/' . str_replace('/', '\/', $except) . '/';
            foreach($pages as $file => $page) {
                if (preg_match($pregexcept, $page)) {
                    unset($pages[$file]);
                }
            }
        }
        return $pages;
    }

    /**
     * Check if the page is editable or not
     * 
     * PukiWIki API Extension
     *
     * @param string $page
     * @return boolean
     */
    function is_editable($page)
    {
        global $cantedit;
        if ($this->conf['ignore_freeze']) {
            $editable = ! in_array($page, $cantedit);
        } else {
            $editable = (! is_freeze($page) and ! in_array($page, $cantedit) );
        }
        return $editable;
    }
}

//////////////////////////////////
class PluginRegexpView
{
    // static
    var $msg;
    // var
    var $plugin = 'regexp';
    var $options;
    var $conf;
    var $model;

    function PluginRegexpView(&$model)
    {
        static $msg = array();
        if (empty($msg)) {
            $msg = array(
               'label' => array(
                  'pass'        => _('Admin Password'),
                  'filter'      => _('Filter Pages'),
                  'except'      => _('Except Pages'),
                  'page'        => _('A Page'),
                  'search'      => _('Search'),
                  'replace'     => _('Replace'),
                  'msearch'     => _('Multiline Search'),
                  'mreplace'    => _('Multiline Replace'),
                  'regexp'      => _('Regexp'),
                  'notimestamp' => _('notimestamp'),
                  'preview'     => _('Preview'),
               ),
               'text' => array(
                  'pass'        => '',
                  'filter'      => 'Filter pages to be processed by regular expression. <br />Ex) "^PukiWiki" =&gt; all pages starting with "PukiWiki."',
                  'except'      => 'Except pages by regular expression.',
                  'page'        => 'Specify a page to be processed. If this field is specified, "Filter Pages" is ignored.',
                  'search'      => 'The string to be replaced. Apply replacing strings to each line separately. ',
                  'replace'     => 'Ex) Search ^#ls\((.*)\)$ =&gt; Replace #lsx(\1). <br />Ex) Search &mimetex\(((?:[^;]|[^)];)*)\); =&gt; Replace $ \1 $. (with regexp check)',
                  'msearch'     => 'The multi-line strings to be replaced. Apply replacing strings to whole contents at one time. Use this when you want to include returns or line feeds. If this field is specified, "Search" is ignored. ', 
                  'mreplace'    => '',
                  'regexp'      => 'Use regular expression for searching.',
                  'notimestamp' => 'Do not change timestamps.',
                  'preview'     => '',
               ),
            );  
        }

        $this->msg     = &$msg;
        $this->model   = &$model;
        $this->options = &$model->options;
        $this->conf    = &$model->conf;
    }

    function login()
    {
        if ($this->conf['adminonly'] === FALSE) return TRUE;
        global $vars;
        $pass = isset($vars['pass']) ? $vars['pass'] : $this->getcookie('pass');
        if (pkwk_login($pass)) {
            $this->setcookie('pass', $pass);
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * Get cookie
     *
     * @param string $key
     * @return mixed
     */
    function getcookie($key)
    {
        $key = 'plugin_regexp_' . $key;
        return isset($_COOKIE[$key]) ? unserialize($_COOKIE[$key]) : null;
    }

    /**
     * Set cookie
     *
     * @param string $key
     * @param mixed $val
     * @return void
     */
    function setcookie($key, $val)
    {
        global $script;
        $parsed = parse_url($script);
        $path = $this->get_dirname($parsed['path']);
        $key = 'plugin_regexp_' . $key;
        setcookie($key, serialize($val), 0, $path);
        $_COOKIE[$key] = serialize($val);
    }

    function result($pages)
    {
        $links = array();
        foreach ($pages as $page) {
            $links[] = make_pagelink($page);
        }
        $msg = implode("<br />\n", $links);
        $body = '<p>The following pages were replaced.</p><div>' . $msg . '</div>';
        return $body;
    }

    function preview($page, $diff, $pages)
    {
        global $script;
        if ($page == '' || $diff == '') {
            return '<div>No page found or nothing changed.</div>';
        } 
        unset($this->options['pass']);
        unset($this->options['pcmd']);
        foreach ($this->options as $key => $val) {
            $this->setcookie($key, $val);
        }

        $msg = '<div>A preview, <b>' . htmlspecialchars($page) . '</b></div>';
        //$diff = '<pre>' . htmlspecialchars($diff) . '</pre>';
        $msg .= '<pre>' . diff_style_to_css(htmlspecialchars($diff)) . '</pre>'; // Pukiwiki API

        $msg .= '<div>List of target pages (Result of Filter Pages) </div>';
        $msg .= '<ul>';
        foreach ($pages as $apage) {
            $msg .= '<li>' . make_pagelink($apage) . '</li>';
        }
        $msg .= '</ul>';

        $form = array();
        $form[] = '<form action="' . $script . '?cmd=' . $this->plugin . '" method="post">';
        $form[] = '<div>';
        $form[] = ' Do you want to replace all pages? ';
        $form[] = ' <input type="hidden" name="cmd"  value="regexp" />';
        $form[] = ' <input type="hidden" name="pcmd"  value="replace" />';
        foreach ($this->options as $key => $val) {
            $form[] = ' <input type="hidden" name="' . $key . '" value="' . $val . '" />';
        }
        $form[] = ' <input type="submit" name="ok" value="Yes" /><br />';
        $form[] = '</div>';
        $form[] = '</form>';
        $form = implode("\n", $form);
        return $msg . $form;
    }

    /**
     * Show form
     *
     * @param string $msg
     * @return string html
     */
    function showform($msg = "")
    {
        global $script;
        foreach ($this->options as $key => $val) {
            ${$key} = $this->getcookie($key);
            if (is_null(${$key})) ${$key} = $val;
        }
        $regexp = ($regexp == 'on') ? ' checked="checked"' : '';
        $notimestamp = ($notimestamp == 'on') ? ' checked="checked"' : '';

        $form = array();
        $form[] = '<form action="' . $script . '?cmd=' . $this->plugin . '" method="post">';
        $form[] = '<div class="ie5"><table class="style_table" cellspacing="1" border="0"><tbody>';
        if ($this->conf['adminonly']) {
            $form[] = '<tr><td class="style_td">' . $this->msg['label']['pass'] . 
                '</td><td class="style_td"><input type="password" name="pass" size="24" value="' . $pass . '" />' . 
                '</td><td class="style_td">' . $this->msg['text']['pass'] . '</td></tr>';
        }
        $form[] = '<tr><td class="style_td">' . $this->msg['label']['filter'] . 
            '</td><td class="style_td"><input type="text" name="filter" size="42" value="' . $filter . '" />' .
            '</td><td class="style_td">' . $this->msg['text']['filter'] . '</td></tr>';
        $form[] = '<tr><td class="style_td">' . $this->msg['label']['except'] . 
            '</td><td class="style_td"><input type="text" name="except" size="42" value="' . $except . '" />' .
            '</td><td class="style_td">' . $this->msg['text']['except'] . '</td></tr>';
        $form[] = '<tr><td class="style_td">' . $this->msg['label']['page'] . 
            '</td><td class="style_td"><input type="text" name="page" size="42" value="' . $page . '" />' . 
            '</td><td class="style_td">' . $this->msg['text']['page'] . '</td></tr>';
        $form[] = '<tr><td class="style_td"><label for="regexp">' . $this->msg['label']['regexp'] . '</label>' . 
            '</td><td class="style_td"><input type="checkbox" name="regexp" id="regexp" value="on"' . $regexp . '/>' .
            '</td><td class="style_td">' . $this->msg['text']['regexp'] . '</td></tr>';
        $form[] = '<tr><td class="style_td">' . $this->msg['label']['search'] . 
            '</td><td class="style_td"><input type="text" name="search" size="42" value="' . $search . '" />' .
            '</td><td class="style_td">' . $this->msg['text']['search'] . '</td></tr>';
        $form[] = '<tr><td class="style_td">' . $this->msg['label']['replace'] . 
            '</td><td class="style_td"><input type="text" name="replace" size="42" value="' . $replace . '" />' . 
            '</td><td class="style_td">' . $this->msg['text']['replace'] . '</td></tr>';
        $form[] = '<tr><td class="style_td">' . $this->msg['label']['msearch'] . 
            '</td><td class="style_td"><textarea name="msearch" rows="3" cols="40">' . $msearch . '</textarea>' .
            '</td><td class="style_td">' . $this->msg['text']['msearch'] . '</td></tr>';
        $form[] = '<tr><td class="style_td">' . $this->msg['label']['mreplace'] . 
            '</td><td class="style_td"><textarea name="mreplace" rows="3" cols="40">' . $mreplace . '</textarea>' .
            '</td><td class="style_td">' . $this->msg['text']['mreplace'] . '</td></tr>';
        $form[] = '<tr><td class="style_td"><label for="notimestamp">' . $this->msg['label']['notimestamp'] . '</label>' . 
            '</td><td class="style_td"><input type="checkbox" name="notimestamp" id="notimestamp" value="on"' . $notimestamp . '/>' .
            '</td><td class="style_td">' . $this->msg['text']['notimestamp'] . '</td></tr>';
        $form[] = '</tbody></table></div>';
        $form[] = '<div>';
        $form[] = ' <input type="hidden" name="cmd"  value="regexp" />';
        $form[] = ' <input type="hidden" name="pcmd"  value="preview" />';
        $form[] = ' <input type="submit" name="submit" id="preview" value="' . $this->msg['label']['preview'] . '" />';
        $form[] = '</div>';
        $form[] = '</form>';
        $form = implode("\n", $form);
   
        if ($msg != '') {
            $msg = '<p><b>' . $msg . '</b></p>';
        }
        return $msg . $form;
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
}

// php extension
if (! function_exists('_')) {
    function &_($str)
    {
        return $str;
    }
}

//////////////////////////////////
function plugin_regexp_init()
{
    global $plugin_regexp_name;
    if (class_exists('PluginRegexpUnitTest')) {
        $plugin_regexp_name = 'PluginRegexpUnitTest';
    } elseif (class_exists('PluginRegexpUser')) {
        $plugin_regexp_name = 'PluginRegexpUser';
    } else {
        $plugin_regexp_name = 'PluginRegexp';
    }
}
function plugin_regexp_action()
{
    global $plugin_regexp, $plugin_regexp_name;
    $plugin_regexp = new $plugin_regexp_name();
    return call_user_func(array(&$plugin_regexp, 'action'));
}

if (! defined('INIT_DIR')) // if not Plus! 
    if (file_exists(DATA_HOME . 'init/regexp.ini.php')) 
        include_once(DATA_HOME . 'init/regexp.ini.php');

?>
