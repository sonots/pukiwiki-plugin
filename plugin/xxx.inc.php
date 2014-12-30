<?php
/**
 * PukiWiki Plugin eXtension Library
 *
 * @author     sonots
 * @license    http://www.gnu.org/licenses/gpl.html GPL v2
 * @link       http://lsx.sourceforge.jp/?Plugin%2Flsx.inc.php
 * @version    $Id: xxx.inc.php,v 1.0 2008-01-04 07:23:17Z sonots $
 * @package    plugin
 */
class Xxx
{
    function Xxx()
    {
    }
}
/////////////// PukiWiki eXtension //////////////////
if (! function_exists('get_heading')) {
    /**
     * Get heading strings from a wiki source line
     *
     * *** Heading Strings ((footnotes)) [id]
     *   -> array("Heading Strings", "id")
     *
     * @param string $line a wiki source line
     * @param bool   $strip cut footnotes
     * @return array [0] heading string [1] a fixed-heading anchor
     * @uses lib/html.php#make_heading
     */
    function get_heading($line, $strip = TRUE)
    {
        global $NotePattern;
        $id = make_heading($line, FALSE); // $line is modified inside
        if ($strip) {
            $line = preg_replace($NotePattern, '', $line); // cut footnotes
        }
        return array($line, $id);
    }
}

