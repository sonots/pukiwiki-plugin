<?php
/**
 * Navi plugin: Show DocBook-like navigation bar and contents
 *
 * @author     sonots
 * @license    http://www.gnu.org/licenses/gpl.html GPL v2
 * @version    $Id: navi.inc.php,v 1.1 2007-06-10 11:14:46 sonots $
 *             based on PukiWiki Plus i18n navi.inc.php, v1.22.4
 * @package    plugin
 *
 * Usage:
 *   #navi(prefix,reverse,place,style)
 * 
 * Added Parameter:
 *
 *   place=header|footer - header puts underline, footer puts overline
 *   style=simple|detail - simple is DocBook header style as explained 
 *                         below, detail is DocBook footer style. 
 *                         simple is the default for place=header.
 *                         detail is the default for place=footer.
 * Example:
 *   #navi(Hoge,,footer,simple)
 *  -----
 *   #navi(Hoge)
 *   #navi(Hoge) 2nd call == #navi(Hoge,,footer,detail) as original navi
 */
/*
 * Usage:
 *   #navi(contents-page-name)   <for ALL child pages>
 *   #navi([contents-page-name][,reverse]) <for contents page>
 *
 * Parameter:
 *   contents-page-name - Page name of home of the navigation (default:itself)
 *   reverse            - Show contents revese
 *
 * Behaviour at contents page:
 *   Always show child-page list like 'ls' plugin
 *
 * Behaviour at child pages:
 *
 *   The first plugin call - Show a navigation bar like a DocBook header
 *
 *     Prev  <contents-page-name>  Next
 *     --------------------------------
 *
 *   The second call - Show a navigation bar like a DocBook footer
 *
 *     --------------------------------
 *     Prev          Home          Next
 *     <pagename>     Up     <pagename>
 *
 * Page-construction example:
 *   foobar    - Contents page, includes '#navi' or '#navi(foobar)'
 *   foobar/1  - One of child pages, includes one or two '#navi(foobar)'
 *   foobar/2  - One of child pages, includes one or two '#navi(foobar)'
 */

// Exclusive regex pattern of child pages
if (! defined('PLUGIN_NAVI_EXCLUSIVE_REGEX'))
    define('PLUGIN_NAVI_EXCLUSIVE_REGEX', '/'.$GLOBALS['non_list'].'/');
// Insert <link rel=... /> tags into XHTML <head></head>
if (! defined('PLUGIN_NAVI_LINK_TAGS'))
    define('PLUGIN_NAVI_LINK_TAGS', FALSE);	// FALSE, TRUE

