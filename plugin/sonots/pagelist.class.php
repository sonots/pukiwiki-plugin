<?php
require_once(dirname(__FILE__) . '/sonots.class.php');
require_once(dirname(__FILE__) . '/metapage.class.php');
//error_reporting(E_ALL);

/**
 * Page List Class
 *
 * @package    PluginSonots
 * @license    http://www.gnu.org/licenses/gpl.html GPL v2
 * @author     sonots <http://lsx.sourceforge.jp/>
 * @version    $Id: pagelist.class.php,v 1.6 2008-08-19 07:23:17Z sonots $
 * @require    sonots    v 1.15
 * @require    metapage  v 1.9
 */

class PluginSonotsPagelist
{
    /**
     * array of PluginSonotsMetapage(s)
     *
     * @var array
     */
    var $metapages = array();

    /**
     * constructor
     *
     * @access public
     * @param array $pages
     * @uses PluginSonotsMetapage
     * @version $Id: v 1.0 2008-06-07 07:23:17Z sonots $
     */
    function PluginSonotsPagelist($pages)
    {
        foreach ($pages as $page) {
            $this->metapages[] = new PluginSonotsMetapage($page);
        }
    }

    /**
     * sizeof
     *
     * @access public
     * @return int
     */
    function sizeof()
    {
        return sizeof($this->metapages);
    }

    /**
     * Initialize the specific meta informations of metapages
     *
     * @access public
     * @param string $metakey meta information name
     * @param mixed initialization value
     * @return array metas
     */
    function init_metas($metakey, $value)
    {
        sonots::init_members($this->metapages, $metakey, $value);
    }

    /**
     * Generate the specific meta infomration to pages
     *
     * @access public
     * @param string $metakey meta information name
     * @param array $args if arguments required to set meta information
     * @version $Id: v 1.0 2008-06-07 07:23:17Z sonots $
     */
    function gen_metas($metakey, $args = array())
    {
        switch ($metakey) {
        case 'leaf': // tree can't be constructed item by item
            $leafs = sonots::get_tree(get_existpages());
            foreach ($this->metapages as $i => $val) {
                $page = $this->metapages[$i]->page;
                $this->metapages[$i]->leaf = $leafs[$page];
            }
            break;
        default:     // others can be
            $metapages = &$this->metapages;
            foreach ($metapages as $i => $val) {
                call_user_func_array(array(&$metapages[$i], 'gen_' . $metakey), $args);
            }
            break;
        }
    }

    /**
     * Get a specific meta information
     *
     * @access public
     * @param int $key page hash array key
     * @param string $metakey meta information name
     * @return mixed
     * @version $Id: v 1.0 2008-08-15 07:23:17Z sonots $
     */
    function get_meta($key, $metakey)
    {
        return $this->metapages[$key]->$metakey;
    }

    /**
     * Get the specific meta informations of pages
     *
     * @access public
     * @param string $metakey meta information name
     * @return array metas
     * @version $Id: v 1.0 2008-06-07 07:23:17Z sonots $
     */
    function get_metas($metakey)
    {
        return sonots::get_members($this->metapages, $metakey);
    }

    /**
     * Applies the callback to the specific meta informations of metapages
     *
     * @access public
     * @param string $metakey meta information name
     * @param callback $callback
     * @return void
     */
    function map_metas($metakey, $callback)
    {
        sonots::map_members($this->metapages, $metakey, $callback);
    }

    /**
     * Pad non existing dir nodes to construct hierarchical tree
     *
     * Example)
     *  A       A
     *  A/A =>  A/A
     *  B/B     B(non exist)
     *          B/B
     *
     * @access public
     * @param string $prefix current path (need to create relname)
     * @return boolean some intances were added or not
     * @version $Id: v 1.1 2008-06-07 07:23:17Z sonots $
     */
    function pad_dirnodes($prefix)
    {
        $origsize = count($this->metapages);
        $prefix = sonots::get_dirname($prefix);
        $prefix = empty($prefix) ? '' : $prefix . '/';
        $paths = $this->get_metas('relname');
        foreach ($paths as $i => $path) {
            $currpath = $path;
            while (TRUE) {
                if ($currpath == '') break;
                // if parent dir does not exist, pad
                if (($j = array_search($currpath, $paths)) === FALSE) {
                    $abspath = $prefix . $currpath;
                    $new = new PluginSonotsMetapage($abspath);
                    $new->reading   = $abspath;
                    $new->relname   = $currpath;
                    $new->depth     = substr_count($currpath, '/') + 1;
                    $new->timestamp = 1;
                    $new->date      = '';
                    $new->leaf      = FALSE;
                    $new->exist     = FALSE;
                    $this->metapages[] = $new;
                    $paths[]        = $currpath;
                }
                $currpath = sonots::get_dirname($currpath);
            }
        }
        return count($this->metapages) > $origsize;
    }

    /**
     * Slice metapages
     *
     * @access public
     * @param int $offset
     * @param mixed $length int or NULL (means forever)
     * @see array_slice
     * @version $Id: v 1.0 2008-06-07 07:23:17Z sonots $
     */
    function slice($offset, $length)
    {
        $this->metapages = sonots::array_slice
            ($this->metapages, $offset, $length, true);
    }

