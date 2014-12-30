<?php
require_once(dirname(__FILE__) . '/sonots/sonots.class.php');
require_once(dirname(__FILE__) . '/sonots/option.class.php');
require_once(dirname(__FILE__) . '/sonots/toc.class.php');
require_once(dirname(__FILE__) . '/sonots/metapage.class.php');
//error_reporting(E_ALL);

/**
 * Table of Contents Plugin
 *
 * @package    plugin
 * @license    http://www.gnu.org/licenses/gpl.html    GPL
 * @author     sonots <http://lsx.sourceforge.jp>
 * @link       http://lsx.sourceforge.jp/?Plugin%2Fcontentsx.inc.php
 * @version    $Id: contentsx.inc.php,v 2.0 2008-07-15 11:14:46 sonots $
 * @require    sonots/sonots     v 1.11
 * @require    sonots/option     v 1.6
 * @require    sonots/toc        v 1.8
 * @require    sonots/metapage   v 1.8
 */
class PluginContentsx
{
    function PluginContentsx()
    {
        // Configure options
        // array(type, default, config)
        static $conf_options = array(
            'page'      => array('string',   null),
            'fromhere'  => array('bool',     true),
            'hierarchy' => array('bool',     true),
            'compact'   => array('bool',     true),
            'num'       => array('interval', null),
            'depth'     => array('interval', null),
            'except'    => array('string',   null),
            'filter'    => array('string',   null),
            'include'   => array('bool',     true),
            'cache'     => array('bool',     true),
            'link'      => array('enum',     'on',  array('on', 'off', 'anchor', 'page')),
        );
        // Configuration
        static $conf = array(
            'use_session'      => true, // action
            'use_authlog'      => true, // action
        );
        $this->conf            = &$conf;
        $this->conf_options    = &$conf_options;
    }
    
    // static
    var $conf;
    var $conf_options;
    var $plugin = "contentsx";
    // var

    /**
     * Block Plugin Main Function
     */
    function convert()
    {
        sonots::init_myerror(); do { // try
            global $vars, $defaultpage;
            $args = func_get_args(); $line = csv_implode(',', $args);
            $options = PluginSonotsOption::parse_option_line($line);
            list($options, $unknowns) = PluginSonotsOption::evaluate_options($options, $this->conf_options);
            $current = isset($vars['page']) ? $vars['page'] : $defaultpage;
            $page    = isset($options['page']) ? $options['page'] : $current;
            $page    = PluginContentsx::check_page($page, $current);
            $options = PluginContentsx::check_options($page, $current, $options, $unknowns);
            if (sonots::mycatch()) break;

            $html = PluginContentsx::display_toc($page, $options);
            if ($html != '') {
            $html = '<table border="0" class="toc"><tbody>' . "\n"
                . '<tr><td class="toctitle">' . "\n"
                . '<span>' . _('Table of Contents') . "</span>\n"
                . "</td></tr>\n"
                . '<tr><td class="toclist">' . "\n"
                . $html 
                . "</td></tr>\n"
                . "</tbody></table>\n";
            }
            return $html;
        } while (false);
        if (sonots::mycatch()) { // catch
            return '<p>#contentsx(): ' . sonots::mycatch() . '</p>';
        }
    }

    /**
     * Check validity of page
     *
     * @access static
     * @param string $page
     * @param array $options
     * @return $options
     */
    function check_page($page, $current)
    {
        $page = get_fullname($page, $current);
        if (! is_page($page)) {
            sonots::mythrow('Page "' . htmlspecialchars($page) . '" does not exist.');
            return;
        }
        if (! check_readable($page, FALSE, FALSE)) {
            sonots::mythrow('Page "' . htmlspecialchars($page) . '" is not readable.');
            return;
        }
        return $page;
    }
    /**
     * Check validity and compatibility of options
     *
     * @access static
     * @param string $page name to get contents
     * @param string $current showing page name
     * @param array $options
     * @param array $unknowns
     * @return $options
     */
    function check_options($page, $current, $options, $unknowns = array())
    {
        if (count($unknowns) > 0) {
            $line = PluginSonotsOption::glue_option_line($unknowns);
            sonots::mythrow('Argument(s) "' . htmlspecialchars($line) . '" are invalid.');
            return;
        }
        if ($page !== $current) {
            $options['fromhere'] = false;
        }
        // link=on is flexible. Set to true value. 
        if ($options['link'] === 'on') {
            if ($page === $current) {
                $options['link'] = 'anchor';
            } else {
                $options['link'] = 'page';
            }
        }
        return $options;
    }

