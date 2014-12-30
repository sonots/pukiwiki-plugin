<?php
require_once(dirname(__FILE__) . '/sonots/sonots.class.php');
require_once(dirname(__FILE__) . '/sonots/option.class.php');
require_once(dirname(__FILE__) . '/sonots/toc.class.php');
require_once(dirname(__FILE__) . '/sonots/metapage.class.php');
//error_reporting(E_ALL);

/**
 * Page Include Plugin
 *
 * @package    plugin
 * @license    http://www.gnu.org/licenses/gpl.html GPL v2
 * @author     sonots
 * @link       http://lsx.sourceforge.jp/?Plugin%2Fincludex.inc.php
 * @version    $Id: includex.inc.php,v 2.0 2008-07-30 07:23:17Z sonots $
 * @require    sonots/sonots     v 1.9
 * @require    sonots/option     v 1.10
 * @require    sonots/toc        v 1.5
 * @require    sonots/metapage   v 1.4
 */
class PluginIncludex 
{
    function PluginIncludex()
    {
        // Configure options
        // array(type, default, config)
        static $conf_options = array(); if (empty($conf_options)) {
            $conf_options = array(
             'num'       => array('interval', null),
             'except'    => array('string',   null),
             'filter'    => array('string',   null),
             'title'     => array('enum',     'on',  array('on', 'off', 'nolink', 'basename')), // obsolete
             'titlestr'  => array('enum',     'title', array('title', 'name', 'pagename', 
                  'absolute', 'page', 'relname', 'relative', 'basename', 'headline', 'off',
             )),
             'titlelink' => array('bool',     true),
             'permalink' => array('string',   null, _('Permalink')),
             'firsthead' => array('bool',     true),
             'readmore'  => array('enum',     null, array('until', 'from')),
             'section'   => array('options',  null, array(
                 'num'       => array('interval', null, array(null, 0)), // start from 0
                 'depth'     => array('interval', null),
                 'except'    => array('string'  , null),
                 'filter'    => array('string',   null),
                 'cache'     => array('bool',     true),
                 'inclsub'   => array('bool',     false), // not yet
             )),
        );}
        // PukiWiki Syntax Definition
        static $syntax = array(
             'headline' => '/^(\*{1,3})/',
        );
        static $visited = array();
        $this->conf_options = &$conf_options;
        $this->syntax       = &$syntax;
        $this->visited      = &$visited;
    }
    
    // static
    var $conf_options;
    var $syntax;
    var $visited;
    var $plugin = "includex";
    
    /**
     * Convert Plugin Main Function
     */
    function convert()
    {
        global $vars, $defaultpage;
        sonots::init_myerror(); do { // try
            $args = func_get_args(); 
            $inclpage = array_shift($args);

            $current  = isset($vars['page']) ? $vars['page'] : $defaultpage;
            $this->visited[$current]  = TRUE;
            $inclpage = PluginIncludex::check_page($inclpage, $current, $this->visited);
            if (sonots::mycatch()) break;
            $this->visited[$inclpage] = TRUE;
            
            $argline = csv_implode(',', $args);
            $argoptions = PluginSonotsOption::parse_option_line($argline);
            list($options, $unknowns) = PluginSonotsOption::evaluate_options($argoptions, $this->conf_options);
            $options = PluginIncludex::check_options($options, $unknowns, $argoptions);
            if (sonots::mycatch()) break;
            
            $html = PluginIncludex::display_include($inclpage, $options, $this->syntax);
            return $html;
        } while (false);
        if (sonots::mycatch()) { // catch
            return '</p>#includex(): ' . sonots::mycatch() . '</p>';
        }
    }
        
    /**
     * Include page
     *
     * @access tatic
     * @param string $page
     * @param array $options
     * @param array $syntax
     * @return string html
     */
    function display_include($page, $options, $syntax)
    {
        global $vars;

        $lines = get_source($page);

        if (is_array($options['section'])) {
            $lines = PluginIncludex::get_sections($lines, $page, $options['section']);
        }

        if (isset($options['readmore'])) {
            $toc = new PluginSonotsToc($page, $options['section']['cache']);
            $readmore = $toc->get_readmore();
            if (isset($readmore)) {
                $last = key(array_keys($lines));
                switch ($options['readmore']) {
                case 'until':
                    for ($i = $readmore; $i <= $last ; ++$i) {
                        if (isset($lines[$i])) unset($lines[$i]);
                    }
                    break;
                case 'from':
                    for ($i = 0; $i <= $readmore ; ++$i) {
                        if (isset($lines[$i])) unset($lines[$i]);
                    }
                    break;
                }
            }
        }

        if (isset($options['filter'])) {
            $lines = sonots::grep_array('/' . str_replace('/', '\/', $options['filter']) . '/', $lines, 'preg', TRUE);
        }
        if (isset($options['except'])) {
            $lines = sonots::grep_array('/' . str_replace('/', '\/', $options['except']) . '/', $lines, 'preg', TRUE, TRUE); // inverse
        }
        if (is_array($options['num'])) {
            list($offset, $length) = $options['num'];
            $lines = sonots::array_slice($lines, $offset, $length, true);
        }

        if (! $options['firsthead']) {
            // cut the headline on the first line
            $firstline = reset($lines);
            if (preg_match($syntax['headline'], $firstline)) {
                array_shift($lines);
            }
        }

        // html
        $html = sonots::get_convert_html($page, $lines);
        //if (trim($html) === '') return '';

        $titlestr = '';
        if ($options['titlestr'] !== 'off') {
            $titlestr = PluginSonotsMetapage::linkstr($page, $options['titlestr'], $vars['page'], true);
        }
        $title = PluginIncludex::display_title($page, $titlestr, $options['title'], $GLOBALS['fixed_heading_edited'], 'includex');

        $footer = '';
        if (is_string($options['permalink'])) {
            $linkstr = sonots::make_inline($options['permalink']);
            $footer = '<p class="permalink">' . make_pagelink($page, $linkstr) . '</p>';
        }

        return $title . "\n" . $html . $footer;
    }


