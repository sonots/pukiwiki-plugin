<?php

/**
 * rewritemap.inc.php - Assign URL of your choice to each page
 *
 * To use this plugin, add the following settings to .htaccess.
 * (Change RewriteBase value according to your environment.)
 *
 * RewriteEngine on
 * RewriteBase /wiki
 * RewriteCond %{REQUEST_FILENAME} !-f
 * RewriteRule ^(.+)\.html(#.*)?$ index.php?cmd=rewritemap&page=$1$2 [L]
 *
 * @author      revulo
 * @licence     http://www.gnu.org/licenses/gpl.html  GPLv2
 * @version     2.0
 * @link        http://www.revulo.com/PukiWiki/Plugin/RewriteMap.html
 */

// Postfix of the URL  ('.html', '/' etc.)
if (!defined('PLUGIN_REWRITEMAP_POSTFIX')) {
    define('PLUGIN_REWRITEMAP_POSTFIX', '.html');
}

// Configuration page for alias rules
if (!defined('PLUGIN_REWRITEMAP_ALIAS_CONFIG')) {
    define('PLUGIN_REWRITEMAP_ALIAS_CONFIG', ':config/RewriteMap');
}

// Configuration page for redirect rules
if (!defined('PLUGIN_REWRITEMAP_REDIRECT_CONFIG')) {
    define('PLUGIN_REWRITEMAP_REDIRECT_CONFIG', ':config/Redirect');
}

// Regular expression of alias rules
if (!defined('PLUGIN_REWRITEMAP_ALIAS_REGEX')) {
    define('PLUGIN_REWRITEMAP_ALIAS_REGEX', '^\|([^|]+)\|([^|]+)\|\s*$');
}

// Regular expression of redirect rules
if (!defined('PLUGIN_REWRITEMAP_REDIRECT_REGEX')) {
    define('PLUGIN_REWRITEMAP_REDIRECT_REGEX', '^\|([^|]+)\|([^|]+)\|\s*$');
}


function plugin_rewritemap_action()
{
    global $vars;

    if (empty($vars['page'])) {
        return;
    }

    if (exist_plugin('statichtml')) {
        $vars['page'] = PluginStatichtml::decode($vars['page']);
    }
    $page = plugin_rewritemap_get_pagename($vars['page']);

    if (is_page($page)) {
        check_readable($page, true, true);
        header_lastmod($page);
        $vars['page'] = $page;
        return array('msg' => '', 'body' => '');
    }

    $redirect = plugin_rewritemap_get_redirect($page);
    $nextpage = plugin_rewritemap_get_pagename($redirect);

    if (is_page($nextpage)) {
        header('HTTP/1.0 301 Moved Permanently');
        header('Location: ' . plugin_rewritemap_url($nextpage));
        exit;
    } else {
        header('HTTP/1.0 404 Not Found');
        exit('404 - Not Found');
    }
}

function plugin_rewritemap_url($page)
{
    global $defaultpage;

    $script  = get_script_uri();
    $baseurl = substr($script, 0, strrpos($script, '/')) . '/';

    if ($page == $defaultpage) {
        return $baseurl;
    }

    $alias = plugin_rewritemap_get_alias($page);
    if (empty($alias)) {
        if (exist_plugin('statichtml')) {
            $alias = PluginStatichtml::encode($page);
        } else {
            $alias = str_replace('%2F', '/', rawurlencode($page));
        }
    }
    return $baseurl . $alias . PLUGIN_REWRITEMAP_POSTFIX;
}

function plugin_rewritemap_get_pagename($alias)
{
    $rules = plugin_rewritemap_get_alias_rules();
    return isset($rules[$alias]) ? $rules[$alias] : $alias;
}

function plugin_rewritemap_get_alias($page)
{
    static $rules;

    if (empty($rules)) {
        $rules = plugin_rewritemap_get_alias_rules();
        $rules = array_flip($rules);
    }
    return isset($rules[$page]) ? $rules[$page] : '';
}

function &plugin_rewritemap_get_alias_rules()
{
    static $rules;

    if (empty($rules)) {
        plugin_rewritemap_init_alias();
        $cache = CACHE_DIR . 'rewritemap.dat';
        $data  = plugin_rewritemap_read_file($cache);
        $rules = unserialize($data);
    }
    return $rules;
}

function plugin_rewritemap_get_redirect($page)
{
    static $rules;

    if (empty($rules)) {
        plugin_rewritemap_init_redirect();
        $cache = CACHE_DIR . 'redirect.dat';
        $data  = plugin_rewritemap_read_file($cache);
        $rules = unserialize($data);
    }
    return isset($rules[$page]) ? $rules[$page] : '';
}

