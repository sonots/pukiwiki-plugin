<?php
require_once(dirname(__FILE__) . '/sonots.class.php');
require_once(dirname(__FILE__) . '/toc.class.php'); // to get title of a page
//error_reporting(E_ALL);

/**
 * Meta Information of A Page
 *
 * This class is a structure of meta information(s) of a page. 
 *
 * This class can also be thought as a namespace having collections 
 * of functions to obtain page meta information. 
 * Use static PluginSonotsMetapage::metaname() for this purpose. 
 *
 * @package    PluginSonots
 * @license    http://www.gnu.org/licenses/gpl.html GPL v2
 * @author     sonots <http://lsx.sourceforge.jp/>
 * @version    $Id: metapage.class.php, v 1.11 2008-07-18 11:14:46 sonots $
 * @require    sonots     v 1.11
 * @require    toc        v 1.9
 */
class PluginSonotsMetapage
{
    /**
     * Pagename
     *
     * @var string
     */
    var $page;
    /**
     * Relative path from current directory
     *
     * @var string
     */
    var $relname;
    /**
     * How to read (Kanji) or alias
     *
     * @var string
     */
    var $reading;
    /**
     * Directory tree depth (Relative Depth)
     *
     * @var int
     */
    var $depth;
    /**
     * Timestamp
     *
     * @var int
     */
    var $timestamp;
    /**
     * Modified Date in a date format
     *
     * @var string
     */
    var $date;
    /**
     * New! (Modified within 1 day or 3 days, etc)
     *
     * @var string
     */
    var $new;
    /**
     * Whether newpage or not
     *
     * @var boolean
     */
    var $newpage;
    /**
     * Whether leaf on directory tree or not
     *
     * @var boolean
     */
    var $leaf;
    /**
     * Existence
     *
     * @var boolean
     */
    var $exist = TRUE;
    /**
     * link string to be shown in link
     *
     * @var string
     */
    var $linkstr;
    /**
     * link to be shown in html
     *
     * @var string
     */
    var $link;
    /**
     * extra information field to be shown in html
     *
     * @var string
     */
    var $info = '';
    /**
     * Popular count
     *
     * @var int
     */
    var $popular;
    /**
     * Table of Contents (object of PluginSonotsToc)
     *
     * @var array
     */
    var $toc;
    /**
     * TITLE: of the page
     *
     * @var string
     */
    var $title;
    /**
     * First heading string of the page
     *
     * @var string
     */
    var $firsthead;

    /**
     * constructor
     *
     * @access public
     * @param string $page
     */
    function PluginSonotsMetapage($page)
    {
        $this->page = $page;
        $this->relname = $page;
    }

