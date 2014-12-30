<?php
/**
 * Cache HTML Outputs of Wiki Syntax
 *
 * @author     sonots
 * @licence    http://www.gnu.org/licenses/gpl.html GPL v2
 * @link       http://lsx.sourceforge.jp/?Plugin%2Fecache.inc.php
 * @version    $Id: ecache.inc.php,v 1.5 2008-03-28 07:23:17Z sonots $
 * @package    plugin
 */

class PluginEcache
{
    function PluginEcache()
    {
        static $default_options = array(
            'page'  => NULL,
            'reset' => 'new', // 'on', 'off', soconds
        );
        $this->default_options = &$default_options;

        // init
        $this->options = $default_options;
    }
    
    // static
    var $default_options;
    // var
    var $error = '';
    var $plugin = 'ecache';
    var $options;
    
    /**
     * Ecache Plugin Convert (Block) Function
     */
    function convert()
    {
        global $vars, $defaultpage;
        $args = func_get_args();
        if (func_num_args() == 0) {
            return 'ecache(): no arugment.';
        }
        $body = array_pop($args);
        $body = str_replace("\r", "\n", $body); //multiline arg has \r
        parse_options($args, $this->options);
        $this->options['page'] = isset($this->options['page']) ? $this->options['page'] : 
            (isset($vars['page']) ? $vars['page'] : $defaultpage);
        return $this->ecache($this->options['page'], $body, $this->options['reset']);
    }
    
    /**
     * Ecache Main Function
     *
     * @param string $page page name
     * @param string $body PukiWiki Syntax text
     * @param string $reset reset option 'on', 'off', 'new', seconds
     * @param string converted html
     */
    function ecache($page, $body, $reset)
    {
        $num   = $this->get_called_num($page);
        $cache = $this->get_cachename($page, $num);
        $contents = $this->read_cache($page, $cache, $reset);
        if ($contents !== FALSE) { 
            $data = unserialize($contents);
            $this->reflect_cache($data);
        } else { // FALSE means that updating is required
            $data = $this->convert_body($page, $body);
            $contents = serialize($data);
            $this->write_cache($page, $cache, $contents);
        }
        return $data['html'];
    }

    /**
     * Reflet cached data into PukiWiki global
     *
     * @param array $data cached data
     * @return void
     */
    function reflect_cache(&$data)
    {
        $GLOBALS['head_tags']    += isset($data['head_tags']) ? $data['head_tags'] : array(); // $head_tag
        $GLOBALS['foot_tags']    += isset($data['foot_tags']) ? $data['foot_tags'] : array(); // Plus! $foot_tag
        $GLOBALS['foot_explain'] += isset($data['foot_explain']) ? $data['foot_explain'] : array(); // $notes
        if (isset($data['newtitle'])) $GLOBALS['newtitle'] = $data['newtitle']; // Plus!
    }

    /**
     * Convert PukiWiki Syntax and get a result to be cached
     *
     * @param $page page name
     * @param $body PukiWiki Syntax contents
     * @return array data sets to be cached
     */
    function convert_body($page, &$body)
    {
        global $vars, $post, $get;
        $temp = array();
        $temp['head_tags']    = $GLOBALS['head_tags'];
        $temp['foot_tags']    = $GLOBALS['foot_tags'];
        $temp['foot_explain'] = $GLOBALS['foot_explain'];

        $temp['page'] = $vars['page'];
        $vars['page'] = $post['page'] = $get['page'] = $page;
        $html = convert_html($body);
        $vars['page'] = $post['page'] = $get['page'] = $temp['page'];
        
        // wierd
        $html = preg_replace('#<p>\#spandel(.*?)(</p>)#si', '<span class="remove_word">$1', $html);
        $html = preg_replace('#<p>\#spanadd(.*?)(</p>)#si', '<span class="add_word">$1', $html);
        $html = preg_replace('#<p>\#spanend(.*?)(</p>)#si', '$1</span>', $html);
        $html = preg_replace('#&amp;spandel;#i', '<span class="remove_word">', $html);
        $html = preg_replace('#&amp;spanadd;#i', '<span class="add_word">', $html);
        $html = preg_replace('#&amp;spanend;#i', '</span>', $html);
        
        $data = array();
        $data['html']         = $html;
        $data['head_tags']    = array_diff($GLOBALS['head_tags'], $temp['head_tags']); // think of only addition
        $data['foot_tags']    = array_diff($GLOBALS['foot_tags'], $temp['foot_tags']); 
        $data['foot_explain'] = array_diff($GLOBALS['foot_explain'], $temp['foot_explain']);
        $data['newtitle']     = isset($GLOBALS['newtitle']) ? $GLOBALS['newtitle'] : NULL;
        return $data;
    }

