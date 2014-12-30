<?php
/**
 * Show a image by a relative url path
 *
 * @author     sonots
 * @license    http://www.gnu.org/licenses/gpl.html GPL v2
 * @version    $Id: imgr.inc.php,v 1.1 2007-06-10 11:14:46 sonots $
 * @package    plugin
 */

function plugin_imgr_inline()
{
    $args = func_get_args();
    if (count($args) < 2) {
        return 'imgr(): no url.';
    }
    $altstr = array_pop($args);
    $path   = array_shift($args);
    $src    = htmlspecialchars($path);
    $altstr = ($altstr !== '') ? $altstr : $src;
    return '<img src="' . $src . '" alt="' . $altstr . '" />';
}
?>
