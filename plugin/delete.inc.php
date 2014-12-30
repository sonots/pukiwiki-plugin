<?php
/**
 * Delete a page
 *
 * @author     sonots <http://lsx.sourceforge.jp>
 * @license    http://www.gnu.org/licenses/gpl.html GPL v2
 * @link       http://lsx.sourceforge.jp/?Plugin%2Fdelete.inc.php
 * @version    $Id: delete.inc.php v1.1 2008-03-24 16:28:39Z sonots $
 * @package    plugin
 */

class PluginDelete
{
    function PluginDelete()
    {
        static $conf = array(
             'adminonly'        => TRUE,
             'use_session'      => TRUE,
             'through_if_admin' => TRUE,
        );
        static $default_options = array(
             'pass'     => '',
             'filter'   => '',
             'page'     => '',
             'method'   => 'page_write', // 'page_write' or 'unlink'
        );
        $this->conf = & $conf;
        $this->default_options = & $default_options;
        
        // init
        $this->options = $this->default_options;
    }

    // static
    var $conf;
    var $default_options;
    // var
    var $error = '';
    var $options = array();

    /**
     * Action Plugin Main Function
     */
    function action()
    {        
        global $vars;
        if (isset($vars['pcmd'])) {
            if (is_admin($vars['pass'], $this->conf['use_session'], $this->conf['through_if_admin'])) {
                if ($vars['pcmd'] === 'preview') {
                    $pages = $this->get_filtered_pages($vars['filter'], $vars['page']);
                    $body = $this->display_preview_form($pages);
                } elseif ($vars['pcmd'] === 'delete') {
                    $pages = isset($vars['pages']) ? $vars['pages'] : array();
                    $body = $this->do_delete($pages);
                }
            } else {
                $body = $this->display_query_form('The password is wrong');
            }
        } else {
            if (isset($vars['page']) && $vars['page'] !== '' &&
                is_admin(null, $this->conf['use_session'], $this->conf['through_if_admin'])) {
                $pages = (array)$vars['page'];
                $body = $this->do_delete($pages);
            } else {
                $body = $this->display_query_form();
            }
        }
        return array('msg'=>_('Delete'), 'body'=>$body);
    }

    /**
     * Get filtered pages
     *
     * @param string $filter regular expression
     * @param string $page 
     * @return array pages
     */
    function get_filtered_pages($filter, $page)
    {
        if ($page != '') {
            return array($page);
        }
        $pages = get_existpages(); //auth::get_existpages();
        if ($filter != '') {
            $filter = '/' . str_replace('/', '\/', $filter) . '/';
            foreach($pages as $file => $apage) {
                if (! preg_match($filter, $apage)) {
                    unset($pages[$file]);
                }
            }
        }
        return $pages;
    }
    
    /*
     * Delete Main function
     *
     * @param array &$pages
     * @param string $method how to delete. By 'page_write' or 'unlink'
     * @return string error message or some messages
     */
    function do_delete(&$pages, $method = 'page_write')
    {
        set_time_limit(0);
        $msg = '';
        switch ($method) {
        case 'page_write':
            foreach ($pages as $page) {
                $msg .= '<a href="' . get_script_uri() . '?cmd=edit&amp;page=' . rawurlencode($page) . '">Edit</a> ';
                page_write($page, '');
                $msg .= 'Maybe Deleted. ' . htmlspecialchars($page);
                $msg .= '<br />' . "\n";
            }
            break;
        case 'unlink':
            foreach ($pages as $page) {
                $msg .= '<a href="' . get_script_uri() . '?cmd=edit&amp;page=' . rawurlencode($page) . '">Edit</a> ';
                $file = get_filename($page);
                if (unlink($file)) {
                    $msg .= 'Deleted. ' . htmlspecialchars($page);
                } else {
                    $msg .= 'Failed to delete. ' . htmlspecialchars($page);
                }
                $msg .= '<br />' . "\n";
            }
            break;
        }
        return $msg;
    }

    /**
     * Parse args for option plugin
     *
     * @param $vars
     * @param $default_options
     * @return $options
     */
    function parse_options(&$vars, &$default_options)
    {
        $options = $default_options;
        foreach ($vars as $key => $val) {
            if (array_key_exists($key, $options)) {
                $options[$key] = $val;
            }
        }
        return $options;
    }
    
    /**
     * Get preview form
     *
     * @param array $pages
     * @return string
     */
    function display_preview_form($pages)
    {
        $form = array();
        $form[] = '<form action="' . get_script_uri() . '?cmd=delete" method="post">';
        $form[] = '<div>';
        foreach ($pages as $page) {
            $form[] = '<input type="checkbox" name="pages[]" value="' . htmlspecialchars($page) . '" checked="checked" />';
            $form[] = htmlspecialchars($page) . '<br />';
        }
        $form[] = '<input type="hidden" name="pcmd"  value="delete" />';
        $form[] = 'Are you sure to delete them?';
        $form[] = '<input type="submit" name="submit" value="Yes" />';
        $form[] = '</div>';
        $form[] = '</form>';
        $form = implode("\n", $form);
        return $form;
    }

