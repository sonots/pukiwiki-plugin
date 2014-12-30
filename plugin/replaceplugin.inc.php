<?php
/**
 * Replace String Plugin Specialized for Replacing Plugins
 *
 * @author     sonots
 * @license    http://www.gnu.org/licenses/gpl.html GPL v2
 * @link       http://lsx.sourceforge.jp/?Plugin%2Freplaceplugin.inc.php
 * @version    $Id: replaceplugin.inc.php,v 1.2 2008-07-26 07:23:17Z sonots $
 * @package    plugin
 */

class PluginReplaceplugin
{
    function PluginReplaceplugin()
    {
        // modify here for default values
        static $conf = array(
            'ignore_freeze' => TRUE,
            'adminonly'     => TRUE,
        );
        static $default_options = array(
            'pcmd'          => '',
            'pass'          => '',
            'fromhere'      => '',
            'filter'        => '',
            'page'         => '',
            'plugintype'    => 'block',
            'oldplugin'     => '',
            'newplugin'     => '',
            'oldoption'     => '',
            'optionnum'        => '',
            'newoption'     => '',
            'regexp'        => FALSE,
            'notimestamp'   => TRUE,
        );
        $this->conf = & $conf;
        $this->default_options = & $default_options;

        // init
        $this->options = $this->default_options;
        $this->view =  new PluginReplacepluginView(&$this);
    }

    // static
    var $conf;
    var $default_options;
    // var
    var $error = '';
    var $plugin = 'replaceplugin';
    var $options = array();
    var $view;
    var $preg_replace;
    var $str_replace;

    function action()
    {
        if (ini_get('safe_mode') == '0') set_time_limit(0);
        $this->set_options();
        return $this->body();
    }

    function set_options()
    {
        global $vars;
        foreach ($this->options as $key => $val) {
            $this->options[$key] = isset($vars[$key]) ? $vars[$key] : '';
        }
    }

    function body()
    {
        foreach ($this->options as $key => $val) {
            ${$key} = $val;
        }

        if ($pcmd == '') {
            $body = $this->view->showform();
        } elseif ($oldplugin == '') {
            $body = $this->view->showform('No Plugin Name.');
        } elseif ($newplugin == '' && $oldoption == '' && $optionnum == '') {
            $body = $this->view->showform('No New Plugin Name and No Target Option.');
        } elseif (! $this->view->login()) { // auth::check_role('role_adm_contents')
            $body = $this->view->showform('The password is wrong.');
        } else {
            if ($pcmd == 'preview') {
                $body = $this->do_preview();
            } elseif ($pcmd == 'replace') {
                $pages = $this->do_replace_all();
                $body = $this->view->result($pages);
            }
        }
        return array('msg'=>$this->plugin, 'body'=>$body);
    }

    function do_preview()
    {
        foreach ($this->options as $key => $val) {
            ${$key} = $val;
        }
        $diff = '';
        $pages = $this->get_pages($filter, $page, $fromhere);
        foreach ($pages as $apage) {
            if (($replace = $this->replace($apage)) == '') {
                continue;
            }
            $source = implode("", get_source($apage));
            $diff = do_diff($source, $replace);
            break;
        }
        $this->options['fromhere'] = $apage;
        $body = $this->view->preview($apage, $diff);
        return $body;
    }

    function do_replace_all()
    {
        foreach ($this->options as $key => $val) {
            ${$key} = $val;
        }
        global $cycle;
        $pages = $this->get_pages($filter, $page, $fromhere);
        $replaced_pages = array();
        $found = 0;
        foreach ($pages as $apage) {
            if (($replace = $this->replace($apage)) == '') {
                continue;
            }
            $cycle = 0;
            set_time_limit(30);
            page_write($apage, $replace, $notimestamp);
            $replaced_pages[] = $apage;
        }
        return $replaced_pages;
    }

