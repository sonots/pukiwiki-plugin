<?php
require_once(dirname(__FILE__) . '/sonots/sonots.class.php');
require_once(dirname(__FILE__) . '/sonots/option.class.php');
require_once(dirname(__FILE__) . '/sonots/pagelist.class.php');
require_once(dirname(__FILE__) . '/sonots/metapage.class.php');
require_once(dirname(__FILE__) . '/sonots/tag.class.php');
exist_plugin('new');           // new option
exist_plugin('contentsx');     // contents option
exist_plugin('includex');      // include option
//error_reporting(E_ALL);

/**
 * Page List (ls) Plugin
 *
 * @package    plugin
 * @license    http://www.gnu.org/licenses/gpl.html GPL v2
 * @author     sonots <http://lsx.sourceforge.jp>
 * @link       http://lsx.sourceforge.jp/?Plugin%2Flsx.inc.php
 * @version    $Id: lsx.inc.php,v 2.1 2008-08-19 11:14:46 sonots $
 * @require    sonots/sonots     v 1.13
 * @require    sonots/option     v 1.11
 * @require    sonots/pagelist   v 1.6
 * @require    sonots/metapage   v 1.11
 * @require    sonots/tag        v 1.0
 * @require    contentsx         v 2.0
 * @require    includex          v 2.0
 */

class PluginLsx
{
    function PluginLsx()
    {
        // Configure options
        // array(type, default, config)
        static $conf_options = array(); if (empty($conf_options)) {
            $contents = new PluginContentsx();
            $include = new PluginIncludex();
            $conf_options = array(
            'prefix'    => array('string', null),
            'hierarchy' => array('bool', true),
            'tree'      => array('enum', false, array(false, 'leaf', 'dir')),
            'depth'     => array('interval', null),
            'num'       => array('interval', null),
            'non_list'  => array('bool', true),
            'filter'    => array('string', null),
            'except'    => array('string', null),
            'sort'      => array('enum', 'name', array('name', 'reading', 'date', 'popular', 'title')),
            'reverse'   => array('bool', false), // option of sort option
            'popular'   => array('enum', 'today', array('today', 'total', 'yesterday', 'recent')), // option of sort option
            'next'      => array('bool', false),
            'contents'  => array('options', null, $contents->conf_options),
            'include'   => array('options', null, $include->conf_options),
            'info'      => array('enumarray', null, array('date', 'new')),
            'date'      => array('bool', false), // will be obsolete
            'new'       => array('bool', false),
            'tag'       => array('string', null),
            'basename'  => array('bool', false), // obsolete
            'linkstr'   => array('enum', 'relative', array('relative', 'relname', 'name', 'page', 'pagename', 'absolute', 'basename', 'title', 'firsthead', 'headline')),
            'link'      => array('enum', 'page', array('page', 'anchor', 'off')),
            'newpage'   => array('enum', null, array('on', 'off', 'except')), // except is obsolete
        );
        }
        $this->conf_options    = &$conf_options;
    }
    
    // static
    var $conf_options;
    var $plugin = "lsx";
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

            if (isset($options['tag'])) {
                $plugin_tag = new PluginSonotsTag();
                $pages = $plugin_tag->get_taggedpages($options['tag']);
            } elseif (isset($options['prefix'])) {
                $pages = sonots::get_existpages($options['prefix']);
            } else {
                $pages = get_existpages();
            }
            $html = $this->pagelist($pages, $options, $argoptions);
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

            if (isset($options['tag'])) {
                $plugin_tag = new PluginSonotsTag();
                $pages = $plugin_tag->get_taggedpages($options['tag']);
                $title = _('List of pages tagged by "') . 
                    htmlspecialchars($options['tag']) . '"';
            } elseif (isset($options['prefix']) && $options['prefix'] !== '') {
                $pages = sonots::get_existpages($options['prefix']);
                $title = _('List of pages under "') . 
                    htmlspecialchars($options['prefix']) . '"';
            } else {
                $pages = get_existpages();
                $title = _('List of pages');
            }
            $html = $this->pagelist($pages, $options, $argoptions);
            if (empty($html)) $html = '<p>' . _('No page found.') . '</p>';
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
        if (! isset($options['prefix']) && count($unknowns) > 0) {
            $unknown_keys = array_diff_key($unknowns, $conf_options);
            $options['prefix'] = $key = key($unknown_keys); // compat with ls, ls2
            unset($unknowns[$key]);
        }
        if (count($unknowns) > 0) {
          $line = PluginSonotsOption::glue_option_line($unknowns);
          sonots::mythrow('Argument(s) "' . htmlspecialchars($line) . '" are invalid');
          return;
        }
        if (! isset($options['prefix'])) {
            if (! isset($options['tag'])) {
                $options['prefix'] = $vars['page'] != '' ? $vars['page'] . '/' : '';
            }
        } elseif ($options['prefix'] === '/') {
            $options['prefix'] = '';
        } else {
            $options['prefix'] = sonots::get_fullname($options['prefix'], $vars['page']);
        }

