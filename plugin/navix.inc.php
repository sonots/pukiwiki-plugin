<?php
require_once(dirname(__FILE__) . '/sonots/sonots.class.php');
require_once(dirname(__FILE__) . '/sonots/option.class.php');
require_once(dirname(__FILE__) . '/sonots/pagelist.class.php');
require_once(dirname(__FILE__) . '/sonots/metapage.class.php');
require_once(dirname(__FILE__) . '/sonots/tag.class.php');
//error_reporting(E_ALL);

/**
 * Navi (DocBook-like Navigation) Plugin
 *
 * @package    plugin
 * @license    http://www.gnu.org/licenses/gpl.html GPL v2
 * @author     sonots <http://lsx.sourceforge.jp>
 * @link       http://lsx.sourceforge.jp/?Plugin%2Fnavixx.inc.php
 * @version    $Id: navix.inc.php,v 2.0 2008-08-18 11:14:46 sonots $
 * @require    sonots/sonots     v 1.14
 * @require    sonots/option     v 1.8
 * @require    sonots/pagelist   v 1.4
 * @require    sonots/metapage   v 1.11
 * @require    sonots/tag        v 1.0
 */

class PluginNavix
{
    function PluginNavix()
    {
        // configure message
        static $linkstr = array();if (empty($linkstr)) {
            $linkstr = array(
            'prev'  => _('Prev'),
            'next'  => _('Next'),
            'up'    => _('Up'),
            'home'  => _('Home'),
        );
        }
        // configuration
        static $conf = array(
             'printcss' => true,
        );
        // configure css
        static $css = array(
            'ul.navix'
            => 'list-style-image:none;list-style-position:outside;list-style-type:none;margin:0;padding:0;text-align:right;width:100%;',
            'ul.navix li.left'
            => 'float:left;text-align:left;width:40%;display:inline;',
            'ul.navix li.center' 
            => 'float:left;text-align:center;width:20%;display:inline;',
            'ul.navix li.right'
            => 'float:none;width:40%;display:inline;',
            'div.wrap_navix hr.top'
            => 'margin:0.4em 0;',
            'div.wrap_navix hr.bottom'
            => 'margin:0.4em 0;',
        );
        // Configure options
        // array(type, default, config)
        static $conf_options = array(); if (empty($conf_options)) {
            $conf_options = array(
            'border'    => array('enum', 'off', array('off', 'top', 'bottom')),                  
            'look'      => array('enum', 'header', array('header', 'footer')),
            'home'      => array('string', null),
            //'prefix'    => array('string', null), // $prefix = $home . '/'
            'tree'      => array('enum', false, array(false, 'leaf', 'dir')),
            'depth'     => array('interval', null),
            'num'       => array('interval', null),
            'non_list'  => array('bool', true),
            'filter'    => array('string', null),
            'except'    => array('string', null),
            'sort'      => array('enum', 'name', array('name', 'reading', 'date', 'popular', 'title')),
            'reverse'   => array('bool', false), // option of sort option
            'popular'   => array('enum', 'today', array('today', 'total', 'yesterday', 'recent')), // option of sort option
            'tag'       => array('string', null),
            'basename'  => array('bool', false), // obsolete
            'linkstr'   => array('enum', 'relative', array('relative', 'relname', 'name', 'page', 'pagename', 'absolute', 'basename', 'title', 'firsthead', 'headline')),
            'link'      => array('enum', 'page', array('page', 'anchor', 'off')),
            'newpage'   => array('enum', null, array('on', 'off', 'except')), // except is obsolete
        );
        }
        $this->conf_options    = &$conf_options;
        $this->conf            = &$conf;
        $this->css             = &$css;
        $this->linkstr         = &$linkstr;
    }
    
    // static
    var $conf_options;
    var $plugin = "navix";
    var $conf;
    var $css;
    var $linkstr;
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
            $pagelist = $this->pagelist($pages, $options, $argoptions);
            $navipages = $this->get_navipages($pagelist, $options);
            $html = $this->display_navi($navipages, $options['look'], $options['border'], $this->conf['printcss']);
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
        global $vars;
        if (isset($vars['pcmd']) && $vars['pcmd'] == 'css') {
            pkwk_common_headers();
            header('Content-Type: text/css');
            foreach ($this->css as $key => $val) {
                print $key . " {\n";
                print str_replace(';', ";\n", $val);
                print '}' . "\n";
            }
            exit;
        }
        return array('title'=>$this->plugin, 'body'=>'nothing to do');
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
        if (! isset($options['home']) && count($unknowns) > 0) {
            $unknown_keys = array_diff_key($unknowns, $conf_options);
            $options['home'] = $key = key($unknown_keys); // compat with ls, ls2
            unset($unknowns[$key]);
        }
        if (count($unknowns) > 0) {
          $line = PluginSonotsOption::glue_option_line($unknowns);
          sonots::mythrow('Argument(s) "' . htmlspecialchars($line) . '" are invalid');
          return;
        }
        if (! isset($options['home'])) {
            if (! isset($options['tag'])) {
                $options['home'] = $vars['page'] != '' ? sonots::get_dirname($vars['page']) : '';
            }
        } elseif ($options['home'] === '/') {
            $options['home'] = '';
        } else {
            $options['home'] = sonots::get_fullname($options['home'], $vars['page']);
        }
        $options['prefix'] = $options['home'] . '/';

