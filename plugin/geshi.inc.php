<?php

/**
 * geshi.inc.php - Syntax highlighting using GeSHi library
 *
 * @author      revulo
 * @licence     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html  GPLv2
 * @version     1.3 alpha1
 * @link        http://www.revulo.com/PukiWiki/Plugin/GeSHi.html
 * @link        http://qbnz.com/highlighter/
 */

// Directory path of GeSHi library
if (!defined('PLUGIN_GESHI_LIB_DIR')) {
    define('PLUGIN_GESHI_LIB_DIR', PLUGIN_DIR . 'geshi/');
}

// Directory path of configuration files
if (!defined('PLUGIN_GESHI_CONFIG_DIR')) {
    define('PLUGIN_GESHI_CONFIG_DIR', PLUGIN_DIR . 'geshi_config/');
}

// Directory path of CSS files (should be started with SKIN_DIR)
if (!defined('PLUGIN_GESHI_CSS_DIR')) {
    define('PLUGIN_GESHI_CSS_DIR', SKIN_DIR . 'geshi/');
}

// Use stylesheets
if (!defined('PLUGIN_GESHI_USE_CSS')) {
    define('PLUGIN_GESHI_USE_CSS', true);
}

// Cache HTML of the highlighted code
if (!defined('PLUGIN_GESHI_CACHE')) {
    define('PLUGIN_GESHI_CACHE', true);
}

// Default language
if (!defined('PLUGIN_GESHI_DEFAULT_LANGUAGE')) {
    define('PLUGIN_GESHI_DEFAULT_LANGUAGE', '');
}

// Display line numbers
if (!defined('PLUGIN_GESHI_LINE_NUMBERS')) {
    define('PLUGIN_GESHI_LINE_NUMBERS', false);
}

// Regular expressions for URLs allowed to include
if (!isset($GLOBALS['plugin_geshi_allowed_url'])) {
    $GLOBALS['plugin_geshi_allowed_url'] = array(
        '^http://',
    );
}


function plugin_geshi_convert()
{
    global $vars;

    $args    = func_get_args();
    $options = plugin_geshi_get_options($args);
    $source  = '';

    if ($options['file'] != '') {
        $source = plugin_geshi_get_source($options['file']);
    } else if (substr(end($args), -1) == "\r") {
        $source = rtrim(end($args));
    }

    if ($source == '') {
        return '';
    } else if ($options['language'] == '' || $options['language'] == 'pre') {
        return '<pre>' . htmlspecialchars($source) . '</pre>';
    }

    if (isset($vars['preview']) || isset($vars['realview'])) {
        $options['cache'] = false;
    }

    $cache = plugin_geshi_get_cachename($vars['page']);
    $diff  = DIFF_DIR . encode($vars['page']) . '.txt';

    if ($options['cache'] === true && is_readable($cache) && filemtime($cache) > filemtime($diff)) {
        $html = plugin_geshi_read_file($cache);
    } else {
        $html = plugin_geshi_highlight_code($source, $options);
        if ($options['cache']) {
            plugin_geshi_write_file($cache, $html);
        }
    }

    if (PLUGIN_GESHI_USE_CSS) {
        plugin_geshi_output_css('default');
        plugin_geshi_output_css($options['language']);
    }
    return $html;
}

function plugin_geshi_get_options($args)
{
    $options = array(
        'cache'    => PLUGIN_GESHI_CACHE,
        'language' => PLUGIN_GESHI_DEFAULT_LANGUAGE,
        'number'   => PLUGIN_GESHI_LINE_NUMBERS,
        'start'    => 1,
        'file'     => '',
    );
    $boolean = array(
        ''    => true,
        'on'  => true,
        'off' => false,
    );

    $num_args = count($args);
    if (substr(end($args), -1) == "\r") {
        $num_args--;
    }

    for ($i = 0; $i < $num_args; ++$i) {
        $token = explode('=', $args[$i]);
        $key   = trim($token[0]);
        $value = isset($token[1]) ? trim($token[1]) : '';

        if (isset($options[$key])) {
            $options[$key] = isset($boolean[$value]) ? $boolean[$value] : $value;
        } else if ($value == '') {
            $options['language'] = $key;
        }
    }

    $options['language'] = strtolower($options['language']);
    $options['language'] = preg_replace('/[^a-z0-9_-]/', '', $options['language']);
    return $options;
}

