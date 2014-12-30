<?php
/**
 * Remake or Cleanup Wiki Pages
 *
 * @author     sonots
 * @license    http://www.gnu.org/licenses/gpl.html GPL v2
 * @link       http://lsx.sourceforge.jp/?Plugin%2Fremakepage.inc.php
 * @version    $Id: remakepage.inc.php,v 1.7 2007-06-05 07:23:17Z sonots $
 * @package    plugin
 */

class PluginRemakepage
{
    function PluginRemakepage()
    {
        $this->cachefile = CACHE_DIR . 'remakepage.dat';
        $this->view    = new PluginRemakepageView();
    }

    var $cachefile;
    var $error = '';
    var $plugin = 'remakepage';
    var $options = array();
    var $view;
    var $def_headline = '/^(\*{1,3})/';

    function action()
    {
        if (ini_get('safe_mode') == '0') set_time_limit(0);
        $this->set_options();
        if (! isset($this->options['pcmd'])) {
            $msg = "";
        } elseif (! $this->view->login()) {
            $msg = "<p><b>The password is wrong. </b></p>\n";
        } else {
            switch ($this->options['pcmd']) {
            case 'chown':
                $msg = $this->chown();
                break;
            case 'fixed_anchor':
                $msg = $this->fixed_anchor();
                break;
            case 'save_time':
                $msg = $this->save_time();
                break;
            case 'restore_time':
                $msg = $this->restore_time();
                break;
            case 'exec':
                $msg = $this->exec();
                break;
            }
        }
        $body = $this->view->showform($msg);
        return array('msg'=>$this->plugin, 'body'=>$body);
    }
    
    function set_options()
    {
        global $vars;
        // i'm lazy :) work more!
        $this->options = $vars;
    }

    function exec()
    {
        global $vars, $get, $post;
        $tmp = $vars['page'];
        $pages = $this->get_pages();
        foreach ($pages as $page) {
            $get['page'] = $post['page'] = $vars['page'] = $page;
            convert_html(get_source($page));
        }
        $get['page'] = $post['page'] = $vars['page'] = $tmp;
        return '<p><b>Exec was done. </b></p>';
    }

    function chown()
    {
        $pages = $this->get_pages();
        foreach ($pages as $page) {
            $file = get_filename($page);
            // this func was created after I suggested, now I can be lazy
            if (pkwk_chown($file, true) === FALSE) {
                return "<p><b>Failed to chown $page. </b></p>";
            }
        }
        return '<p><b>Chown was done. </b></p>';
    }

    function fixed_anchor()
    {
        $pages = $this->get_pages();
        $done = array();
        foreach ($pages as $page) {
            $do = false;
            $lines = get_source($page);
            foreach ($lines as $i => $line) {
                // multiline plugin. refer lib/convert_html
                if(defined('PKWKEXP_DISABLE_MULTILINE_PLUGIN_HACK') && PKWKEXP_DISABLE_MULTILINE_PLUGIN_HACK === 0) {
                    $matches = array();
                    if ($multiline < 2) {
                        if(preg_match('/^#([^\(\{]+)(?:\(([^\r]*)\))?(\{*)/', $line,$matches)) {
                            $multiline  = strlen($matches[3]);
                        }
                    } else {
                        if (preg_match('/^\}{' . $multiline . '}$/', $line, $matches)) {
                            $multiline = 0;
                        }
                        continue;
                    }
                }
                if (preg_match($this->def_headline, $line, $matches)) {
                    $anchor = make_heading($line, FALSE);
                    if ($anchor === '') {
                        $do = true;
                        break;
                    }
                }
            }
            if ($do) {
                $source = join('', $lines);
                page_write($page, $source, true);
                // ->make_str_rules -> generate_fixed_heading_ancher_id
                // chown also
                $done[] = $page;
            }
        }
        $body = '<p>';
        $body .= '<b>Created fixed_heading anchors for followings:</b><br />';
        foreach ($done as $page) {
            $link = make_pagelink($page);
            $body .= $link . "<br />\n";
        }
        $body .= '</p>';
        return $body;
    }

    function save_time()
    {
        $pages = $this->get_pages();
        $contents = "";
        if (! $fp = fopen($this->cachefile, "w")) {
            return "<p><b>timestamp cache file, $this->cachefile, can not open. </b></p>";
        }
        foreach ($pages as $page) {
            $mtime = filemtime(get_filename($page));
            $contents .= csv_implode(',', array($page, $mtime)) . "\n";
        }
        if (! fwrite($fp, $contents)) {
            return "<p><b>can not write to timestamp cache file, $this->cachefile. </b></p>";
        }
        fclose($fp);
        return "<p><b>timestamp cache file, $this->cachefile, was created. </b></p>";
    }
    
