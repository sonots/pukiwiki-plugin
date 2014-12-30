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
 * List Tagged Pages Plugin
 *
 * @package    plugin
 * @license    http://www.gnu.org/licenses/gpl.html GPL v2
 * @author     sonots <http://lsx.sourceforge.jp>
 * @link       http://lsx.sourceforge.jp/?Plugin%2Ftag.inc.php
 * @version    $Id: taglist.inc.php,v 2.0 2008-07-18 07:23:17Z sonots $
 * @require    sonots/sonots     v 1.13
 * @require    sonots/option     v 1.8
 * @require    sonots/pagelist   v 1.4
 * @require    sonots/metapage   v 1.11
 * @require    sonots/tag        v 1.0
 * @require    contentsx         v 2.0
 * @require    includex          v 2.0
 * @compatible tagcloud.inc.php  v 2.0
 * @compatible tag.inc.php       v 2.0
 */

class PluginTaglist
{
    function PluginTaglist()
    {
        // Configure options
        // array(type, default, config)
        static $conf_options = array(); if (empty($conf_options)) {
            $contents = new PluginContentsx();
            $include = new PluginIncludex();
            $conf_options = array(
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
            'related'   => array('string', null),
            'linkstr'   => array('enum', 'name', array('name', 'page', 'pagename', 'absolute', 'base', 'basename', 'title', 'firsthead', 'headline')),
            'link'      => array('enum', 'page', array('page', 'anchor', 'off')),
            'newpage'   => array('enum', null, array('on', 'off', 'except')), // except is obsolete
        );
        }
        $this->conf_options    = &$conf_options;
    }
    
    // static
    var $conf_options;
    var $plugin = "taglist";
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
                $html = $this->pagelist($pages, $options, $argoptions);
            } else {
                $plugin_tag = new PluginSonotsTag();
                if (isset($options['related'])) {
                    $tags = $plugin_tag->get_related_tags($options['related']);
                } else {
                    $tags = $plugin_tag->get_existtags();
                }
                $html = $this->tagpagelist($tags, $options, $argoptions);
            }
            
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
                $html = $this->pagelist($pages, $options, $argoptions);
            } else {
                $plugin_tag = new PluginSonotsTag();
                if (isset($options['related'])) {
                    $tags = $plugin_tag->get_related_tags($options['related']);
                    $title = _('List of tags related to "') . 
                        htmlspecialchars($options['related']) . _('" and their tagged pages');
                } else {
                    $tags = $plugin_tag->get_existtags();
                    $title = _('List of all tags and their tagged pages');
                }
                $html = $this->tagpagelist($tags, $options, $argoptions);
            }
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
        if (! isset($options['tag']) && count($unknowns) > 0) {
            $unknown_keys = array_diff_key($unknowns, $conf_options);
            $options['tag'] = $key = key($unknown_keys);
            unset($unknowns[$key]);
        }
        if (count($unknowns) > 0) {
          $line = PluginSonotsOption::glue_option_line($unknowns);
          sonots::mythrow('Argument(s) "' . htmlspecialchars($line) . '" are invalid');
          return;
        }

        if (isset($options['include'])) {
            $options['include'] = PluginIncludex::check_options($options['include']);
            $options['date'] = false;      // include does not use definitely
            $options['new']  = false;      // include does not use definitely
            $options['info'] = null;
            $options['contents'] = null;     // include does not use definitely
        }

        //// Compat
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
            $pagelist->init_metas('depth', 1);

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

        if ($options['next'] && is_array($options['num'])) {
            $argoptions['tag'] = $options['tag'];
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
    
    /**
     * Display tags and tagged pages
     *
     * @param array $tags list of tags
     * @return string HTML
     */
    function tagpagelist($tags, $options, $argoptions, $cssclass = 'taglist tags')
    {
        $html = '<ul class="' . $cssclass . '">';
        $plugin_tag = new PluginSonotsTag();
        foreach ($tags as $tag) {
            $html .= '<li>' . $plugin_tag->make_taglink($tag);
            $pages = $plugin_tag->get_taggedpages($tag);
            $html .= $this->pagelist($pages, $options, $argoptions);
            $html .= '</li>';
        }
        $html .= '</ul>';
        return $html;
    }
}

////////////////////////
function plugin_taglist_init()
{
    global $plugin_taglist_name;
    if (class_exists('PluginTaglistUnitTest')) {
        $plugin_taglist_name = 'PluginTaglistUnitTest';
    } elseif (class_exists('PluginTaglistUser')) {
        $plugin_taglist_name = 'PluginTaglistUser';
    } else {
        $plugin_taglist_name = 'PluginTaglist';
    }
}

function plugin_taglist_convert()
{
    global $plugin_taglist, $plugin_taglist_name;
    $plugin_taglist = new $plugin_taglist_name();
    $args = func_get_args();
    return call_user_func_array(array(&$plugin_taglist, 'convert'), $args);
}

function plugin_taglist_action()
{
    global $plugin_taglist, $plugin_taglist_name;
    $plugin_taglist = new $plugin_taglist_name();
    return $plugin_taglist->action();
}

if (! defined('INIT_DIR')) // if not Plus! 
    if (file_exists(DATA_HOME . 'init/taglist.ini.php')) 
        include_once(DATA_HOME . 'init/taglist.ini.php');
?>
