<?php
/**
 * Make a link by a relative url path 
 *
 * @author     sonots
 * @license    http://www.gnu.org/licenses/gpl.html GPL v2
 * @version    $Id: linkr.inc.php,v 1.1 2007-06-10 11:14:46 sonots $
 * @package    plugin
 */

function plugin_linkr_inline()
{
    $args = func_get_args();
    if (count($args) < 2) {
        return 'linkr(): no url.';
    }
    $linkstr = array_pop($args);
    $path    = array_shift($args);
    $href    = htmlspecialchars($path);
    $linkstr = ($linkstr !== '') ? $linkstr : $href;
    return '<a href="' . $href . '">' . $linkstr . '</a>';
}
?>
