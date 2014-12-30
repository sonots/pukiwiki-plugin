<?php
/**
 * Display Math Equations using mimetex.cgi
 *
 * @author     sonots
 * @license    http://www.gnu.org/licenses/gpl.html GPL v2
 * @link       http://lsx.sourceforge.jp/?Plugin%2Fmimetex.inc.php
 * @version    $Id: mimetex.inc.php,v 1.4 2007-04-01 06:10:21Z sonots $
 * @package    plugin
 */

if (! defined('INIT_DIR')) // if not Plus! 
    if (file_exists(DATA_HOME . 'init/mimetex.ini.php')) 
        include_once(DATA_HOME . 'init/mimetex.ini.php');

defined('MIMETEX_PATH') or define('MIMETEX_PATH', 'http://www.forkosh.dreamhost.com/cgi-bin/mimetex.cgi');
defined('MIMETEX_CACHE') or define('MIMETEX_CACHE', 1); // 1: Use Cache Function
defined('MIMETEX_CACHE_DIR') or define('MIMETEX_CACHE_DIR',  CACHE_DIR);
defined('MIMETEX_CACHE_URI') or define('MIMETEX_CACHE_URI',  CACHE_DIR);
defined('MIMETEX_CACHE_POSTFIX') or define('MIMETEX_CACHE_POSTFIX',  '.mimetex.gif');

function plugin_mimetex_inline()
{
    $args = func_get_args();
    array_pop($args); // drop {};
    $body = call_user_func_array('plugin_mimetex_body', $args);
    return '<span class="mimetex">' . $body . '</span>';
}

function plugin_mimetex_convert()
{
    $args = func_get_args();
    $body = call_user_func_array('plugin_mimetex_body', $args);
    return '<div class="mimetex" style="text-align:center;">' . $body . '</div>';
}


function plugin_mimetex_body()
{
    $args = func_get_args();
    $mimetex = trim(implode(",", $args));

    // Format
    $mimetex = str_replace("\r", '', $mimetex); // delete carriage return
    $mimetex = str_replace("\n", '', $mimetex); 
    //$mimetex = strtr($mimetex, ' ', '~'); // convert space into tex space style. 
    //$mimetex = str_replace("\r", '\\\\', $mimetex); // convert return into tex style return
    if (preg_match('/Safari/', getenv('HTTP_USER_AGENT'))) {
        $mimetex = strtr($mimetex, '\?', '\\'); // stupid safari converts \ into ?.
    }

    $cgi_uri = plugin_mimetex_cgi_uri($mimetex);
    if (MIMETEX_CACHE) {
        $cache_filename = plugin_mimetex_cache_filename($mimetex);
        $cache_uri      = plugin_mimetex_cache_uri($mimetex);
        if (! file_exists($cache_filename)) {
            $err = plugin_mimetex_cache($cgi_uri, $cache_filename);
            if ($err === -1) {
                return 'mimetex(): mimetex cache dir '. MIMETEX_CACHE_DIR . ' does not exist or not writable. ';
            } elseif ($err === -2) {
                return 'mimetex(): mimetex cgi ' . MIMETEX_PATH . ' does not respond. ';
            }
        }
        $src = $cache_uri;
    } else {
        $src = $cgi_uri;
    }
    return '<img src="' . $src . '" alt="' . htmlspecialchars($mimetex) . '" />';
}

function plugin_mimetex_cache_filename($str)
{
    return MIMETEX_CACHE_DIR . plugin_mimetex_cache_encode($str) . MIMETEX_CACHE_POSTFIX;
}

function plugin_mimetex_cache_uri($str)
{
    return MIMETEX_CACHE_URI . plugin_mimetex_cache_encode($str) . MIMETEX_CACHE_POSTFIX;
}

function plugin_mimetex_cache_encode($str)
{
    return md5(str_replace(' ', '', $str));
}

function plugin_mimetex_cgi_uri($mimetex)
{
    return MIMETEX_PATH . '?' . rawurlencode($mimetex);
}

function plugin_mimetex_cache($uri, $filename)
{
    $fp = fopen($uri, "rb");
    if ($fp === FALSE) { return -2; }
    $data = "";
    while (!feof($fp)) {
        $data .= fread($fp, 1000);
    }
    fclose($fp);
    $fp = fopen($filename, "wb");
    if ($fp === FALSE) { return -1; }
    fwrite($fp, $data);
    fclose($fp);
}

?>