    /**
     * Get qury form
     *
     * @param $msg error message or some messages
     * @var $options
     * @var $conf 'adminonly'
     * @global $vars;
     * @return string
     */
    function display_query_form($message = "")
    {
        static $msg = array();
        if (empty($msg)) {
            $msg = array();
            $msg['label'] = array(
                 'pass'        => _('Password'),
                 'filter'      => _('Filter Pages'),
                 'page'        => _('A Page'),
                 'method'      => _('Delete Function'),
            );
            $msg['text'] = array(
                 'pass'        => '',
                 'filter'      => 'by regular expression. Ex) "^PukiWiki" =&gt; all pages starting with "PukiWiki."',
                 'page'        => 'Specify a page which you want to process. "Filter Pages" is ignored.',
                 'method'      => 'Use PukiWiki\'s page_write function or PHP\'s unlink function to delete pages.',
            );
            $msg['button'] = array(
                 'delete'      => _('Preview'),
            );  
        }
        
        global $vars;
        $options = $this->parse_options($vars, $this->default_options);
        foreach ($options as $key => $val) {
            ${$key} = $val;
        }
        $method = array();
        $method['page_write'] = ($options['method'] == 'page_write' ? ' checked="checked"' : '');
        $method['unlink']     = ($options['method'] == 'unlink'     ? ' checked="checked"' : '');
        $is_admin = (! $this->conf['adminonly'] || is_admin(null, $this->conf['use_session'], $this->conf['through_if_admin']));

        $form = array();
        $form[] = '<form action="' . get_script_uri() . '?cmd=delete " method="post">';
        $form[] = '<div class="ie5"><table class="style_table" cellspacing="1" border="0"><tbody>';
        $form[] = '<tr>';
        $form[] = ' <td class="style_td">' . $msg['label']['pass'] . '</td>';
        $form[] = ' <td class="style_td"><input type="password" name="pass" size="24" value="' . htmlspecialchars($pass) . '"' . 
            ($is_admin ? ' style="background-color:#ddd;" disabled="disabled"' : '') . ' />' . '</td>';
        $form[] = ' <td class="style_td">' . $msg['text']['pass'] . '</td>';
        $form[] = '</tr>';
        $form[] = '<tr>';
        $form[] = ' <td class="style_td">' . $msg['label']['filter'] . '</td>';
        $form[] = ' <td class="style_td"><input type="text" name="filter" size="24" value="' . htmlspecialchars($filter) . '" />' . '</td>';
        $form[] = ' <td class="style_td">' . $msg['text']['filter'] . '</td>';
        $form[] = '</tr>';
        $form[] = '<tr>';
        $form[] = ' <td class="style_td">' . $msg['label']['page'] . '</td>';
        $form[] = ' <td class="style_td"><input type="text" name="page" size="24" value="' . htmlspecialchars($page) . '" />' . '</td>';
        $form[] = ' <td class="style_td">' . $msg['text']['page'] . '</td>';
        $form[] = '</tr>';
        $form[] = '<tr>';
        $form[] = ' <td class="style_td">' . $msg['label']['method'] . '</td>';
        $form[] = ' <td class="style_td"><input type="radio" name="method" value="page_write" id="page_write"' . $method['page_write'] . ' /><label for="page_write">page_write</label>';
        $form[] = ' <input type="radio" name="method" value="unlink" id="unlink"' . $method['unlink'] . ' /><label for="unlink">unlink</label>';
        $form[] = ' <td class="style_td">' . $msg['text']['method'] . '</td>';
        $form[] = '</tr>';
        $form[] = '</tbody></table></div>';
        $form[] = '<div>';
        $form[] = ' <input type="hidden" name="pcmd"  value="preview" />';
        $form[] = ' <input type="submit" name="submit" id="delete" value="' . $msg['button']['delete'] . '" />';
        $form[] = '</div>';
        $form[] = '</form>';
        $form = implode("\n", $form);
   
        if ($message != '') {
            $message = '<p><b>' . htmlspecialchars($message) . '</b></p>';
        }
        return $message . $form;
    }
}

//////////////// PukiWiki API Extension
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

////////////////////////////////
function plugin_delete_init()
{
    global $plugin_delete_name;
    if (class_exists('PluginDeleteUnitTest')) {
        $plugin_delete_name = 'PluginDeleteUnitTest';
    } elseif (class_exists('PluginDeleteUser')) {
        $plugin_delete_name = 'PluginDeleteUser';
    } else {
        $plugin_delete_name = 'PluginDelete';
    }
}

function plugin_delete_action()
{
    global $plugin_delete, $plugin_delete_name;
    $plugin_delete = new $plugin_delete_name();
    return call_user_func(array(&$plugin_delete, 'action'));
}

?>
