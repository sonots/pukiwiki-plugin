<?php
require_once(dirname(__FILE__) . '/sonots/sonots.class.php');
require_once(dirname(__FILE__) . '/sonots/tag.class.php');
if (! defined('INIT_DIR')) // if not Plus! 
    if (file_exists(DATA_HOME . 'init/tag.ini.php')) 
        include_once(DATA_HOME . 'init/tag.ini.php');
//error_reporting(E_ALL);

/**
 *  Tagging Plugin
 *
 *  @package    plugin
 *  @license    http://www.gnu.org/licenses/gpl.html GPL v2
 *  @author     sonots <http://lsx.sourceforge.jp>
 *  @link       http://lsx.sourceforge.jp/?Plugin%2Ftag.inc.php
 *  @version    $Id: tag.inc.php,v 2.0 2008-06-05 07:23:17Z sonots $
 *  @require    sonots/sonots    v1.9
 *  @require    sonots/tag       v1.0
 *  @compatible tagcould.inc.php v2.0
 *  @compatible taglist.inc.php  v2.0
 */
class PluginTag
{
    function PluginTag()
    {
        static $conf = array();
        if (empty($conf)) {
            $conf['use_session'] = TRUE;
            $conf['use_authlog'] = TRUE;
        }
        $this->conf = & $conf;
    }

    var $conf;
    var $plugin = 'tag';

    /**
     * Inline Plugin Main Function
     */
    function inline() // tagging
    {
        static $tagging = FALSE;
        if (func_num_args() == 0){
            return 'tag(): no argument(s). ';
        }
        global $vars, $defaultpage; 
        $page = isset($vars['page']) ? $vars['page'] : $defaultpage;
        $args = func_get_args(); 
        array_pop($args);  // drop {}
        $tags = $args;
        $tags = sonots::trim_array($tags, true, true);
        
        $pkwk_tag = new PluginSonotsTag();
        if ($tagging) { // 2nd call
            $pkwk_tag->add_tags($page, $tags);
        } elseif (isset($vars['preview']) || isset($vars['realview']) ||
                  sonots::is_page_newer($page, PluginSonotsTag::get_tags_filename($page))) {
            $pkwk_tag->save_tags($page, $tags);
            $tagging = TRUE;
        }
        return $this->display_tagging($tags);
    }

    /**
     * Display tagging // not display_tags
     *
     * @param array $tags
     * @param string $basehref
     * @return string HTML
     */
    function display_tagging($tags)
    {
        $ret = '<span class="tag">';
        $ret .= 'Tag: ';
        $pkwk_tag = new PluginSonotsTag();
        foreach ($tags as $tag) {
            $ret .= $pkwk_tag->make_taglink($tag);
        }
        $ret .= '</span>';

        global $head_tags;
        $head_tags[] = ' <meta name="keywords" content="' . 
            htmlspecialchars(implode(', ', $tags)) . '" />';

        return $ret;
    }

    /**
     * Action Plugin Main Funtion
     */
    function action() // clean cache
    {
        global $vars;
        if (sonots::is_admin($vars['pass'], $this->conf['use_session'], $this->conf['use_authlog'])) {
            $body = $this->clean_cache();
        } else {
            $action = get_script_uri() . '?cmd=' . $this->plugin . '&pcmd=clean';
            $body = sonots::display_password_form($action);
        }
        return array('msg'=>'Clean Tag Caches', 'body'=>$body);
    }
    
    /**
     * Clean Tag Caches
     *
     * @return string HTML
     */
    function clean_cache()
    {
        set_time_limit(0);
        global $vars;
        
        // remove all files
        $pkwk_tag = new PluginSonotsTag();
        $files = $pkwk_tag->get_items_filenames();
        $files = array_merge($files, $pkwk_tag->get_tags_filenames());
        $files[] = $pkwk_tag->get_tagcloud_filename();
        foreach ($files as $file) {
            unlink($file);
        }
        // execute all pages
        $exec_pages = sonots::exec_existpages('', '/&tag\([^;]*\);/');
        if (empty($exec_pages)) {
            $html = '';
        } else {
            $links = array_map('make_pagelink', $exec_pages);
            $html = '<p>Following pages were executed to assure:</p>'
                . '<p>' . implode("<br />\n", $links) . '</p>';
        }
        $html .= $pkwk_tag->display_tagcloud();
        return $html;
    }

    /**
     * Experimental: Write After Plugin Main Function
     *
     * @param string &$page
     * @param string &$postdata
     * @param boolean &$notimestamp
     * @return void or exit
     */
    function write_after()
    {
        $args = func_get_args();
        $page = $args[0];
        $postdata = $args[1];
        if ($postdata == "") { // if page is deleted
            $pkwk_tag = new PluginSonotsTag();
            $pkwk_tag->save_tags($page, array()); // remove tags
        }
        // ToDo: renew tag cache on write_after, not on read
        // Since the whole text must be parsed to find '&tag();',
        // it is not realistic. 
        // Must create a separated form for Tags to avoid this load. 
    }

    /**
     * Experimental: Plugin for Rename Plugin Main Function
     *
     * @param array $pages $oldpage => $newpage
     * @return void or exit
     */
    function rename_plugin()
    {
        $args = func_get_args();
        $pages = $args[0];
        $pkwk_tag = new PluginSonotsTag();
        foreach ($pages as $oldpage => $newpage) {
            $pkwk_tag->rename_item($oldpage, $newpage);
        }
    }
}

////////////////////////////////
function plugin_tag_init()
{
    global $plugin_tag_name;
    if (class_exists('PluginTagUnitTest')) {
        $plugin_tag_name = 'PluginTagUnitTest';
    } elseif (class_exists('PluginTagUser')) {
        $plugin_tag_name = 'PluginTagUser';
    } else {
        $plugin_tag_name = 'PluginTag';
    }
}

function plugin_tag_inline()
{
    global $plugin_tag, $plugin_tag_name;
    $plugin_tag = new $plugin_tag_name();
    $args = func_get_args();
    return call_user_func_array(array(&$plugin_tag, 'inline'), $args);
}

function plugin_tag_convert()
{
    global $plugin_tag, $plugin_tag_name;
    $plugin_tag = new $plugin_tag_name();
    $args = func_get_args();
    return call_user_func_array(array(&$plugin_tag, 'convert'), $args);
}

function plugin_tag_action()
{
    global $plugin_tag, $plugin_tag_name;
    $plugin_tag = new $plugin_tag_name();
    return call_user_func(array(&$plugin_tag, 'action'));
}

function plugin_tag_write_after()
{
    global $plugin_tag_name; 
    $plugin_tag = new $plugin_tag_name();
    $args = func_get_args();
    return call_user_func_array(array(&$plugin_tag, 'write_after'), $args);
}

function plugin_tag_rename_plugin()
{
    global $plugin_tag_name; 
    $plugin_tag = new $plugin_tag_name();
    $args = func_get_args();
    return call_user_func_array(array(&$plugin_tag, 'rename_plugin'), $args);
}
?>