    /*
     * Get lines in the specified section only
     *
     * 0st sec
     * <h2>.....</h2> 1st head
     * 1st sec
     * <h2>.....</h2> 2nd head
     * 2nd sec
     *
     * @access static
     * @param array $lines
     * @param string $page
     * @param array $options section options
     * @return array lines
     * @uses PluginSonotsToc
     */
    function get_sections($lines, $page, $options)
    {
        $toc = new PluginSonotsToc($page, $options['cache']);

        $headlines = $toc->get_headlines();

        if (isset($options['filter'])) {
            sonots::grep_by($headlines, 'string', 'preg', '/' . str_replace('/', '\/', $options['filter']) . '/');
        }
        if (isset($options['except'])) {
            sonots::grep_by($headlines,'string', 'preg', '/' . str_replace('/', '\/', $options['except']) . '/', TRUE); // inverse
        }
        if (is_array($options['depth'])) {
            // Do not use negative offsets
            list($min, $max) = PluginSonotsOption::conv_interval($options['depth'], array(1, PHP_INT_MAX));
            sonots::grep_by($headlines, 'depth', 'ge', $min);
            sonots::grep_by($headlines, 'depth', 'le', $max);
        }
        $outlines = array();

        if (is_array($options['num'])) {
            array_unshift($headlines, new PluginSonotsHeadline($page, 0, 0, '', ''));
            list($offset, $length) = $options['num'];
            $headlines = sonots::array_slice($headlines, $offset, $length, true);
        }
        $linenums = sonots::get_members($headlines, 'linenum');

        // extract from current head till next head - 1
        $allheadlines = $toc->get_headlines();
        $alllinenums = sonots::get_members($allheadlines, 'linenum');
        if (! isset($alllinenums[0])) array_unshift($alllinenums, 0); // virtual head at the file head
        array_push($alllinenums, end(array_keys($lines)) + 1); // virtual head at the file tail
        $outlines = array();
        $current = 0;
        foreach ($alllinenums as $next) {
            if (in_array($current, $linenums)) {
                if ($next == $current) continue;
                $outlines += sonots::array_slice($lines, $current, $next - $current, true);
            }
            $current = $next;
        }
        return $outlines;
    }

    /**
     * Check Option Compatibilities
     *
     * @access static
     * @param array $options
     * @param array $unknowns
     * @param array $argoptions options before evaluated
     * @return array $options
     */     
    function check_options($options, $unknowns = array(), $argoptions)
    {
        if ($options['title'] != 'on') {
            if ($options['title'] == 'nolink') {
                $options['titlelink'] = false;
                $options['titlestr'] = 'name';
            } else {
                $options['titlestr'] = $options['title'];
            }
        }
        return $options;
    }

    /**
     * Check Page Validity
     *
     * @access static
     * @param string $page
     * @param string $current
     * @param array $visited
     * @return string evaluated page
     */
    function check_page($page, $current, $visited = array())
    {
        if (empty($page)) {
            sonots::mythrow("No page is specified."); return;
        }
        $page = get_fullname($page, $current);
        if (! is_page($page)) {
            sonots::mythrow('Page ' . sonots::make_pagelink_nopg($page) . ' does not exist.'); return;
        }
        if (! check_readable($page, false, false)) {
            sonots::mythrow('Page ' . sonots::make_pagelink_nopg($page) . ' is not readable.'); return;
        }
        if (isset($visited[$page])) {
            sonots::mythrow('Page ' . sonots::make_pagelink_nopg($page) . ' is already included.'); return;
        }
        return $page;
    }

    /**
     * Get <h1> title of a page
     * 
     * @access static
     * @param string $page pagename
     * @param string $titlestr titlestr
     * @param boolean $link link to page or not
     * @param boolean $editlink add edit link icon or not
     * @param string $cssclass
     * @return $string html
     */
    function display_title($page, $titlestr = '', $link = FALSE, $editlink = FALSE, $cssclass = '')
    {
        $aname = ' ' . sonots::make_pageanamelink_icon($page);
        $edit  = '';
        if ($titlestr == '') {
            return '<div class="' . $cssclass . '" style="padding:0px;margin:0px;">' . $aname . '</div>';
        }
        if ($editlink) {
            $edit = ' ' . sonots::make_pageeditlink_icon($page);
        }
        if ($link) {
            $titlestr = make_pagelink($page, $titlestr);
        }
        return '<h1 class="' . $cssclass . '">' . $titlestr . $edit . $aname . '</h1>';
    }
}

///////////////////////////////////////////
function plugin_includex_init()
{
    global $plugin_includex_name;
    if (class_exists('PluginIncludexUnitTest')) {
        $plugin_includex_name = 'PluginIncludexUnitTest';
    } elseif (class_exists('PluginIncludexUser')) {
        $plugin_includex_name = 'PluginIncludexUser';
    } else {
        $plugin_includex_name = 'PluginIncludex';
    }
}
function plugin_includex_convert()
{
    global $plugin_includex, $plugin_includex_name;
    $plugin_includex = new $plugin_includex_name();
    $args = func_get_args();
    return call_user_func_array(array(&$plugin_includex, 'convert'), $args);
}

if (! defined('INIT_DIR')) // if not Plus! 
    if (file_exists(DATA_HOME . 'init/includex.ini.php')) 
        include_once(DATA_HOME . 'init/includex.ini.php');

?>