    /**
     * Read cache
     *
     * @param string $page page name
     * @param string $cache cache file name
     * @param string $reset reset option 'on', 'off', 'new', seconds
     * @return mixed contents or FALSE if cache should be renewed
     */
    function read_cache($page, $cache, $reset = 'off')
    {
        global $vars;
        if (isset($vars['preview']) || isset($vars['realview'])) return FALSE;
        if ($reset === 'on') {
            return FALSE;
        } elseif ($reset === 'new') {
            if (is_page_newer($page, $cache)) return FALSE;
        } elseif (is_numeric($reset)) {
            if (is_page_newer($page, $cache)) return FALSE;
            $cachestamp = file_exists($cache) ? filemtime($cache) : 0;
            if (time() > $cachestamp + $sleep) return FALSE;
        }
        return file_get_contents($cache);
    }

    /**
     * Write cache
     *
     * @param string $page page name
     * @param string $cache cache file name
     * @param string $contents contents to be written into cache
     * @return boolean Success or failure
     */
    function write_cache($page, $cache, &$contents)
    {
        return file_put_contents($cache, $contents);
    }

    /**
     * Count number of that ecache is called in a page
     *
     * @param string $page page name
     * @return integer number of called
     */
    function get_called_num($page)
    {
        static $called = array();
        if (! isset($called[$page])) {
            $called[$page] = 1;
        } else {
            $called[$page]++;
        }
        return $called[$page];
    }

    /**
     * Get cache file name
     *
     * @param string $page page name
     * @param integer $num cache file number in the page
     * @return string cache file name
     */
    function get_cachename($page, $num = 1)
    {
        return CACHE_DIR . encode($page) . '_' . $num . '.' . $this->plugin;
    }

}

//////////////// PukiWiki API Extension
if (! function_exists('parse_options')) {
    function parse_options(&$args, &$options, $sep = '=')
    {
        foreach ($args as $arg) {
            list($key, $val) = array_pad(explode($sep, $arg, 2), 2, TRUE);
            if (array_key_exists($key, $options)) {
                $options[$key] = $val;
            }
        }
    }
}

if (! function_exists('is_page_newer')) {
    /**
     * Check if the page timestamp is newer than the file timestamp
     *
     * PukiWiki API Extension
     *
     * @param string $page pagename
     * @param string $file filename
     * @param bool $ignore_notimestamp Ignore notimestamp edit and see the real time editted
     * @return boolean
     */
    function is_page_newer($page, $file, $ignore_notimestamp = TRUE)
    {
        $filestamp = file_exists($file) ? filemtime($file) : 0;
        if ($ignore_notimestamp) { // See the diff file. PukiWiki Trick. 
            $pagestamp  = is_page($page) ? filemtime(DIFF_DIR . encode($page) . '.txt') : 0;
        } else {
            $pagestamp  = is_page($page) ? filemtime(get_filename($page)) : 0;
        }    
        return $pagestamp > $filestamp;
    }
}

/////////////// PHP API Extension
if (! function_exists('file_put_contents')) {
    if (! defined('FILE_APPEND')) define('FILE_APPEND', 8);
    if (! defined('FILE_USE_INCLUDE_PATH')) define('FILE_USE_INCLUDE_PATH', 1);
    /**
     * Write a string to a file (PHP5 has this function)
     *
     * PHP Compat
     *
     * @param string $filename
     * @param string $data
     * @param int $flags
     * @return int the amount of bytes that were written to the file, or FALSE if failure
     */
    function file_put_contents($filename, $data, $flags = 0)
    {
        $mode = ($flags & FILE_APPEND) ? 'a' : 'w';
        $fp = fopen($filename, $mode);
        if ($fp === false) {
            return false;
        }
        if (is_array($data)) $data = implode('', $data);
        if ($flags & LOCK_EX) flock($fp, LOCK_EX);
        $bytes = fwrite($fp, $data);
        if ($flags & LOCK_EX) flock($fp, LOCK_UN);
        fclose($fp);
        return $bytes;
    }
}

///////////////////////////////////////////
function plugin_ecache_init()
{
    global $plugin_ecache_name;
    if (class_exists('PluginEcacheUnitTest')) {
        $plugin_ecache_name = 'PluginEcacheUnitTest';
    } elseif (class_exists('PluginEcacheUser')) {
        $plugin_ecache_name = 'PluginEcacheUser';
    } else {
        $plugin_ecache_name = 'PluginEcache';
    }
}
function plugin_ecache_convert()
{
    global $plugin_ecache, $plugin_ecache_name;
    $plugin_ecache = new $plugin_ecache_name();
    $args = func_get_args();
    return call_user_func_array(array(&$plugin_ecache, 'convert'), $args);
}
?>