    ////////////// member functions //////////
    /**
     * Generate relative name property of this page
     *
     * @access public
     * @param string $currdir
     * @uses relname
     */
    function gen_relname($currdir = '')
    {
        $this->relname = PluginSonotsMetapage::relname($this->page, $currdir);
    }
    /**
     * Generate depth property of this page
     *
     * @access public
     * @uses detph
     */
    function gen_depth()
    {
        $this->depth = PluginSonotsMetapage::depth($this->relname);
    }
    /**
     * Generate reading (how to read Kanji) property of this page
     *
     * @access public
     * @uses reading
     */
    function gen_reading()
    {
        $this->reading = PluginSonotsMetapage::reading($this->page);
    }
    /**
     * Generate local filename property of this page
     *
     * @access public
     * @uses filename
     */
    function gen_filename()
    {
        $this->filename = PluginSonotsMetapage::filename($this->page);
    }
    /**
     * Generate timestamp property of this page
     *
     * @access public
     * @uses timestamp
     */
    function gen_timestamp()
    {
        $this->timestamp = PluginSonotsMetapage::timestamp($this->page);
    }
    /**
     * Generate modified date property of this page
     *
     * @access public
     * @uses date
     */
    function gen_date()
    {
        if (! isset($this->timestamp)) $this->gen_timestamp();
        $this->date = PluginSonotsMetapage::date($this->timestamp);
    }
    /**
     * Generate New! property of this page
     *
     * @access public
     * @uses newdate
     */
    function gen_new()
    {
        if (! isset($this->timestamp)) $this->gen_timestamp();
        $this->new = PluginSonotsMetapage::newdate($this->timestamp);
    }
    /**
     * Generate info property of this page
     *
     * @access public
     * @param array $order array of new,date specifying order.
     */
    function gen_info($order = array()) 
    {
        if (! $this->exist) {
            $this->info = '';
            return;
        }
        $info = '';
        foreach ($order as $elem) {
            switch ($elem) {
            case 'date':
                if (! isset($this->date)) $this->gen_date();
                $info .= '<span class="comment_date">' . $this->date . '</span>';
                break;
            case 'new':
                if (! isset($this->new)) $this->gen_new();
                $info .= $this->new;
                break;
            }
        }
        $this->info = $info; //PluginSonotsMetapage::info($info);
    }
    /**
     * Generate newpage property of this page
     *
     * @access public
     */
    function gen_newpage()
    {
        $this->newpage = PluginSonotsMetapage::newpage($this->page);
    }
    /**
     * Generate leaf property of this page
     *
     * leaf can not be determined item by item
     *
     * @access public
     * @param boolean $leaf
     */
    function gen_leaf($leaf)
    {
        $this->leaf = $leaf;
    }
    /**
     * Generate exist property of this page
     *
     * @access public
     */
    function gen_exist()
    {
        $this->exist = PluginSonotsMetapage::exist($this->page);
    }
    /**
     * Generate linkstr property of this page
     *
     * @access public
     * @param string $optlinkstr
     * @param string $currdir computed as default
     * @param boolean $usecache use Toc cache or not (used for title and firsthead)
     * @uses linkstr
     */
    function gen_linkstr($optlinkstr = 'relative', $currdir = '', $usecache = true)
    {
        $currdir = empty($currdir) ? substr($this->page, 0, strlen($this->page) - strlen($this->relname)) : $currdir;
        $this->linkstr = PluginSonotsMetapage::linkstr($this->page, $optlinkstr, $currdir, $usecache);
    }
     
    /**
     * Generate link property of this page
     *
     * @access public
     * @param string $optlinkstr
     * @param string $optlink
     * @param string $currdir computed as default
     * @param boolean $usecache use Toc cache or not (used for title and firsthead)
     * @uses linkstr
     * @uses link
     */
    function gen_link($optlinkstr = 'relative', $optlink = 'page', $currdir = '', $usecache = true)
    {
        $this->gen_linkstr($optlinkstr, $currdir, $usecache);
        $this->link = ($this->exist) ? 
            PluginSonotsMetapage::link($this->page, $this->linkstr, $optlink) : $this->linkstr;
    }
    /**
     * Generate popular property of this page
     *
     * @access public
     * @param string $when
     * @uses popular
     */
    function gen_popular($when = 'today')
    {
        $this->popular = PluginSonotsMetapage::popular($this->page, $when);
    }
    /**
     * Generate Table of Contents property of this page
     *
     * @access public
     * @param boolean $usecache
     * @uses toc
     */
    function gen_toc($usecache = true)
    {
        $this->toc = PluginSonotsMetapage::toc($this->page, $usecache);
    }
    /**
     * Generate title property of this page
     *
     * @access public
     * @param boolean $usecache
     * @uses title
     */
    function gen_title($usecache = true)
    {
        $this->title = PluginSonotsMetapage::title($this->page, $usecache);
    }
    /**
     * Generate firsthead property of this page
     *
     * @access public
     * @param boolean $usecache
     * @uses firsthead
     */
    function gen_firsthead($usecache = true)
    {
        $this->firsthead = PluginSonotsMetapage::firsthead($this->page, $usecache);
    }