        //// hierarchy off
        if (isset($options['tag'])) {
            $options['hierarchy'] = false;
        }
        if ($options['sort'] == 'date') {
            $options['hierarchy'] = false;
        }
        if ($options['sort'] == 'title') {
            $options['hierarchy'] = false;
        }
        if (isset($options['include'])) {
            $options['include'] = PluginIncludex::check_options($options['include']);
            $options['hierarchy'] = false; // hierarchy + include => XHTML invalid
            $options['date'] = false;      // include does not use definitely
            $options['new']  = false;      // include does not use definitely
            $options['info'] = null;
            $options['contents'] = null;     // include does not use definitely
        }

        if ($options['linkstr'] === 'relative' && $options['hierarchy']) {
            $options['linkstr'] = 'basename'; // equivalent to basename
        }

        //// Compat
        if ($options['basename']) {
            $options['linkstr'] = 'basename'; 
        }

        if ($options['date'] || $options['new']) {
            $options['info'] = array();
        }
        if ($options['date']) {
            if (! in_array('date', $options['info'])) {
                $options['info'][] = 'date';
            }
        }
        if ($options['new']) {
            if (! in_array('new', $options['info'])) {
                $options['info'][] = 'new';
            }
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

        if (isset($options['prefix']) && $options['prefix'] !== '') {
            $pagelist->gen_metas('relname', array(sonots::get_dirname($options['prefix'])));
        }
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
        if (isset($options['depth']) || $options['hierarchy'] || $options['tree'] ) {
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

        if (is_array($options['info']) || $options['sort'] === 'date') {
            $pagelist->gen_metas('timestamp');
        }
        if ($options['sort'] === 'popular') {
            $pagelist->gen_metas('popular', array($options['popular']));
        }
        if ($options['sort'] === 'reading') {
            $pagelist->gen_metas('reading');
        }
        if ($options['sort'] === 'title') {
            $pagelist->gen_metas('title');
        }
        $pagelist->sort_by($options['sort'], $options['reverse']);

        $max = count($pagelist->metapages); // for next option
        if (is_array($options['num'])) {
            list($offset, $length) = $options['num'];
            $pagelist->slice($offset, $length);
        }
        
        //// display
        if (isset($options['include'])) {
            $pages = $pagelist->get_metas('page');
            $include = new PluginIncludex(); // just want static var
            $includes = array();
            foreach ($pages as $i => $page) {
                $includes[$i] = PluginIncludex::display_include($page, $options['include'], $include->syntax);
            }
            $html = implode("\n", $includes);
        } else {
            if ($options['hierarchy']) {
                if ($pagelist->pad_dirnodes($options['prefix'])) {
                    $pagelist->sort_by($options['sort'], $options['reverse']);
                }
            } else {
                $pagelist->init_metas('depth', 1);
            }
            
            $pagelist->gen_metas('link', array($options['linkstr'], $options['link']));
            $links = $pagelist->get_metas('link');
            
            $infos = array();
            if (is_array($options['info'])) {
                $pagelist->gen_metas('info', array($options['info']));
                $infos = $pagelist->get_metas('info');
            }
            
            $tocs = array();
            if (isset($options['contents'])) {
                $pages = $pagelist->get_metas('page');
                foreach ($pages as $i => $page) {
                    $toc_options = PluginContentsx::check_options($page, '', $options['contents']);
                    $tocs[$i] = PluginContentsx::display_toc($page, $toc_options);
                }
            }
            
            $items = array();
            foreach ($links as $i => $link) {
                $items[$i] = $links[$i];
                if (isset($infos[$i])) $items[$i] .= ' ' . $infos[$i];
                if (isset($tocs[$i])) $items[$i] .= $tocs[$i];
            }
            $levels = $pagelist->get_metas('depth');
            $html = sonots::display_list($items, $levels, $this->plugin);
        }

        //// display navi. $max is needed, $argoptions is need. 
        if ($options['next'] && is_array($options['num'])) {
            $argoptions['prefix'] = $options['prefix'];
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
}

///////////////////////////////////////////
function plugin_lsx_init()
{
    global $plugin_lsx_name;
    if (class_exists('PluginLsxUnitTest')) {
        $plugin_lsx_name = 'PluginLsxUnitTest';
    } elseif (class_exists('PluginLsxUser')) {
        $plugin_lsx_name = 'PluginLsxUser';
    } else {
        $plugin_lsx_name = 'PluginLsx';
    }
}
function plugin_lsx_action()
{
    global $plugin_lsx, $plugin_lsx_name;
    $plugin_lsx = new $plugin_lsx_name();
    return call_user_func(array(&$plugin_lsx, 'action'));
}
function plugin_lsx_convert()
{
    global $plugin_lsx, $plugin_lsx_name;
    $plugin_lsx = new $plugin_lsx_name();
    $args = func_get_args();
    return call_user_func_array(array(&$plugin_lsx, 'convert'), $args);
}

if (! defined('INIT_DIR')) // if not Plus! 
    if (file_exists(DATA_HOME . 'init/lsx.ini.php')) 
        include_once(DATA_HOME . 'init/lsx.ini.php');

?>