function plugin_rewritemap_init_alias()
{
    $config = get_filename(PLUGIN_REWRITEMAP_ALIAS_CONFIG);
    $cache  = CACHE_DIR . 'rewritemap.dat';

    if (filemtime($config) > filemtime($cache)) {
        plugin_rewritemap_update_cache('alias');
    }
}

function plugin_rewritemap_init_redirect()
{
    $config = get_filename(PLUGIN_REWRITEMAP_REDIRECT_CONFIG);
    $cache  = CACHE_DIR . 'redirect.dat';

    if (filemtime($config) > filemtime($cache)) {
        plugin_rewritemap_update_cache('redirect');
    }
}

function plugin_rewritemap_update_cache($type)
{
    if ($type == 'alias') {
        $config  = get_filename(PLUGIN_REWRITEMAP_ALIAS_CONFIG);
        $pattern = PLUGIN_REWRITEMAP_ALIAS_REGEX;
        $cache   = CACHE_DIR . 'rewritemap.dat';
    } else {
        $config  = get_filename(PLUGIN_REWRITEMAP_REDIRECT_CONFIG);
        $pattern = PLUGIN_REWRITEMAP_REDIRECT_REGEX;
        $cache   = CACHE_DIR . 'redirect.dat';
    }

    $data = plugin_rewritemap_read_file($config);

    $rules = array();
    if (preg_match_all('/' . $pattern . '/m', $data, $matches, PREG_SET_ORDER)) {
        foreach($matches as $match) {
            $from = trim($match[1]);
            $to   = trim($match[2]);
            $rules[$from] = $to;
        }
    }

    $data = serialize($rules);
    return plugin_rewritemap_write_file($cache, $data);
}

function plugin_rewritemap_rename($renames)
{
    plugin_rewritemap_update_config('alias',    $renames);
    plugin_rewritemap_update_config('redirect', $renames);
}

function plugin_rewritemap_update_config($type, $renames)
{
    if ($type == 'alias') {
        $config  = get_filename(PLUGIN_REWRITEMAP_ALIAS_CONFIG);
        $pattern = PLUGIN_REWRITEMAP_ALIAS_REGEX;
    } else {
        $config  = get_filename(PLUGIN_REWRITEMAP_REDIRECT_CONFIG);
        $pattern = PLUGIN_REWRITEMAP_REDIRECT_REGEX;
    }

    $fp = fopen($config, 'r+b');
    if ($fp === false) {
        return false;
    }
    flock($fp, LOCK_EX);
    $data  = fread($fp, filesize($config));
    $lines = explode("\n", $data);

    $updated   = false;
    $num_lines = count($lines);
    for ($i = 0; $i < $num_lines; $i++) {
        if (preg_match('/' . $pattern . '/', $lines[$i], $matches)) {
            $from = trim($matches[1]);
            $to   = trim($matches[2]);
            if (isset($renames[$to])) {
                if (SOURCE_ENCODING == 'EUC-JP') {
                    mb_regex_encoding(SOURCE_ENCODING);
                    $parts     = mb_split($from, $lines[$i], 2);
                    $parts[1]  = mb_ereg_replace($to, $renames[$to], $parts[1]);
                    $lines[$i] = implode($from, $parts);
                } else {
                    $parts     = preg_split('#' . $from . '#u', $lines[$i], 2);
                    $parts[1]  = preg_replace('#' . $to . '#u', $renames[$to], $parts[1]);
                    $lines[$i] = implode($from, $parts);
                }
                $updated = true;
            }
        }
    }

    if ($updated) {
        $data = implode("\n", $lines);
        $last = ignore_user_abort(1);
        rewind($fp);
        fwrite($fp, $data);
        fflush($fp);
        ftruncate($fp, ftell($fp));
        ignore_user_abort($last);
    }
    flock($fp, LOCK_UN);
    fclose($fp);
    return true;
}

function plugin_rewritemap_read_file($filename)
{
    $fp = fopen($filename, 'rb');
    if ($fp === false) {
        return false;
    }
    flock($fp, LOCK_SH);
    $data = fread($fp, filesize($filename));
    flock($fp, LOCK_UN);
    fclose($fp);
    return $data;
}

function plugin_rewritemap_write_file($filename, $data)
{
    $fp = fopen($filename, file_exists($filename) ? 'r+b' : 'wb');
    if ($fp === false) {
        return false;
    }
    flock($fp, LOCK_EX);
    $last = ignore_user_abort(1);
    rewind($fp);
    fwrite($fp, $data);
    fflush($fp);
    ftruncate($fp, ftell($fp));
    ignore_user_abort($last);
    flock($fp, LOCK_UN);
    fclose($fp);
    return true;
}

?>