    //////////////////// static functions /////////////
    /**
     * Get relative path of a page (lower path only)
     *
     * @access public
     * @static
     * @param string $page
     * @param string $currdir
     * @return string $relname
     */
    function relname($page, $currdir = '')
    {
        if ($currdir == '') {
            return $page;
        } else {
            $currdirlen = strlen($currdir);
            if ($currdir{$currdirlen-1} !== '/') {
                ++$currdirlen; //Add strlen('/')
            }
            return substr($page, $currdirlen);
        }
    }
    /**
     * Get directory depth of a path
     *
     * @access public
     * @static
     * @param string $path
     * @return int $depth
     */
    function depth($path)
    {
        return substr_count($path, '/') + 1;
    }

    /**
     * Get reading of a page
     *
     * @access public
     * @static
     * @param string $page pagename
     * @return string reading
     * @uses sonots::get_readings
     */
    function reading($page)
    {
        $readings = sonots::get_readings((array)$page);
        return current($readings);
    }
    /**
     * Get local filename of a page
     *
     * @access public
     * @static
     * @param string $page
     * @return string
     */
    function filename($page)
    {
        return get_filename($page);
    }
    /**
     * Get timestamp of a page
     *
     * @access public
     * @static
     * @param string $page
     * @return string
     */
    function timestamp($page)
    {
        return get_filetime($page);
    }
    /**
     * Get date format of a timestamp
     *
     * @access public
     * @static
     * @param int $timestamp
     * @return string
     */
    function date($timestamp)
    {
        return format_date($timestamp);
    }
    /**
     * Get New! of a page
     *
     * @access public
     * @static
     * @param int $timestamp
     * @return string
     */
    function newdate($timestamp)
    {
        // ToDo: Implementing by myself to get more flexibility
        $date = PluginSonotsMetapage::date($timestamp);
        return do_plugin_inline('new', 'nodate', $date);
    }

    /**
     * Get newpage information of a page
     *
     * @access public
     * @static
     * @param string $page pagename
     * @return boolean
     * @uses sonots::is_newpage
     */
    function newpage($page)
    {
        return sonots::is_newpage($page);
    }

    /**
     * Get existence of a page
     *
     * @access public
     * @static
     * @param string $page
     * @return boolean
     */
    function exist($page)
    {
        return is_page($page);
    }

    /**
     * Get string used in html link
     *
     * @access public
     * @static
     * @param string $page pagename
     * @param string $option option
     *  - name|page|pagename|absolute: pagename (absolute path)
     *  - base|basename      : page basename
     *  - title              : TITLE: of page
     *  - headline|firsthead : The first headline of a page
     *  - relative|relname   : relative pagename from currdir
     * @param string $currdir current dir name($currdir is required for relative)
     * @param boolean $usecache use cache of Toc or not (used for title and headline)
     * @return string
     * @uses sonots::get_basename
     * @uses title
     * @uses firsthead
     * @uses relname
     */
    function linkstr($page, $option = 'relative', $currdir = '', $usecache = true)
    {
        switch ($option) {
        case 'name':
        case 'page':
        case 'pagename':
        case 'absolute':
        default:
            $linkstr = htmlspecialchars($page);
            break;
        case 'base':
        case 'basename':
            $linkstr = htmlspecialchars(sonots::get_basename($page));
            break;
        case 'title':
            $title = PluginSonotsMetapage::title($page, $usecache);
            if (is_null($title)) {
                $linkstr = htmlspecialchars($page);
            } else {
                $linkstr = strip_htmltag(make_link($title));
            }
            break;
        case 'firsthead':
        case 'headline':
            $firsthead = PluginSonotsMetapage::firsthead($page, $usecache);
            if (is_null($firsthead)) {
                $linkstr = htmlspecialchars($page);
            } else {
                $linkstr = strip_htmltag(make_link($firsthead));
            }
            break;
        case 'relname':
        case 'relative':
            $linkstr = htmlspecialchars(PluginSonotsMetapage::relname($page, $currdir));
            break;
        }
        return $linkstr;
    }

