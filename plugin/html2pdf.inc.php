<?php
/**
 * HTML2PDF.BIZ WebAPI Plugin
 *
 * @author     sonots
 * @license    http://www.gnu.org/licenses/gpl.html GPL v2
 * @version    $Id: html2pdf.inc.php,v 1.1 2007-02-26 11:14:46 sonots $
 * @package    plugin
 */

if (! defined('PLUGIN_HTML2PDF_URL')) {
    define('PLUGIN_HTML2PDF_URL', 'http://html2pdf.biz/api?url=');
}
if (! defined('PLUGIN_HTML2PDF_RET')) {
    define('PLUGIN_HTML2PDF_RET', 'PDF'); // JSON, PDF, PNG
}

function plugin_html2pdf_action()
{
    global $vars, $defaultpage;
    $page = isset($vars['page']) ? $vars['page'] : $defaultpage;
    $url = get_script_uri() . '?' . rawurlencode($page);
    header('Location: ' . PLUGIN_HTML2PDF_URL . $url . 
        '&ret=' . PLUGIN_HTML2PDF_RET);
    return;
}

function plugin_html2pdf_inline()
{
    $url = plugin_html2pdf_get_request_uri();
    return '<a href="' . PLUGIN_HTML2PDF_URL . htmlspecialchars(rawurlencode($url)) . 
        '&amp;ret=' . PLUGIN_HTML2PDF_RET . '">' . PLUGIN_HTML2PDF_RET . '</a>';
}

// Not only script uri, whole request uri
// lib/func.php#get_script_uri
// Get absolute-URI of this request
function plugin_html2pdf_get_request_uri($init_uri = '')
{
    if ($init_uri == '') {
        // Set automatically
        $msg     = 'get_request_uri() failed: Please set $script at INI_FILE manually';
        if (isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI']{0} == '/') {
            $url  = (SERVER_PORT == 443 ? 'https://' : 'http://'); // scheme
            $url .= SERVER_NAME;    // host
            $url .= (SERVER_PORT == 80 ? '' : ':' . SERVER_PORT);  // port
            $url .= $_SERVER['REQUEST_URI'];
        } else {
            global $vars;
            $url = get_script_uri() . '?';
            $queries = array();
            $queries[] = $vars['cmd'] != '' ? 'cmd=' . rawurlencode($vars['cmd']) : '';
            $queries[] = $vars['page'] != '' ? 'page=' . rawurlencode($vars['page']) : '';
            $url .= implode('&', $queries);
        }
        if (! is_url($url, TRUE))
            die_message($msg);
    } else {
        // Set manually
        if (! is_url($init_uri, TRUE)) die_message('$url: Invalid URI');
        $url = $init_uri;
    }

    return $url;
}

?>