    /**
     * Display Table of Contents
     *
     * @access static
     * @param string $page
     * @param array $options
     * @return string html
     */
    function display_toc($page, $options)
    {
        $toc = new PluginSonotsToc($page, $options['cache']);

        if ($options['include']) {
            $toc->expand_includes();
        }
        $headlines = $toc->get_headlines();
        if ($options['fromhere']) {
            $fromhere = $toc->get_fromhere();
            $offset = 0; $headline = reset($headlines);
            while (! ($headline->page === $page && $headline->linenum > $fromhere)) {
                ++$offset;
                if (($headline = next($headlines)) === false) break;
            }
            $headlines = sonots::array_slice($headlines, $offset, null, true);
        }
        if (isset($options['filter'])) {
            sonots::grep_by($headlines, 'string', 'preg', '/' . str_replace('/', '\/', $options['filter']) . '/');
        }
        if (isset($options['except'])) {
            sonots::grep_by($headlines, 'string', 'preg', '/' . str_replace('/', '\/', $options['except']) . '/', true); // inverse
        }
        if (is_array($options['depth'])) {
            // Do not use negative offsets
            list($min, $max) = PluginSonotsOption::conv_interval($options['depth'], array(1, PHP_INT_MAX));
            sonots::grep_by($headlines, 'depth', 'ge', $min);
            sonots::grep_by($headlines, 'depth', 'le', $max);
        }
        if (is_array($options['num'])) {
            list($offset, $length) = $options['num'];
            $headlines = sonots::array_slice($headlines, $offset, $length, true);
        }
        
        if ($options['hierarchy']) {
            if ($options['include'] && count($toc->get_includes()) >= 1) {
                // depth of included page is 0, shift up
                sonots::map_members($headlines, 'depth', create_function('$x','return $x+1;'));
            }
            if ($options['compact']) {
                PluginSonotsToc::compact_depth($headlines);
            }
        } else {
            sonots::init_members($headlines, 'depth', 1); // flatten (to all 1)
        }
        $html = PluginSonotsToc::display_toc($headlines, 'contentsx', $options['link']);
        return $html;
    }
    
    /**
     * Action Plugin Main Function
     */
    function action() // clean cache
    {
        set_time_limit(0);
        global $vars;

        if (sonots::is_admin($vars['pass'], $this->conf['use_session'], $this->conf['use_authlog']) && 
            $vars['pcmd'] == 'clean') {
            $html = $this->clean_cache();
        } else {
            $basehref = get_script_uri() . '?cmd=contentsx&pcmd=clean';
            $html = sonots::display_password_form($basehref);
        }
        return array('msg'=>'Clean Contentsx Caches', 'body'=>$html);
    }

    /**
     * Clean Table of Contents Cache Files
     */
    function clean_cache()
    {
        set_time_limit(0);
        global $vars;

        $page = isset($vars['page']) ? $vars['page'] : '';
        if ($page != '') {
            $toc = new PluginSonotsToc();
            $file = $toc->syntax['cachefile']($page);
            @unlink($file);
            if (exec_page($page, '/^#contentsx/')) {
                $body = 'Recreated a cache of ';
            } else {
                $body = 'No #contentsx in ';
            }
            $body .= make_pagelink($page);
        } else {
            $toc = new PluginSonotsToc();
            $file = $toc->syntax['cachefile']('hoge');
            $suffix = substr($file, strrpos($file, '.'));
            // remove all files
            $files = sonots::get_existfiles(CACHE_DIR, $suffix);
            foreach ($files as $file) {
                unlink($file);
            }
            // execute all pages
            $exec_pages = sonots::exec_existpages('', '/^#contentsx/');
            $links = array_map('make_pagelink', $exec_pages);
            $body = '<p>Following pages were executed to assure:</p>'
                . '<p>' . implode("<br />\n", $links) . '</p>';
        }
        return $body;
    }
}

///////////////////////////////////////////
function plugin_contentsx_init()
{
    global $plugin_contentsx_name;
    if (class_exists('PluginContentsxUnitTest')) {
        $plugin_contentsx_name = 'PluginContentsxUnitTest';
    } elseif (class_exists('PluginContentsxUser')) {
        $plugin_contentsx_name = 'PluginContentsxUser';
    } else {
        $plugin_contentsx_name = 'PluginContentsx';
    }
}
function plugin_contentsx_action()
{
    global $plugin_contentsx, $plugin_contentsx_name;
    $plugin_contentsx = new $plugin_contentsx_name();
    return call_user_func(array(&$plugin_contentsx, 'action'));
}
function plugin_contentsx_convert()
{
    global $plugin_contentsx, $plugin_contentsx_name;
    $plugin_contentsx = new $plugin_contentsx_name();
    $args = func_get_args();
    return call_user_func_array(array(&$plugin_contentsx, 'convert'), $args);
}

if (! defined('INIT_DIR')) // if not Plus! 
    if (file_exists(DATA_HOME . 'init/contentsx.ini.php')) 
        include_once(DATA_HOME . 'init/contentsx.ini.php');

?>