    /**
     * Get link of a page
     * 
     * @access public
     * @static
     * @param string $page pagename
     * @param string $linkstr linkstr
     * @param string $option option
     * - page   : link to page
     * - anchor : link to anchor
     * - off    : no link
     * @return string
     * @uses sonots::make_pagelink_nopg
     * @uses sonots::make_pageanchor
     */
    function link($page, $linkstr, $option)
    {
        switch ($option) {
        case 'page':
            $link = sonots::make_pagelink_nopg($page, $linkstr);
            break;
        case 'anchor':
            $anchor = sonots::make_pageanchor($page);
            $link = sonots::make_pagelink_nopg('', $linkstr, '#' . $anchor);
            break;
        case 'off':
            $link = $linkstr;
            break;
        }
        return $link;
    }

    /**
     * Get number of popular
     *
     * @access public
     * @static
     * @param string $page pagename
     * @param string $when 'total' or 'today' or 'yesterday' or 'recent'
     * @return int
     */
    function popular($page, $when)
    {
        static $localtime, $today, $yesterday;
        if (! isset($localtime)) {
            if (function_exists('set_timezone')) { // plus
                list($zone, $zonetime) = set_timezone(DEFAULT_LANG);
                $localtime = UTIME + $zonetime;
                $today = gmdate('Y/m/d', $localtime);
                $yesterday = gmdate('Y/m/d', gmmktime(0,0,0, gmdate('m',$localtime), gmdate('d',$localtime)-1, gmdate('Y',$localtime)));
            } else {
                $localtime = ZONETIME + UTIME;
                $today = get_date('Y/m/d'); // == get_date('Y/m/d', UTIME) == date('Y/m/d, ZONETIME + UTIME);
                $yesterday = get_date('Y/m/d', mktime(0,0,0, date('m',$localtime), date('d',$localtime)-1, date('Y',$localtime)));
            }
        }
        $counterfile = COUNTER_DIR . encode($page) . '.count';
        if (is_readable($counterfile)) {
            $lines = file($counterfile);
            $lines = array_map('rtrim', $lines);
            list($total_count, $date, $today_count, $yesterday_count, $ip) = $lines;
        } else {
            return 0;
        }
        $popular = 0;
        switch ($when) {
        case 'total':
            $popular = $total_count;
            break;
        case 'today':
            if ($date == $today) {
                $popular = $today_count;
            }
            break;
        case 'yesterday':
            if ($date == $today) {
                $popular = $yesterday_count;
            } elseif ($date == $yesterday) {
                $popular = $today_count;
            }
            break;
        case 'recent':
            if ($date == $today) {
                $popular = $today_count + $yesterday_count;
            } elseif ($date == $yesterday) {
                $popular = $today_count;
            }
            break;
        }
        return $popular;
    }
    /**
     * Get Table of Contents of a page
     * 
     * @access public
     * @static
     * @param string $page
     * @param boolean $usecache use toc cache or not
     * @return PluginSonotsToc
     * @uses PluginSonotsToc
     */
    function toc($page, $usecache = true)
    {
        return new PluginSonotsToc($page, $usecache);
    }
    /**
     * Get TITLE of the page
     * 
     * @access public
     * @static
     * @param string $page
     * @param boolean $usecache use toc cache or not
     * @return string|null TITLE or null
     * @uses PluginSonotsToc
     */
    function title($page, $usecache = true)
    {
        $toc = new PluginSonotsToc($page, $usecache);
        return $toc->get_title();
    }
    /**
     * Get first headline string of the page
     * 
     * @access public
     * @static
     * @param string $page
     * @param boolean $usecache use toc cache or not
     * @return string|null first heading string or null
     * @uses PluginSonotsToc
     */
    function firsthead($page, $usecache = true)
    {
        $toc = new PluginSonotsToc($page, $usecache);
        return $toc->get_firsthead();
    }
        
}

?>
