<?php
/**
 * Make a link to cmd 
 *
 * @author     sonots
 * @license    http://www.gnu.org/licenses/gpl.html GPL v2
 * @version    $Id: cmd.inc.php,v 1.1 2007-06-10 11:14:46 sonots $
 * @package    plugin
 */

function plugin_cmd_inline()
{
    $args = func_get_args();
    if (count($args) < 2) {
        return 'cmd(): &amp;cmd(cmd,[page]){linkstr};';
    }
    $linkstr = array_pop($args);
    $cmd     = array_shift($args);
    $page    = empty($args) ? '' : array_shift($args);
    $linkstr = ($linkstr === '') ? htmlspecialchars($cmd) : $linkstr;
    $href    = plugin_cmd_getlink($cmd, $page);
    $href    = (is_null($href)) ? get_script_uri() . '?cmd=' . rawurlencode($cmd) : $href;
    return '<a class="cmd" href="' . $href . '">' . $linkstr . '</a>';
}

function plugin_cmd_getlink($cmd, $page = '')
{
	global $vars;
	global $defaultpage, $whatsnew;

    if ($page == '') {
        $page = isset($vars['page']) ? $vars['page'] : $defaultpage;
    }
    $r_page = rawurlencode($page);
    $script = get_script_uri();

	$_LINK = array();
    // refer lib/html.inc.php
    // Future Work: Use only necessary one
    $_LINK['add']      = "$script?cmd=add&amp;page=$r_page";
    $_LINK['backup']   = "$script?cmd=backup&amp;page=$r_page";
    $_LINK['copy']     = "$script?plugin=template&amp;refer=$r_page";
    $_LINK['diff']     = "$script?cmd=diff&amp;page=$r_page";
    $_LINK['edit']     = "$script?cmd=edit&amp;page=$r_page";
    $_LINK['filelist'] = "$script?cmd=filelist";
    $_LINK['freeze']   = "$script?cmd=freeze&amp;page=$r_page";
    $_LINK['help']     = "$script?cmd=help";
    $_LINK['list']     = "$script?cmd=list";
    $_LINK['menu']     = "$script?$menubar";
    $_LINK['new']      = "$script?plugin=newpage&amp;refer=$r_page";
    $_LINK['newsub']   = "$script?plugin=newpage_subdir&amp;directory=$r_page";
    $_LINK['read']     = "$script?cmd=read&amp;page=$r_page";
    $_LINK['rdf']      = "$script?cmd=rss&amp;ver=1.0";
    $_LINK['recent']   = "$script?" . rawurlencode($whatsnew);
    $_LINK['refer']    = "$script?plugin=referer&amp;page=$r_page";
    $_LINK['reload']   = "$script?$r_page";
    $_LINK['rename']   = "$script?plugin=rename&amp;refer=$r_page";
    $_LINK['print']    = "$script?plugin=print&amp;page=$r_page";
    $_LINK['rss']      = "$script?cmd=rss";
    $_LINK['rss10']    = "$script?cmd=rss&amp;ver=1.0"; // Same as 'rdf'
    $_LINK['rss20']    = "$script?cmd=rss&amp;ver=2.0";
    $_LINK['mixirss']  = "$script?cmd=mixirss";         // Same as 'rdf' for mixi
    $_LINK['skeylist']   = "$script?cmd=skeylist&amp;page=$r_page";
    $_LINK['linklist']   = "$script?cmd=linklist&amp;page=$r_page";
    $_LINK['log_browse'] = "$script?cmd=logview&amp;kind=browse&amp;page=$r_page";
    $_LINK['log_update'] = "$script?cmd=logview&amp;page=$r_page";
    $_LINK['log_down']   = "$script?cmd=logview&amp;kind=download&amp;page=$r_page";
    $_LINK['search']   = "$script?cmd=search";
    $_LINK['side']     = "$script?$sidebar";
    $_LINK['source']   = "$script?plugin=source&amp;page=$r_page";
    $_LINK['template'] = "$script?plugin=template&amp;refer=$r_page";
    $_LINK['top']      = "$script";
    return $_LINK[$cmd];
}

?>