    /////////////////////////////
    function replace($apage)
    {
        foreach ($this->options as $key => $val) {
            ${$key} = $val;
        }
        list($targets, $linenums) = $this->retrieve_plugins($apage);
        $replaces = $this->replace_plugin_names($oldplugin, $newplugin, $targets);
        $replaces = $this->replace_plugin_options($oldoption, $optionnum, $newoption, $regexp, $replaces);

        foreach ($targets as $i => $target) {
            if ($targets[$i] == $replaces[$i]) {
                unset($targets[$i]);
                unset($linenums[$i]);
                unset($replaces[$i]);
            }
        }
        if (empty($targets)) return '';

        $lines = get_source($apage);
        foreach ($linenums as $i => $linenum) {
            $lines[$linenum] = 
                str_replace($targets[$i], $replaces[$i], $lines[$linenum]);
        }
        $replace = implode("", $lines);
        /*
        $source = implode("", get_source($apage));
        $replace = str_replace($targets, $replaces, $source);
        if ($source == $replace) return '';*/
        return $replace;
    }

    function replace_plugin_options($oldoption, $optionnum, $newoption, $regexp, $plugin_strs)
    {
        if ($oldoption == '' && $optionnum == '') {
            return $plugin_strs;
        }

        $search = '/^([^(]+)(?:\(([^\r]*)\))(.*)$/';
        foreach ($plugin_strs as $i => $line) {
            $matches = array();
            if (preg_match($search, $line, $matches) == 0) {
                continue;
            }
            $head    = &$matches[1];
            $options = &$matches[2];
            $tail    = &$matches[3];
            $options = csv_explode(',', $options);
            foreach ($options as $j => $option) {
                if (strpos($option, ',') !== FALSE) {
                    $options[$j] = '"' . $option . '"'; // csv recover
                }
            }
            if ($oldoption != '') {
                foreach ($options as $j => $option) {
                    if ($options[$j] == '') continue;
                    if ($regexp) {
                        $options[$j] = preg_replace('/' . str_replace('/', '\/', $oldoption) . '/', $newoption, $options[$j]);
                    } else {
                        $options[$j] = ($oldoption == $option) ? $newoption : $options[$j];
                        //$options[$j] = str_replace($oldoption, $newoption, $options[$j]);
                    }
                    if ($options[$j] == '') unset($options[$j]);
                }
            } else {
                $num = ($optionnum >= 0) ? $optionnum : count($options) + $optionnum;
                if ($regexp) {
                    $options[$num] = preg_replace(preg_quote($options[$num], '/'), $newoption, $options[$num]);
                } else {
                    $options[$num] = $newoption;
                }                    
                if ($options[$num] == '') unset($options[$num]);
            }
            // no csv implode.
            $options = implode(',', $options);
            $plugin_strs[$i] = "$head($options)$tail";
        }
        return $plugin_strs;
    }

    function replace_plugin_names($oldplugin, $newplugin, $plugin_strs)
    {
        if ($newplugin == '') {
            return $plugin_strs;
        }
        $search = '/^([#&])' . preg_quote($oldplugin, '/') . '(.*)$/';
        $replace = '${1}' . $newplugin . '${2}';
        foreach ($plugin_strs as $i => $line) {
            $plugin_strs[$i] = preg_replace($search, $replace, $plugin_strs[$i]);
        }
        return $plugin_strs;
    }

    function retrieve_plugins($apage)
    {
        $oldplugin = $this->options['oldplugin'];
        $plugintype = $this->options['plugintype'];

        if (! $this->is_editable($apage)) {
            return array();
        }
        $searches = array();
        if ($plugintype == 'block') {
            // #listbox2(hoge,hoge)
            $searches[] = '/^(#' . preg_quote($oldplugin, '/') . '(?:\(([^\r]*)\))?(\{*))$/';
            // |hoge|#listbox2(hoge,hoge)|
            $searches[] = '/(?:^\|(?:[^|]*\|)*)(#' . preg_quote($oldplugin, '/') . '(?:\(([^\r]*)\))?)(?:\|(?:[^|]*\|)*$)/';
        } elseif ($plugintype == 'inline') {
            // hogehoge&listbox2(hoge,hoge);hoge
            $searches[] = '/(&' . preg_quote($oldplugin, '/') . '(?:\(([^;\r]*)\))?(\{([^;\r]*)\})?;)/';
        }
        $lines = get_source($apage);
        $plugin_strs = array();
        $linenums = array();
        foreach ($lines as $linenum => $line) {
            foreach ($searches as $search) {
                $matches = array();
                preg_match_all($search, $line, $matches);
                foreach ($matches[1] as $match) {
                    $linenums[]    = $linenum;
                    $plugin_strs[] = $match;
                }
            }
        }
        return array($plugin_strs, $linenums);
    }