        //// Compat
        if ($options['basename']) {
            $options['linkstr'] = 'basename'; 
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

        if (is_array($options['num'])) {
            list($offset, $length) = $options['num'];
            $pagelist->slice($offset, $length);
        }
        return $pagelist;
    }

    /**
     * Get pages used for Navi
     *
     * @access global
     * @param object $pagelist PluginSonotsPagelist object
     * @param array $options
     * @return array $navipages array($home, $up, $prev, $current, $next)
     */
    function get_navipages($pagelist, $options)
    {
        global $vars, $defaultpage;
        $current = $vars['page'];
        $home    = $options['home'];
        $up      = sonots::get_dirname($current);

        $pages = $pagelist->get_metas('page');
        $prev = $home;
        foreach ($pages as $page) {
            if ($page == $current) break;
            $prev = $page;;
        }
        $next = current($pages);

        return array($home, $up, $prev, $current, $next);
    }

    /**
     * Display navigation
     *
     * @param array $navipages array($home, $up, $prev, $current, $next)
     * @param string $look Show DocBook 'footer' style or DocBook 'header'
     *
     * DocBook header
     * <pre>
     *     Prev          Home          Next
     * </pre>
     * DocBook footer
     * <pre>
     *     Prev          Home          Next
     *     <pagename>     Up     <pagename>
     * </pre>
     *
     * @param string $border 'off' or 'top' or 'bottom'. 
     * - 'off' shows no border.
     * - 'top' shows a border top.
     * - 'bottom' shows a border bottom. 
     * @param bool $printcss print css with html as style="" attributes. 
     * @return string html
     */
    function display_navi($navipages, $look = 'header', $border = 'off', $printcss = false)
    {
        $linkstr = $this->linkstr;
        $css     = $this->css;
        $footer  = ($look == 'footer');
        list($home, $up, $prev, $current, $next) = $navipages;

        // get link
        $link = array();
        $link['prev'] = make_pagelink($prev, $linkstr['prev']);
        $link['home'] = ($home == '') ? sonots::make_toplink($linkstr['home'])
            : make_pagelink($home, $linkstr['home']);
        if ($next) $link['next'] = make_pagelink($next, $linkstr['next']);
        if ($footer) {
            $link['up'] = make_pagelink($up, $linkstr['up']);
            $link['prevfoot'] = strip_tags(make_link(PluginSonotsMetapage::linkstr($prev, $footer, $current, true)));
            if ($next) $link['nextfoot'] = strip_tags(make_link(PluginSonotsMetapage::linkstr($next, $footer, $current, true)));
        }

        // html
        $html = '<div class="wrap_navix" style="margin:0px;padding:0px;">' . "\n";
        if ($border === 'top') {
            $html .= '<hr class="full_hr top"' . 
                ($printcss ? (' style="' . $css['div.wrap_navix hr.top'] . '"') : '') . 
                '/>' . "\n";
        }
        $html .= '<ul class="navix"' . 
            ($printcss ? (' style="' . $css['ul.navix'] . '"') : '') . '>' . 
            "\n";
        $html .= '<li class="left"' .
            ($printcss ? (' style="' . $css['ul.navix li.left'] . '"') : '') . '>' .
            $link['prev'] . ($footer ? '<br />' . $link['prevfoot'] : '') .
            '</li>' . "\n";
        $html .= '<li class="center"' .
            ($printcss ? (' style="' . $css['ul.navix li.center'] . '"') : '') . '>' .
            $link['home'] . ($footer ? '<br />' . $link['up'] : '') .
            '</li>' . "\n";
        $html .= '<li class="right"' .
            ($printcss ? (' style="' . $css['ul.navix li.right'] . '"') : '') . '>' .
            ($next ? ($link['next'] . ($footer ? '<br />' . $link['nextfoot'] : '')) : '&nbsp;') . 
            '</li>' . "\n";
        $html .= '</ul>' . "\n";
        if ($border === 'bottom') {
            $html .= '<hr class="full_hr bottom"' . 
                ($printcss ? (' style="' . $css['div.wrap_navix hr.bottom'] . '"') : '') . 
                '/>' . "\n";
        }
        $html .= '</div>' . "\n";
        return $html;
    }
}

///////////////////////////////////////////
function plugin_navix_init()
{
    global $plugin_navix_name;
    if (class_exists('PluginNavixUnitTest')) {
        $plugin_navix_name = 'PluginNavixUnitTest';
    } elseif (class_exists('PluginNavixUser')) {
        $plugin_navix_name = 'PluginNavixUser';
    } else {
        $plugin_navix_name = 'PluginNavix';
    }
}
function plugin_navix_action()
{
    global $plugin_navix, $plugin_navix_name;
    $plugin_navix = new $plugin_navix_name();
    return call_user_func(array(&$plugin_navix, 'action'));
}
function plugin_navix_convert()
{
    global $plugin_navix, $plugin_navix_name;
    $plugin_navix = new $plugin_navix_name();
    $args = func_get_args();
    return call_user_func_array(array(&$plugin_navix, 'convert'), $args);
}

if (! defined('INIT_DIR')) // if not Plus! 
    if (file_exists(DATA_HOME . 'init/navix.ini.php')) 
        include_once(DATA_HOME . 'init/navix.ini.php');

?>
