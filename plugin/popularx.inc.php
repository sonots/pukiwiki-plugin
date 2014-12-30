<?php
require_once(dirname(__FILE__) . '/sonots/sonots.class.php');
require_once(dirname(__FILE__) . '/sonots/option.class.php');
require_once(dirname(__FILE__) . '/sonots/pagelist.class.php');
require_once(dirname(__FILE__) . '/sonots/metapage.class.php');
//error_reporting(E_ALL);

/**
 * Popular Plugin eXtension
 *
 * @package    plugin
 * @license    http://www.gnu.org/licenses/gpl.html GPL v2
 * @author     sonots <http://lsx.sourceforge.jp>
 * @link       http://lsx.sourceforge.jp/?Plugin%2Fpopularx.inc.php
 * @version    $Id: popularx.inc.php,v 2.0 2008-07-18 07:23:17Z sonots $
 * @require    sonots/sonots     v 1.13
 * @require    sonots/option     v 1.8
 * @require    sonots/pagelist   v 1.4
 * @require    sonots/metapage   v 1.11
 */

class PluginPopularx
{
    function PluginPopularx()
    {
        // Configure options
        // array(type, default, config)
        static $conf_options = array(); if (empty($conf_options)) {
            $conf_options = array(
            'tree'      => array('enum', false, array(false, 'leaf', 'dir')),
            'num'       => array('interval', '1:10'),
            'non_list'  => array('bool', true),
            'filter'    => array('string', null),
            'except'    => array('string', null),
            'reverse'   => array('bool', false), 
            'popular'   => array('enum', 'today', array('total', 'yesterday', 'today', 'recent')),
            'next'      => array('bool', false),
            'linkstr'   => array('enum', 'name', array('name', 'page', 'pagename', 'absolute', 'base', 'basename', 'title', 'firsthead', 'headline')),
            'newpage'   => array('enum', null, array('on', 'off', 'except')), // except is obsolete
        );
        }
        $this->conf_options    = &$conf_options;
    }
    
    // static
    var $conf_options;
    var $plugin = "popularx";
    // var

    /**
     * Block Plugin Main Function
     */
    function convert()
    {
        sonots::init_myerror(); do { // try
            $args = func_get_args(); $argline = csv_implode(',', $args);
            $argoptions = PluginSonotsOption::parse_option_line($argline);
            list($options, $unknowns) = PluginSonotsOption::evaluate_options($argoptions, $this->conf_options);
            $options = $this->check_options($options, $unknowns, $this->conf_options);
            if (sonots::mycatch()) break;

            $pages = get_existpages();
            $html = $this->pagelist($pages, $options, $argoptions);
            if (empty($html)) $html = '<p>' . _('No page found.') . '</p>';
            return $html;
        } while (false);
        if (sonots::mycatch()) { // catch
            return '<p>#' . $this->plugin . '(): ' . sonots::mycatch() . '</p>';
        }
    }

    /**
     * Action Plugin Main Function
     */
    function action()
    {
        sonots::init_myerror(); do { // try
            global $vars;
            $argoptions = PluginSonotsOption::parse_uri_option_line($vars);
            $argoptions = array_intersect_key($argoptions, $this->conf_options);
            list($options, $unknowns) = PluginSonotsOption::evaluate_options($argoptions, $this->conf_options);
            $options = $this->check_options($options, array(), $this->conf_options);
            if (sonots::mycatch()) break;

            $pages = get_existpages();
            $title = $this->plugin;
            $html = $this->pagelist($pages, $options, $argoptions);
            return array('msg'=>$title, 'body'=>$html);
        } while(false);
        if (sonots::mycatch()) { // catch
            return array('msg'=>$this->plugin, 'body'=>'<p>' . sonots::mycatch() . '</p>');
        }
    }

    /**
     * Check option validities and compatibilities
     *
     * @param array $options
     * @param array $unknown unknown options
     * @param array $conf_options
     * @return array
     */
    function check_options($options, $unknowns, $conf_options)
    {
        global $vars;

        // first arg
        if (count($unknowns) > 0) {
            $unknown_keys = array_diff_key($unknowns, $conf_options);
            $key = key($unknown_keys);
            if (in_array($key, $conf_options['popular'][2])) {
                $options['popular'] = $key;
                unset($unknowns[$key]);
            }
        }
        if (count($unknowns) > 0) {
          $line = PluginSonotsOption::glue_option_line($unknowns);
          sonots::mythrow('Argument(s) "' . htmlspecialchars($line) . '" are invalid');
          return;
        }

        return $options;
    }