    function restore_time()
    {
        $this->cachefile = CACHE_DIR . "remakepage.dat";
        if (($lines = file($this->cachefile)) === FALSE) {
            return "<p><b>timestamp cache file, $this->cachefile, does not exist or not readable. </b></p>";
        }
        $oldpages = array();
        $failedpages = array();
        foreach ($lines as $line) {
            $line = rtrim($line);
            list($page, $time) = csv_explode(',', $line);
            $oldpages[] = $page;
            if(is_page($page) && pkwk_touch_file(get_filename($page), $time) === false) {
                $failedpages[] = $page;
            }
        }
        put_lastmodified();
        
        $body = '<p>';
        $body .= '<b>Restored timestamps.</b><br />';
        $nonexists = array_diff($oldpages, get_existpages());
        if (! empty($nonexists)) {
            $body .= "<b>Following pages do not exist in current wiki,</b><br />\n";
            $body .= implode("<br />\n", $nonexists) . "<br />";
        }
        if (! empty($failedpages)) {
            $body .= "<b>Failed to restore timestamp of </b><br />\n";
            $body .= implode("<br />\n", $failedpages) . "<br />";
            $body .= "<b>Skipped.</b><br />";
        }
        $body .= '</p>';
        return $body;
    }

    function get_pages()
    {
        $pages = array();
        if ($this->options['page'] != '') {
            $pages = array($this->options['page']);
        } else {
            $pages = get_existpages();
            if ($this->options['prefix'] != '') {
                foreach ($pages as $i => $page) {
                    if (strpos($page, $this->options['prefix']) !== 0) {
                        unset($pages[$i]);
                    }
                }
            }
        }
        return $pages;
    }
}

//////////////////////////////////////////
class PluginRemakepageView
{
    var $admincookie = 'adminpass';

    function login()
    {
        global $vars;
        $pass = isset($vars['pass']) ? $vars['pass'] : $this->get_admincookie();
        if (pkwk_login($pass)) {
            $this->set_admincookie($pass);
            return TRUE;
        } else {
            return FALSE;
        }
    }

    function get_admincookie()
    {
        return isset($_COOKIE[$this->admincookie]) ? $_COOKIE[$this->admincookie] : "";
    }
            
    function set_admincookie($pass)
    {
        global $script;
        $path = dirname($scirpt);
        setcookie($this->admincookie, $pass, 0, $path);
        $_COOKIE[$this->admincookie] = $pass;
    }

    function showform($msg = "")
    {
        global $vars;
        foreach (array('prefix', 'page') as $key) {
            ${$key} = isset($vars[$key]) ? $vars[$key] : '';
        }
        if (PLUGIN_DUMP2HTML_ADMINONLY) {
            $pass = $this->get_admincookie();
        }
        $pass = $this->get_admincookie();
        $body = $msg;
        $body  .= <<<EOD
<form action="$script" method="post">
 <div>
  <input type="hidden"   name="cmd"  value="remakepage" />
  <input type="password" name="pass" size="12" value="$pass" />Admin Pasword<br />
  <input type="text"  name="prefix" size="24" value="$prefix" />Prefix of Pages (Leave blank for all)<br />
  <input type="text"  name="page" size="24" value="$page" />Specific One Page<br />
  <input type="radio" name="pcmd" id="chown" value="chown" checked="checked" />
  <label for="chown">Chown owner of pages to apache/php user</label><br />
  <input type="radio" name="pcmd" id="fixed_anchor" value="fixed_anchor" />
  <label for="fixed_anchor">Create fixed heading anchors for pages (This might take too much time to finish)</label><br />
  <input type="radio" name="pcmd" id="exec" value="exec" />
  <label for="exec">Execute all pages (convert_html or read)</label><br />
  <input type="radio" name="pcmd" id="save_time" value="save_time" />
  <label for="save_time">Cache timestamps of pages</label><br />
  <input type="radio" name="pcmd" id="restore_time" value="restore_time" />
  <label for="restore_time">Restore timestamps of pages from cached timestamps</label><br />
  <input type="submit"   name="ok"   value="Submit" /><br />
 </div>
</form>
EOD;
        return $body;
    }
}

///////////////////////////////////////////
function plugin_remakepage_common_init()
{
    global $plugin_remakepage;
    if (class_exists('PluginRemakepageUnitTest')) {
        $plugin_remakepage = new PluginRemakepageUnitTest();
    } elseif (class_exists('PluginRemakepageUser')) {
        $plugin_remakepage = new PluginRemakepageUser();
    } else {
        $plugin_remakepage = new PluginRemakepage();
    }
}

function plugin_remakepage_action()
{
    global $plugin_remakepage; plugin_remakepage_common_init();
    return $plugin_remakepage->action();
}

?>