function plugin_geshi_get_source($str)
{
    if (strpos($str, ':') !== false) {
        if (preg_match('#^(https?|ftps?)://#', $str)) {
            $url = $str;
        } else {
            list($interwiki, $page) = explode(':', $str, 2);
            $url = get_interwiki_url($interwiki, $page);
        }

        $allowed = false;
        foreach ($GLOBALS['plugin_geshi_allowed_url'] as $pattern) {
            if (preg_match('#' . $pattern . '#', $url)) {
                $allowed = true;
                break;
            }
        }

        if ($allowed === true) {
            return file_get_contents($url);
        }
    } else {
        // TODO (Include local file)
    }
    return '';
}

function plugin_geshi_highlight_code($source, $options)
{
    if (!class_exists('GeSHi')) {
        require PLUGIN_GESHI_LIB_DIR . 'geshi.php';
    }
    $geshi = new GeSHi($source, $options['language']);
    $geshi->set_encoding(CONTENT_CHARSET);

    if (PLUGIN_GESHI_USE_CSS) {
        $geshi->enable_classes();
    }

    if ($options['number']) {
        $geshi->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS);
        $geshi->start_line_numbers_at($options['start']);
        $geshi->set_overall_class('geshi number ' . $options['language']);

        plugin_geshi_read_setting($geshi, 'default');
        plugin_geshi_read_setting($geshi, $options['language']);
        $html = $geshi->parse_code();

        if ($geshi->header_type == GESHI_HEADER_PRE) {
            $before = array(
                '<ol',
                '/ol>',
                '</div',
                '> ',
                '  ',
            );
            $after = array(
                '<code><object><ol style="margin-top: 0; margin-bottom: 0;"',
                '/ol></object></code>',
                "\n</div",
                '>&nbsp;',
                ' &nbsp;',
            );
            $html = str_replace($before, $after, $html);
        }
    } else {
        $geshi->set_overall_class('geshi ' . $options['language']);

        plugin_geshi_read_setting($geshi, 'default');
        plugin_geshi_read_setting($geshi, $options['language']);
        $html = $geshi->parse_code();

        $html = str_replace("\n&nbsp;", "\n", $html);
    }
    return $html;
}

function plugin_geshi_read_setting(&$geshi, $language)
{
    $filename = PLUGIN_GESHI_CONFIG_DIR . $language . '.php';
    if (is_readable($filename)) {
        include $filename;
        if ($language_data) {
            $geshi->language_data =
                array_merge($geshi->language_data, $language_data);
        }
    }
}

function plugin_geshi_output_css($language)
{
    global $head_tags;
    static $css_dir, $css_uri, $flags = array();

    if (empty($css_dir)) {
        if (DATA_HOME != '' && strpos(SKIN_FILE, DATA_HOME) === 0) {
            $css_dir = DATA_HOME . PLUGIN_GESHI_CSS_DIR;
        } else {
            $css_dir = PLUGIN_GESHI_CSS_DIR;
        }

        if (defined('SKIN_URI')) {
            $css_uri = substr_replace(PLUGIN_GESHI_CSS_DIR, SKIN_URI, 0, strlen(SKIN_DIR));
        } else {
            $css_uri = $css_dir;
        }
    }

    if (empty($flags[$language])) {
        $filename = $language . '.css';
        if (is_readable($css_dir . $filename)) {
            $head_tags[] =
                '<link rel="stylesheet" type="text/css" href="' . $css_uri . $filename . '" />';
        }
        $flags[$language] = true;
    }
}

function plugin_geshi_get_cachename($page)
{
    static $counts = array();

    if (isset($counts[$page])) {
        $counts[$page]++;
    } else {
        $counts[$page] = 1;
    }
    return CACHE_DIR . 'geshi/' . encode($page) . '_' . $counts[$page] . '.html';
}

function plugin_geshi_read_file($filename)
{
    $fp = fopen($filename, 'rb');
    if ($fp === false) {
        return false;
    }
    $data = fread($fp, filesize($filename));
    fclose($fp);
    return $data;
}

function plugin_geshi_write_file($filename, $data)
{
    $fp = fopen($filename, file_exists($filename) ? 'r+b' : 'wb');
    if ($fp === false) {
        return false;
    }
    flock($fp, LOCK_EX);
    $last = ignore_user_abort(1);
    rewind($fp);
    $bytes = fwrite($fp, $data);
    fflush($fp);
    ftruncate($fp, ftell($fp));
    ignore_user_abort($last);
    fclose($fp);
    return $bytes;
}

?>