    /**
     * List pages
     *
     * @param array $pages
     * @param array $options
     * @param array $argoptions
     * @return string html
     */
    function pagelist($pages, $options, $argoptions)
    {
        $pagelist = new PluginSonotsPagelist($pages);

        if ($options['non_list']) {
            $pattern = '/' . $GLOBALS['non_list'] . '/';
            $pagelist->grep_by('page', 'preg', $pattern, true); // inverse
        }
        if (isset($options['filter']) && $options['filter'] !== '') {
            $pagelist->grep_by('relname', 'preg', '/' . str_replace('/', '\/', $options['filter']) . '/');
        }
        if (isset($options['except'])) {
            $pagelist->grep_by('relname', 'preg', '/' . str_replace('/', '\/', $options['except']) . '/', true); // inverse
        }
        if (isset($options['newpage'])) {
            switch ($options['newpage']) {
            case 'on':
                $pagelist->gen_metas('newpage');
                $pagelist->grep_by('newpage', 'eq', true);
                break;
            case 'except':
            case 'off':
                $pagelist->gen_metas('newpage');
                $pagelist->grep_by('newpage', 'eq', false);
                break;
            default:
                break;
            }
        }
        if (isset($options['depth']) || $options['tree'] ) {
            $pagelist->gen_metas('depth');
        }
        if (isset($options['depth'])) {
            // do not use negative interval for depth
            list($min, $max) = PluginSonotsOption::conv_interval($options['depth'], array(1, PHP_INT_MAX));
            $pagelist->grep_by('depth', 'ge', $min);
            $pagelist->grep_by('depth', 'le', $max);
        }
        switch ($options['tree']) {
        case 'leaf':
            $pagelist->gen_metas('leaf');
            $pagelist->grep_by('leaf', 'eq', true);
            break;
        case 'dir':
            $pagelist->gen_metas('leaf');
            $pagelist->grep_by('leaf', 'eq', false);
            break;
        default:
            break;
        }

        if (isset($options['popular'])) {
            $pagelist->gen_metas('popular', array($options['popular']));
        }
        $pagelist->sort_by('popular', $options['reverse']);

        $max = count($pagelist->metapages); // for next option
        if (is_array($options['num'])) {
            list($offset, $length) = $options['num'];
            $pagelist->slice($offset, $length);
        }
        
        //// display
        $pagelist->gen_metas('linkstr', array($options['linkstr']));
        $linkstrs = $pagelist->get_metas('linkstr');
        $pages    = $pagelist->get_metas('page');
        $counts   = $pagelist->get_metas('popular');
        $links    = $this->get_popular_links($pages, $linkstrs, $counts, $GLOBALS['var']['page']);
        if (empty($links)) {
            return '<p>#' . $this->plugin . '(): no counter information is available.</p>';
        }
        $levels   = array_map(create_function('','return 1;'), $links);
        $html = sonots::display_list($links, $levels, 'popularx');
        
        //// display navi. $max is needed, $argoptions is need. 
        if ($options['next'] && is_array($options['num'])) {
            $argoptions['popular'] = $options['popular'];
            unset($argoptions['num']);
            $argoptions = array_intersect_key($argoptions, $options);
            $argline = PluginSonotsOption::glue_uri_option_line($argoptions);
            $basehref = get_script_uri() . '?cmd=' . $this->plugin;
            $basehref .= empty($argline) ? '' : '&amp;' . htmlspecialchars($argline);
            $current = PluginSonotsOption::conv_interval($options['num']);
            $html .= $pagelist->display_navi($current, array(1, $max), $basehref, $this->plugin);
        }

        return $html;
    }
    
    function get_popular_links($pages, $linkstrs, $counts, $currentpage = '')
    {
        $links = array();
        foreach ($pages as $i => $page) {
            $linkstr = htmlspecialchars($linkstrs[$i]);
            $count   = $counts[$i];
            if ($count === 0) continue;
            $linkstr .= '<span class="counter">(' . $count . ')</span>';
            if ($page == $currentpage) { // Do not link to itself
                $title = htmlspecialchars($page) . ' ' . get_pg_passage($page, false);
                $links[$i] = '<span title="' . $title . '">' . $linkstr . '</span>';
            } else {
                $links[$i] = make_pagelink($page, $linkstr);
            }
        }
        return $links;
    }
}

///////////////////////////////////////////
function plugin_popularx_init()
{
    global $plugin_popularx_name;
    if (class_exists('PluginPopularxUnitTest')) {
        $plugin_popularx_name = 'PluginPopularxUnitTest';
    } elseif (class_exists('PluginPopularxUser')) {
        $plugin_popularx_name = 'PluginPopularxUser';
    } else {
        $plugin_popularx_name = 'PluginPopularx';
    }
}
function plugin_popularx_action()
{
    global $plugin_popularx, $plugin_popularx_name;
    $plugin_popularx = new $plugin_popularx_name();
    return call_user_func(array(&$plugin_popularx, 'action'));
}
function plugin_popularx_convert()
{
    global $plugin_popularx, $plugin_popularx_name;
    $plugin_popularx = new $plugin_popularx_name();
    $args = func_get_args();
    return call_user_func_array(array(&$plugin_popularx, 'convert'), $args);
}

if (! defined('INIT_DIR')) // if not Plus! 
    if (file_exists(DATA_HOME . 'init/popularx.ini.php')) 
        include_once(DATA_HOME . 'init/popularx.ini.php');

?>