    function is_editable($apage)
    {
        global $cantedit;
        if ($this->conf['ignore_freeze']) {
            $editable = ! in_array($apage, $cantedit);
        } else {
            $editable = (! is_freeze($apage) and ! in_array($apage, $cantedit) );
        }
        return $editable;
    }

    function get_pages($filter = '', $page = '', $fromhere = '')
    {
        if ($page != '') {
            return array($page);
        }
        $pages = get_existpages(); //auth::get_existpages();
        if ($filter != '') {
            foreach($pages as $file => $apage) {
                if (! preg_match('/' . str_replace('/', '\/', $filter) . '/', $apage)) {
                    unset($pages[$file]);
                }
            }
        }
        if ($fromhere != '') {
            foreach ($pages as $i => $apage) {
                if ($apage != $fromhere) {
                    unset($pages[$i]);
                } else {
                    break;
                }
            }
        }
        return $pages;
    }
}

//////////////////////////////////
class PluginReplacepluginView
{
    var $plugin = 'replaceplugin';
    var $options;
    var $conf;
    var $model;

    function PluginReplacepluginView($model)
    {
        $this->options = &$model->options;
        $this->conf    = &$model->conf;
        $this->model   = &$model;
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
        foreach ($pages as $apage) {
            $links[] = make_pagelink($apage);
        }
        $msg = implode("<br />\n", $links);
        $body = '<p>The following pages were replaced.</p><div>' . $msg . '</div>';
        return $body;
    }

    function preview($apage, $diff)
    {
        global $script;
        if ($apage == '' || $diff == '') {
            return '<div>No page found or nothing changed.</div>';
        } 
        unset($this->options['pass']);
        unset($this->options['pcmd']);
        foreach ($this->options as $key => $val) {
            $this->setcookie($key, $val);
        }

        $msg = '<div>A preview, <b>' . make_pagelink($apage) . '</b></div>';
        //$diff = '<pre>' . htmlspecialchars($diff) . '</pre>';
        $msg .= '<pre>' . diff_style_to_css(htmlspecialchars($diff)) . '</pre>'; // Pukiwiki API

        $form = array();
        $form[] = '<form action="' . $script . '?cmd=replaceplugin" method="post">';
        $form[] = '<div>';
        $form[] = ' Do you want to replace all pages? ';
        $form[] = ' <input type="hidden" name="cmd"  value="replaceplugin" />';
        $form[] = ' <input type="hidden" name="pcmd"  value="replace" />';
        foreach ($this->options as $key => $val) {
            $form[] = ' <input type="hidden" name="' . $key . '" value="' . $val . '" />';
        }
        $form[] = ' <input type="submit" name="submit" value="Yes" /><br />';
        $form[] = '</div>';
        $form[] = '</form>';
        $form = implode("\n", $form);
        return $msg . $form;
    }