    /**
     * sort metapages by a meta
     *
     * @access public
     * @param string|null $meta meta name. 
     * @param boolean $reverse sort in reverse order
     * @version $Id: v 1.2 2008-06-10 07:23:17Z sonots $
     */
    function sort_by($meta, $reverse = false)
    {
        switch ($meta) {
        case 'name':
            $metas = $this->get_metas('relname');
            sonots::natcasesort_filenames($metas);
            break;
        case 'date':
            $metas = $this->get_metas('timestamp');
            arsort($metas, SORT_NUMERIC);
            break;
        case 'reading':
            $metas = $this->get_metas('reading');
            sonots::natcasesort_filenames($metas);
            break;
        case 'popular':
            $metas = $this->get_metas('popular');
            arsort($metas, SORT_NUMERIC);
            break;
        case 'title':
            $metas = $this->get_metas('title');
            $metas = array_map('make_link', $metas);
            $metas = array_map('strip_tags', $metas);
            $metas = array_map('trim', $metas);
            asort($metas, SORT_STRING);
            break;
        default:
            $metas = $this->get_metas($meta);
            asort($metas, SORT_STRING);
            break;
        }
        if ($reverse) {
            $metas = array_reverse($metas, true);
        }
        sonots::array_asort_key($this->metapages, $metas);
    }

    /**
     * Reverse order of metapages
     *
     * @access public
     * @version $Id: v 1.0 2008-08-19 07:23:17Z sonots $
     */
    function reverse()
    {
        $this->metapages = array_reverse($this->metapages, true);
    }

    /**
     * Grep out metapages by speific fields
     *
     * @access public
     * @param string $meta name of meta information to be greped
     * @param string $func func name
     *  - preg     : grep by preg
     *  - ereg     : grep by ereg
     *  - mb_ereg  : grep by mb_ereg
     *  - prefix   : remains if prefix matches (strpos)
     *  - mb_prefix: (mb_strpos)
     *  - eq       : remains if equality holds
     *  - ge       : remains if greater or equal to
     *  - le       : remains if less or equal to
     * @param mixed $pattern
     * @param boolean $inverse grep -v
     * @return void
     * @version $Id: v 1.0 2008-06-07 07:23:17Z sonots $
     */
    function grep_by($meta, $func, $pattern, $inverse = FALSE)
    {
        sonots::grep_by($this->metapages, $meta, $func, $pattern, $inverse);
    }

    /**
     * Get HTML of page list
     *
     * @access public
     * @param string $cssclass css class
     * @return string list html
     * @version $Id: v 1.0 2008-06-07 07:23:17Z sonots $
     */
    function display_pages($cssclass = '')
    {
        $items = $this->get_metas('link');
        $levels = $this->get_metas('depth');
        return sonots::display_list($items, $levels, $cssclass);
    }

    ///////////// static functions /////////
    /**
     * Prev Next Navigation
     *
     * @access public
     * @static
     * @param array $interval array($start, $end) current showing interval
     * @param array $entire array($start, $end) entire interval
     * @param string $basehref base href
     * @param string $cssclass
     * @return string html
     * @uses get_prevnext
     * @version $Id: v 1.1 2008-06-07 07:23:17Z sonots $
     */
    function display_navi($interval, $entire, $basehref, $cssclass = '')
    {
        $length = $interval[1] - $interval[0] + 1;
        list($prev, $next) = PluginSonotsPagelist::get_prevnext($interval);
        
        $prevlink = '';
        if ($prev[1] >= $entire[0]) {
            $prevhref = $basehref . '&amp;num=' . $prev[0] . ':' . $prev[1];
            $prevlink = '<span class="prev" style="float:left;"><a href="' . $prevhref . '">' . _('Prev ') . $length . '</a></span>';
        }
        $nextlink = '';
        if ($next[0] <= $entire[1]) {
            $nexthref = $basehref . '&amp;num=' . $next[0] . ':' . $next[1];
            $nextlink = '<span class="next" style="float:right;"><a href="' . $nexthref . '">' . _('Next ') . $length . '</a></span>';
        }
        return '<div class="' . $cssclass . '">' . $prevlink . $nextlink . '</div><div style="clear:both;"></div>';
    }

    /**
     * Get prev next intervals
     *
     * Example)
     * <code>
     *  $current = array(3, 10); // 3rd to 10th
     *  list($prev, $next) = get_prevnext($current);
     *  // array(-5, 2), array(11, 18)
     *  $entire = array(1, 100);
     *  list($prev, $next) = get_prevnext($current, $entire);
     *  // array(1, 2), array(11, 18)
     * </code>
     *
     * @access private
     * @static
     * @param array $current
     * @param array $entire use if you want to assure entire interval
     * @return array array($prev, $next)
     * @version $Id: v 1.1 2008-06-07 07:23:17Z sonots $
     */
    function get_prevnext($current, $entire = array(NULL, NULL))
    {
        $diff = $current[1] - $current[0];
        $next = array($current[1] + 1, $current[1] + 1 + $diff);
        $prev = array($current[0] - 1 - $diff, $current[0] - 1);
        if (! is_null($entire[0])) {
            $prev[0] = ($prev[0] < $entire[0]) ? $entire[0] : $prev[0];
            $prev[1] = ($prev[1] < $entire[0]) ? $entire[0] : $prev[1];
            $next[0] = ($next[0] < $entire[0]) ? $entire[0] : $next[0];
            $next[1] = ($next[1] < $entire[0]) ? $entire[0] : $next[1];
        }
        if (! is_null($entire[1])) {
            $prev[0] = ($prev[0] > $entire[1]) ? $entire[1] : $prev[0];
            $prev[1] = ($prev[1] > $entire[1]) ? $entire[1] : $prev[1];
            $next[0] = ($next[0] > $entire[1]) ? $entire[1] : $next[0];
            $next[1] = ($next[1] > $entire[1]) ? $entire[1] : $next[1];
        }
        return array($prev, $next);
    }

}

?>