if (! function_exists('make_pagelink_nopg')) {
    /**
     * Make a hyperlink to the page without passage
     *
     * @param string $page pagename
     * @param string $alias string to be displayed on the link
     * @param string $anchor anchor
     * @param string $refer reference pagename. query '&amp;refer=' is added. 
     * @param bool $isautolink flag if this link is created via autolink or not
     * @return string link html
     * @uses lib/make_link.php#make_pagelink
     */
    function make_pagelink_nopg($page, $alias = '', $anchor = '', $refer = '', $isautolink = FALSE)
    {
        // no passage
        global $show_passage;
        $tmp = $show_passage; $show_passage = 0;
        $link = make_pagelink($page, $alias, $anchor, $refer, $isautolink);
        $show_passage = $tmp;
        return $link;
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
if (! function_exists('exec_page')) {
    /**
     * Execute (convert_html) this page
     *
     * PukiWiki API Extension
     *
     * @param string $page
     * @param string $regexp execute only matched lines (preg_grep)
     * @return boolean executed
     */
    function exec_page($page, $regexp = null)
    {
        global $vars, $get, $post;
        $lines = get_source($page);
        if (isset($regexp)) {
            $lines = preg_grep($regexp, $lines);
        }
        if (empty($lines)) return FALSE;
        $tmp_page = $vars['page'];
        $tmp_cmd  = $vars['cmd'];
        $vars['cmd'] = $get['cmd'] = $post['cmd'] = 'read';
        $vars['page'] = $get['page'] = $post['page'] = $page;
        convert_html($lines);
        $vars['page'] = $get['page'] = $post['page'] = $tmp_page;
        $vars['cmd'] = $get['cmd'] = $post['cmd'] = $tmp_cmd;
        return TRUE;
    }
}
if (! function_exists('exec_existpages')) {
    /**
     * Execute (convert_html) all pages
     *
     * PukiWiki API Extension
     *
     * @param string $regexp execute only matched lines (preg_grep)
     * @return array executed pages
     */
    function exec_existpages($regexp = null)
    {
        global $vars, $get, $post;
        $pages = get_existpages();
        $exec_pages = array();
        $tmp_page = $vars['page'];
        $tmp_cmd  = $vars['cmd'];
        $vars['cmd'] = $get['cmd'] = $post['cmd'] = 'read';
        foreach ($pages as $page) {
            $vars['page'] = $get['page'] = $post['page'] = $page;
            $lines = get_source($page);
            if (isset($regexp)) {
                $lines = preg_grep($regexp, $lines);
            }
            if (empty($lines)) continue;
            convert_html($lines);
            $exec_pages[] = $page;
        }
        $vars['page'] = $get['page'] = $post['page'] = $tmp_page;
        $vars['cmd'] = $get['cmd'] = $post['cmd'] = $tmp_cmd;
        return $exec_pages;
    }
}
/////////////// PHP eXtension ///////////////////////
if (! function_exists('get_existfiles')) {
    /**
     * Get list of files in a directory
     *
     * PHP Extension
     *
     * @access public
     * @param string $dir Directory Name
     * @param string $ext File Extension
     * @param bool $recursive Traverse Recursively
     * @return array array of filenames
     * @uses is_dir()
     * @uses opendir()
     * @uses readdir()
     */
    function &get_existfiles($dir, $ext = '', $recursive = FALSE) 
    {
        if (($dp = @opendir($dir)) == FALSE)
            return FALSE;
        $pattern = '/' . preg_quote($ext, '/') . '$/';
        $dir = ($dir[strlen($dir)-1] == '/') ? $dir : $dir . '/';
        $dir = ($dir == '.' . '/') ? '' : $dir;
        $files = array();
        while (($file = readdir($dp)) !== false ) {
            if($file != '.' && $file != '..' && is_dir($dir . $file) && $recursive) {
                $files = array_merge($files, get_existfiles($dir . $file, $ext, $recursive));
            } else {
                $matches = array();
                if (preg_match($pattern, $file, $matches)) {
                    $files[] = $dir . $file;
                }
            }
        }
        closedir($dp);
        return $files;
    }
}

if (! function_exists('is_associative_array')) {
    /**
     * Check if an array is an associative array
     *
     * PHP Extension
     *
     * @param array $array
     * @return boolean
     */
    function is_associative_array($array) 
    {
        if (!is_array($array) || empty($array))
            return false;
        $keys = array_keys($array);
        return array_keys($keys) !== $keys;
        // or
        //return is_array($array) && !is_numeric(implode(array_keys($array)));
    }
}

if (! function_exists('file_put_contents')) {
    /**
     * Write a string to a file (PHP5 has this function)
     *
     * @param string $filename
     * @param string $data
     * @param int $flags
     * @return int the amount of bytes that were written to the file, or FALSE if failure
     */
    if (! defined('FILE_APPEND')) define('FILE_APPEND', 8);
    if (! defined('FILE_USE_INCLUDE_PATH')) define('FILE_USE_INCLUDE_PATH', 1);
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

///////////// PHP Adapter /////////////////////

if (! function_exists('_')) {
    /**
     * For envirionments which have not installed gettext.
     *
     * @param string $str string
     * @return string
     */
    function &_($str)
    {
        return $str;
    }
}

////////////// ToDo: Rewrite All //////////////////////
class XxxOptionParser
{
    var $error = "";

    function parse_options($args, $options)
    {
        if (! is_associative_array($args)) {
            $args = $this->associative_args($args, $options);
            if ($this->error != "") { return; }
        }

        foreach ($args as $key => $val) {
            if ( !isset($options[$key]) ) { continue; } // for action ($vars)
            $type = $options[$key][0];

            switch ($type) {
            case 'bool':
                if($val == "" || $val == "on" || $val == "true") {
                    $options[$key][1] = true;
                } elseif ($val == "off" || $val == "false" ) {
                    $options[$key][1] = false;
                } else {
                    $this->error = htmlspecialchars("$key=$val") . " is invalid. ";
                    $this->error .= "The option, $key, accepts only a boolean value.";
                    $this->error .= "#$this->plugin($key) or #$this->plugin($key=on) or #$this->plugin($key=true) for true. ";
                    $this->error .= "#$this->plugin($key=off) or #$this->plugin($key=false) for false. ";
                    return;
                }
                break;
            case 'string':
                $options[$key][1] = $val;
                break;
            case 'sanitize':
                $options[$key][1] = htmlspecialchars($val);
                break;
            case 'number':
                // Do not parse yet, parse after getting min and max. Here, just format checking
                if ($val === '') {
                    $options[$key][1] = '';
                    break;
                }
                if ($val[0] === '(' && $val[strlen($val) - 1] == ')') {
                    $val = substr($val, 1, strlen($val) - 2);
                }
                foreach (explode(",", $val) as $range) {
                    if (preg_match('/^-?\d+$/', $range)) {
                    } elseif (preg_match('/^-?\d*\:-?\d*$/', $range)) {
                    } elseif (preg_match('/^-?\d+\+-?\d+$/', $range)) {
                    } else {
                        $this->error = htmlspecialchars("$key=$val") . " is invalid. ";
                        $this->error .= "The option, " . $key . ", accepts number values such as 1, 1:3, 1+3, 1,2,4. ";
                        $this->error .= "Specify options as \"$key=1,2,4\" or $key=(1,2,3) when you want to use \",\". ";
                        $this->error .= "In more details, a style like (1:3,5:7,9:) is also possible. 9: means from 9 to the last. ";
                        $this->error .= "Furtermore, - means backward. -1:-3 means 1,2,3 from the tail. ";
                        return;
                    }
                }
                $options[$key][1] = $val;
                break;
            case 'enum':
                if($val == "") {
                    $options[$key][1] = $options[$key][2][0];
                } elseif (in_array($val, $options[$key][2])) {
                    $options[$key][1] = $val;
                } else {
                    $this->error = htmlspecialchars("$key=$val") . " is invalid. ";
                    $this->error .= "The option, " . $key . ", accepts values from one of (" . join(",", $options[$key][2]) . "). ";
                    $this->error .= "By the way, #$this->plugin($key) equals to #$this->plugin($key=" . $options[$key][2][0] . "). ";
                    return;
                }
                break;
            case 'array':
                if ($val == '') {
                    $options[$key][1] = array();
                    break;
                }
                if ($val[0] === '(' && $val[strlen($val) - 1] == ')') {
                    $val = substr($val, 1, strlen($val) - 2);
                }
                $val = explode(',', $val);
                //$val = $this->support_paren($val);
                $options[$key][1] = $val;
                break;
            case 'enumarray':
                if ($val == '') {
                    $options[$key][1] = $options[$key][2];
                    break;
                }
                if ($val[0] === '(' && $val[strlen($val) - 1] == ')') {
                    $val = substr($val, 1, strlen($val) - 2);
                }
                $val = explode(',', $val);
                //$val = $this->support_paren($val);
                $options[$key][1] = $val;
                foreach ($options[$key][1] as $each) {
                    if (! in_array($each, $options[$key][2])) {
                        $this->error = "$key=" . htmlspecialchars(join(",", $options[$key][1])) . " is invalid. ";
                        $this->error .= "The option, " . $key . ", accepts sets of values from (" . join(",", $options[$key][2]) . "). ";
                        $this->error .= "By the way, #$this->plugin($key) equals to #$this->plugin($key=(" . join(',',$options[$key][2]) . ")). ";
                        return;
                    }
                } 
                break;
            default:
            }
        }

        return $options;
    }
    
    /**
     * Handle associative type option arguments as
     * ["prefix=Hoge/", "contents=(hoge", "hoge", "hoge)"] => ["prefix"=>"hoge/", "contents"=>"(hoge,hoge,hoge)"]
     * This has special supports for parentheses type arguments (number, array, enumarray)
     * Check option in along with.
     * @access    public
     * @param     Array $args      Original option arguments
     * @return    Array $result    Converted associative option arguments
     */
    function associative_args($args, $options)
    {
        $result = array();
        while (($arg = current($args)) !== false) {
            list($key, $val) = array_pad(explode("=", $arg, 2), 2, '');
            if (! isset($options[$key])) {
                $this->error = 'No such a option, ' . htmlspecialchars($key);
                return;
            }
            // paren support
            if ($val[0] === '(' && ($options[$key][0] == 'number' || 
                 $options[$key][0] == 'array' || $options[$key][0] == 'enumarray')) {
                while(true) {
                    if ($val[strlen($val)-1] === ')' && substr_count($val, '(') == substr_count($val, ')')) {
                        break;
                    }
                    $arg = next($args);
                    if ($arg === false) {
                        $this->error = "The # of open and close parentheses of one of your arguments did not match. ";
                        return;
                    }
                    $val .= ',' . $arg;
                }
            }
            $result[$key] = $val;
            next($args);
        }
        return $result;
    }

    function parse_numoption($optionval, $min, $max)
    {
        if ($optionval === '') {
            return '';
        }
        $result = array();
        foreach (explode(",", $optionval) as $range) {
            if (preg_match('/^-?\d+$/', $range)) {
                $left = $right = $range;
            } elseif (preg_match('/^-?\d*\:-?\d*$/', $range)) {
                list($left, $right) = explode(":", $range, 2);
                if ($left == "" && $right == "") {
                    $left = $min;
                    $right = $max;
                } elseif($left == "") {
                    $left = $min;
                } elseif ($right == "") {
                    $right = $max;
                }
            } elseif (preg_match('/^-?\d+\+-?\d+$/', $range)) {
                list($left, $right) = explode("+", $range, 2);
                $right += $left;
            }
            if ($left < 0) {
                $left += $max + 1;
            }
            if ($right < 0) {
                $right += $max + 1;
            }
            $result = array_merge($result, range($left, $right));
            // range allows like range(5, 3) also
        }
        // filter
        foreach (array_keys($result) as $i) {
            if ($result[$i] < $min || $result[$i] > $max) {
                unset($result[$i]);
            }
        }
        sort($result);
        $result = array_unique($result);

        return $result;
    }
}

?>