    function showform($msg = "")
    {
        static $text = array(); if (empty($text)) { $text = array(
          'label' => array(
             'pass'        => _('Admin Password'),
             'filter'      => _('Filter Pages'),
             'except'      => _('Except Pages'),
             'page'        => _('A Page'),
             'oldplugin'   => _('Old Plugin Name'),
             'newplugin'   => _('New Plugin Name'),
             'plugintype'  => _('Plugin Type'),
             'oldoption'   => _('Old Option Name'),
             'optionnum'   => _('Option Number'),
             'newoption'   => _('New Option Name'),
             'regexp'      => _('Regexp'),
             'notimestamp' => _('notimestamp'),
             'preview'     => _('Preview'),
          ),
          'text' => array(
             'pass'        => '',
             'filter'      => 'Filter pages to be processed by regular expression. <br />Ex) "^PukiWiki" =&gt; all pages starting with "PukiWiki." No filter(s) => all pages.',
             'except'      => 'Except pages by regular expression.',
             'page'        => 'Specify a page to be processed. If this field is specified, "Filter Pages" is ignored.',
             'oldplugin'   => 'Type the target plugin name',
             'newplugin'   => 'Type the new plugin name',
             'plugintype'  => 'Choose the plugin type which is used to replace the plugin name.',
             'oldoption'   => 'Type the target option name. You may use a regular expression. "Old Option Name" has a priority than "Option Number."',
             'optionnum'   => 'Choose the target option by number. Negative means from the end. Ex) #ls(a,link) => 0 is "a", 1 is "link"',
             'newoption'   => 'Type the new option name.',
             'regexp'      => 'Use regular expression to replace the option name.',
             'notimestamp' => 'Do not change timestamps.',
             'preview'     => '',
          ),
        );}
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
            $form[] = '<tr><td class="style_td">' . $text['label']['pass'] . 
                '</td><td class="style_td"><input type="password" name="pass" size="24" value="' . $pass . '" />' . 
                '</td><td class="style_td">' . $text['text']['pass'] . '</td></tr>';
        }
        $form[] = '<tr><td class="style_td">' . $text['label']['filter'] . 
            '</td><td class="style_td"><input type="text" name="filter" size="42" value="' . $filter . '" />' .
            '</td><td class="style_td">' . $text['text']['filter'] . '</td></tr>';
        $form[] = '<tr><td class="style_td">' . $text['label']['except'] . 
            '</td><td class="style_td"><input type="text" name="except" size="42" value="' . $except . '" />' .
            '</td><td class="style_td">' . $text['text']['except'] . '</td></tr>';
        $form[] = '<tr><td class="style_td">' . $text['label']['page'] . 
            '</td><td class="style_td"><input type="text" name="page" size="42" value="' . $page . '" />' . 
            '</td><td class="style_td">' . $text['text']['page'] . '</td></tr>';

        $form[] = '<tr><td class="style_td"></td><td class="style_td"></td><td class="style_td"></td></tr>';

        $form[] = '<tr><td class="style_td">' . $text['label']['plugintype'] . 
            '</td><td class="style_td">' . 
            '<input type="radio" name="plugintype" value="inline" id="inline" /><label for="inline">inline</label>' .
            '<input type="radio" name="plugintype" value="block" id="block" checked="checked" /><label for="block">block</label>' .
            '</td><td class="style_td">' . $text['text']['plugintype'] . '</td></tr>';
        $form[] = '<tr><td class="style_td">' . $text['label']['oldplugin'] . 
            '</td><td class="style_td"><input type="text" name="oldplugin" size="42" value="' . $oldplugin . '" />' .
            '</td><td class="style_td">' . $text['text']['oldplugin'] . '</td></tr>';
        $form[] = '<tr><td class="style_td">' . $text['label']['newplugin'] . 
            '</td><td class="style_td"><input type="text" name="newplugin" size="42" value="' . $newplugin . '" />' .
            '</td><td class="style_td">' . $text['text']['newplugin'] . '</td></tr>';

        $form[] = '<tr><td class="style_td"></td><td class="style_td"></td><td class="style_td"></td></tr>';