function plugin_navi_convert()
{
    global $vars, $script, $head_tags;
//    global $_navi_prev, $_navi_next, $_navi_up, $_navi_home;
    static $navi = array(array());
    $_navi_prev = _('Prev');
    $_navi_next = _('Next');
    $_navi_up   = _('Up');
    $_navi_home = _('Home');

    $current = $vars['page'];
    $reverse = FALSE;
    if (func_num_args()) {
        list($home, $reverse, $place, $style) = array_pad(func_get_args(), 1, '');
        // strip_bracket() is not necessary but compatible
        $home    = get_fullname(strip_bracket($home), $current);
        $is_home = ($home == $current);
        if (! is_page($home)) {
            return '#navi(contents-page-name): No such page: ' .
                htmlspecialchars($home) . '<br />';
        } else if (! $is_home &&
            ! preg_match('/^' . preg_quote($home, '/') . '/', $current)) {
            return '#navi(' . htmlspecialchars($home) .
                '): Not a child page like: ' .
                htmlspecialchars($home . '/' . basepagename($current)) .
                '<br />';
        }
        $reverse = (strtolower($reverse) == 'reverse');
    } else {
        $home    = $vars['page'];
        $is_home = TRUE; // $home == $current
    }

    $pages  = array();
    if (isset($navi[$home][$current])) { // 2nd call
        if (! isset($place)) $place = 'footer';
    }
    if (! isset($style) && $place == 'footer') {
        $style = 'detail';
    }
    if (! isset($navi[$home][$current])) {
        $navi[$home][$current] = array(
            'up'   =>'',
            'prev' =>'',
            'prev1'=>'',
            'next' =>'',
            'next1'=>'',
            'home' =>'',
            'home1'=>'',
        );

        $pages = preg_grep('/^' . preg_quote($home, '/') .
            '($|\/)/', auth::get_existpages());
        if (PLUGIN_NAVI_EXCLUSIVE_REGEX != '') {
            // If old PHP could use preg_grep(,,PREG_GREP_INVERT)...
            $pages = array_diff($pages,
                preg_grep(PLUGIN_NAVI_EXCLUSIVE_REGEX, $pages));
        }
        $pages[] = $current; // Sentinel :)
        $pages   = array_unique($pages);
        natcasesort($pages);
        if ($reverse) $pages = array_reverse($pages);

        $prev = $home;
        foreach ($pages as $page) {
            if ($page == $current) break;
            $prev = $page;
        }
        $next = current($pages);

        $pos = strrpos($current, '/');
        $up = '';
        if ($pos > 0) {
            $up = substr($current, 0, $pos);
            $navi[$home][$current]['up']    = make_pagelink($up, $_navi_up);
        }
        if (! $is_home) {
            $navi[$home][$current]['prev']  = htmlspecialchars($prev);
            $navi[$home][$current]['prev1'] = make_pagelink($prev, $_navi_prev);
        } else {
            $navi[$home][$current]['prev'] = '&nbsp;';
            $navi[$home][$current]['prev1'] = '&nbsp;';
        }
        if ($next != '') {
            $navi[$home][$current]['next']  = htmlspecialchars($next);
            $navi[$home][$current]['next1'] = make_pagelink($next, $_navi_next);
        } else {
            $navi[$home][$current]['next'] = '&nbsp;';
            $navi[$home][$current]['next1'] = '&nbsp;';
        }
        $navi[$home][$current]['home']  = make_pagelink($home);
        $navi[$home][$current]['home1'] = make_pagelink($home, $_navi_home);

        // Generate <link> tag: start next prev(previous) parent(up)
        // Not implemented: contents(toc) search first(begin) last(end)
        if (PLUGIN_NAVI_LINK_TAGS) {
            foreach (array('start'=>$home, 'next'=>$next,
                'prev'=>$prev, 'up'=>$up) as $rel=>$_page) {
                if ($_page != '') {
                    $s_page = htmlspecialchars($_page);
                    $r_page = rawurlencode($_page);
                    $head_tags[] = ' <link rel="' .
                        $rel . '" href="' . $script .
                        '?' . $r_page . '" title="' .
                        $s_page . '" />';
                }
            }
        }
    }

    $ret = '';

    if ($is_home) {
        // Show contents
        $count = count($pages);
        if ($count == 0) {
            return '#navi(contents-page-name): You already view the result<br />';
        } else if ($count == 1) {
            // Sentinel only: Show usage and warning
            $home = htmlspecialchars($home);
            $ret .= '#navi(' . $home . '): No child page like: ' .
                $home . '/Foo';
        } else {
            $ret .= '<ul>';
            foreach ($pages as $page)
                if ($page != $home)
                    $ret .= ' <li>' . make_pagelink($page) . '</li>';
            $ret .= '</ul>';
        }

    } elseif ($style === 'detail') {
        $ret = <<<EOD
<ul class="navi" style="width:100%;list-style:none;text-align:right;padding:0px;margin:0px;">
<li class="navi_left" style="width:40%;float:left;text-align:left;">{$navi[$home][$current]['prev1']}<br />{$navi[$home][$current]['prev']}</li>
<li class="navi_none" style="width:20%;float:left;text-align:center;">{$navi[$home][$current]['home1']}<br />{$navi[$home][$current]['up']}</li>
<li class="navi_right" style="width:40%;float:none;">{$navi[$home][$current]['next1']}<br />{$navi[$home][$current]['next']}</li>
</ul>
EOD;
    } else { // Header
        $ret = <<<EOD
<ul class="navi" style="width:100%;list-style:none;text-align:right;padding:0px;margin:0px;">
<li class="navi_left" style="width:40%;float:left;text-align:left;">{$navi[$home][$current]['prev1']}</li>
<li class="navi_none" style="width:20%;float:left;text-align:center;">{$navi[$home][$current]['home1']}</li>
<li class="navi_right" style="width:40%;float:none;">{$navi[$home][$current]['next1']}</li>
</ul>
EOD;
    }

    if ($place === 'footer') {
        $ret = '<hr class="full_hr" />' . $ret;
    } else {
        $ret = $ret . '<hr class="full_hr" />';
    } 
    return $ret;
}

if (! class_exists('auth')) {
    class auth
    {
        function get_existpages($dir = DATA_DIR, $ext = '.txt')
        {
            return get_existpages($dir, $ext);
        }
    }
}

?>
