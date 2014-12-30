<?php
/**
 * Output HTML directory, but remove malicious codes
 *
 * @author     sonots
 * @license    http://www.gnu.org/licenses/gpl.html GPL v2
 * @link       http://lsx.sourceforge.jp/?Plugin%2Fhtml.inc.php
 * @version    $Id: htmlp.inc.php,v 1.1 2007-02-24 16:28:39Z sonots $
 * @package    plugin
 */

function plugin_htmlp_inline()
{
    $args = func_get_args();
    array_pop($args); // drop {}
    $body = implode(',', $args);
    require_once('htmlpurifier/library/HTMLPurifier.auto.php');
    $purifier = new HTMLPurifier();
    $body = $purifier->purify($body);
    return $body;
}

function plugin_htmlp_convert()
{
    $args = func_get_args();
    $body = array_pop($args);
    if (substr($body, -1) != "\r") {
        return '<p>htmlp(): no argument(s).</p>';
    }
    require_once('htmlpurifier/library/HTMLPurifier.auto.php');
    $purifier = new HTMLPurifier();
    $body = $purifier->purify($body);

    $noskin = in_array("noskin", $args);
    if ($noskin) {
        pkwk_common_headers();
        print $body;
        exit;
    }
    return $body;
}

?>