        $form[] = '<tr><td class="style_td">' . $text['label']['oldoption'] . 
            '</td><td class="style_td"><input type="text" name="oldoption" size="42" value="' . $oldoption . '" />' .
            '</td><td class="style_td">' . $text['text']['oldoption'] . '</td></tr>';
        $form[] = '<tr><td class="style_td">' . $text['label']['optionnum'] . 
            '</td><td class="style_td">' .
            '<input type="radio" name="optionnum" id="arg0" value="0" /><label for="arg0">0</label>' .
            '<input type="radio" name="optionnum" id="arg1" value="1" /><label for="arg1">1</label>' .
            '<input type="radio" name="optionnum" id="arg2" value="2" /><label for="arg2">2</label>' .
            '<input type="radio" name="optionnum" id="arg3" value="3" /><label for="arg3">3</label>' .
            '<input type="radio" name="optionnum" id="arg4" value="4" /><label for="arg4">4</label>' .
            '<input type="radio" name="optionnum" id="arg-5" value="-5" /><label for="arg-5">5</label>' .
            '<input type="radio" name="optionnum" id="arg-4" value="-4" /><label for="arg-4">-4</label>' .
            '<input type="radio" name="optionnum" id="arg-3" value="-3" /><label for="arg-3">-3</label>' .
            '<input type="radio" name="optionnum" id="arg-2" value="-2" /><label for="arg-2">-2</label>' .
            '<input type="radio" name="optionnum" id="arg-1" value="-1" /><label for="arg-1">-1</label>' .
            '</td><td class="style_td">' . $text['text']['optionnum'] . '</td></tr>';
        $form[] = '<tr><td class="style_td">' . $text['label']['newoption'] . 
            '</td><td class="style_td"><input type="text" name="newoption" size="42" value="' . $newoption . '" />' .
            '</td><td class="style_td">' . $text['text']['newoption'] . '</td></tr>';
        $form[] = '<tr><td class="style_td"><label for="regexp">' . $text['label']['regexp'] . '</label>' . 
            '</td><td class="style_td"><input type="checkbox" name="regexp" id="regexp" value="on"' . $regexp . '/>' .
            '</td><td class="style_td">' . $text['text']['regexp'] . '</td></tr>';
        $form[] = '</tbody></table></div>';
        $form[] = '<div>';
        $form[] = ' <input type="hidden" name="cmd"  value="replaceplugin" />';
        $form[] = ' <input type="hidden" name="pcmd"  value="preview" />';
        $form[] = ' <input type="checkbox" name="notimestamp" id="notimestamp" value="on"' . $notimestamp . '/>' . ' ' . $text['text']['notimestamp'] . ' ';
        $form[] = ' <input type="submit" name="submit" id="preview" value="' . $text['label']['preview'] . '" />';
        $form[] = '</div>';
        $form[] = '</form>';
        $form = implode("\n", $form);
   
        $tips = array();
        $tips[] = '<h2>Example</h2>';
        $tips[] = '<p>Target: #ls2(PukiWiki,hoge=Hoge,title)</p>';
        $tips[] = '<div class="ie5"><table class="style_table" cellspacing="1" border="0"><tbody>';
        $tips[] = '<tr><th class="style_th">Old Plugin Name</th><th class="style_th">New Plugin Name</th><th class="style_th">Old Option Name</th><th class="style_th">Option Number</th><th class="style_th">New Option Name</th><th class="style_th">Regexp</th><th class="style_th">Result</th></tr>';
        $tips[] = '<tr><td class="style_td">ls2</td><td class="style_td">lsx</td><td class="style_td"></td><td class="style_td"></td><td class="style_td"></td><td class="style_td"></td><td class="style_td">#lsx(PukiWiki,hoge=Hoge,title)</td></tr>';
        $tips[] = '<tr><td class="style_td">ls2</td><td class="style_td">lsx</td><td class="style_td">title</td><td class="style_td"></td><td class="style_td">contents</td><td class="style_td">off</td><td class="style_td">#lsx(PukiWiki,hoge=Hoge,contents)</td></tr>';
        $tips[] = '<tr><td class="style_td">ls2</td><td class="style_td"></td><td class="style_td">^hoge=(.*)$</td><td class="style_td"></td><td class="style_td">fuga=\1</td><td class="style_td">on</td><td class="style_td">#ls2(PukiWiki,fuga=Hoge,title)</td></tr>';
        $tips[] = '<tr><td class="style_td">ls2</td><td class="style_td">lsx</td><td class="style_td"></td><td class="style_td">0</td><td class="style_td">prefix=\0</td><td class="style_td">on</td><td class="style_td">#lsx(prefix=PukiWiki,hoge=Hoge,title)</td></tr>';
        $tips[] = '</tbody></table></div>';
        $tips = implode("\n", $tips);

        if ($msg != '') {
            $msg = '<p><b>' . $msg . '</b></p>';
        }
        return $msg . $form . $tips;
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

//////////////////////////////////
function plugin_replaceplugin_common_init()
{
    global $plugin_replaceplugin;
    if (class_exists('PluginReplacepluginUnitTest')) {
        $plugin_replaceplugin = new PluginReplacepluginUnitTest();
    } elseif (class_exists('PluginReplacepluginUser')) {
        $plugin_replaceplugin = new PluginReplacepluginUser();
    } else {
        $plugin_replaceplugin = new PluginReplaceplugin();
    }
}

function plugin_replaceplugin_action()
{
    global $plugin_replaceplugin; plugin_replaceplugin_common_init();
    return call_user_func(array(&$plugin_replaceplugin, 'action'));
}

?>
