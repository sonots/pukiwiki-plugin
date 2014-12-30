<?php
/**
 * Make a link to self-pukiwiki url by omitting pukiwiki url
 * so that it is not necessary to create InterWikiName for self-pukiwiki url
 *
 * @author     sonots
 * @license    http://www.gnu.org/licenses/gpl.html GPL v2
 * @version    $Id: intrawiki.inc.php,v 1.1 2007-06-10 11:14:46 sonots $
 * @package    plugin
 */

function plugin_intrawiki_inline()
{
    $args = func_get_args();
    if (count($args) < 2) {
        return 'intrawiki(): &amp;intrawiki(pukiwiki query){linkstr};';
    }
    $linkstr = array_pop($args);
    $query   = array_shift($args);
    $href    = get_script_uri() . '?' . htmlspecialchars($query);
    $linkstr = ($linkstr !== '') ? $linkstr : htmlspecialchars($query);
    return '<a href="' . $href . '">' . $linkstr . '</a>';
}
?>
