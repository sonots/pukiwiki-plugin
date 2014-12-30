<?php
/**
 * Page Include Plugin
 *
 * @author     sonots <http://note.sonots.com>
 * @license    http://www.gnu.org/licenses/gpl.html    GPL
 * @link       http://note.sonots.com/?PukiWiki/includex.inc.php
 * @version    $Id: includex.inc.php 677 2007-06-05 07:23:17Z sonots $
 * @package    includex.inc.php
 */

class PluginIncludex
{
    function PluginIncludex()
    {
        // Modify here for default values (type, default, choices)
        static $default_options = array(
            'num'       => array('number',  ''),
            'except'    => array('string',  ''),
            'filter'    => array('string',  ''),
            'title'     => array('enum',    'on',  array('on', 'off', 'nolink', 'basename')), // obsolete
            'titlestr'  => array('enum',    'title',  array('name', 'off', 'basename', 'title', 'relname')),
            'titlelink' => array('bool',    true),
            'section'   => array('array',   array()),
            'permalink' => array('string',  false),
            'head'      => array('bool',    true),
        );
        static $default_section_options = array(
            'num'       => array('number',  ''),
            'depth'     => array('number',  ''),
            'except'    => array('string',  ''),
            'filter'    => array('string',  ''),
            'cache'     => array('enum',    'on',  array('on', 'off', 'reset')),
            'inclsub'   => array('bool',    false), // not yet
        );
        $this->default_options = &$default_options;
        $this->default_section_options = &$default_section_options;

        // init
        static $visited = array();
        $this->visited = &$visited;
        $this->options = $this->default_options;
        $this->section_options = $this->default_section_options;
    }

    // static
    var $default_options;
    var $default_section_options;
    var $visited;
    // var
    var $error = "";
    var $plugin = "includex";
    var $options;
    var $section_options;
    var $inclpage;
    var $lines;
    var $headlines;
    var $narrowed_headlines;
    
    function convert()
    {
        $args = func_get_args();
        $body = $this->body($args);
        if ($this->error != "" ) { return "<p>#$this->plugin(): $this->error</p>"; }
        return $body;
    }

    function body($args)
    {
        global $vars, $get, $post;

        $this->visited[$vars['page']] = TRUE;
        $this->inclpage = array_shift($args);
        $this->check_page();
        if ($this->error != "") { return; }

        $parser = new PluginIncludexOptionParser();
        $this->options = $parser->parse_options($args, $this->options);
        if ($parser->error != "") { $this->error = $parser->error; return; }

        $this->check_options();
        if ($this->error != "") { return; }
        
        $this->init_lines();
        if ($this->error !== "") { return; }

        $this->narrow_lines();
        if ($this->error !== "") { return; }

        $body = $this->frontend();
        if ($this->error !== "") { return; }

        $this->visited[$this->inclpage] = TRUE;
        return $body;
    }

    function check_options()
    {
        // support lower version
        if ($this->options['title'][1] != 'on') {
            if ($this->options['title'][1] == 'nolink') {
                $this->options['titlelink'][1] = false;
                $this->options['titlestr'][1] = 'name';
            } else {
                $this->options['titlestr'][1] = $this->options['title'][1];
            }
        }
        if ($this->options['permalink'][1] === '') {
            $this->options['permalink'][1] = _('Permalink');
        }
    }

    function check_page()
    {
        global $vars;

        if (empty($this->inclpage)) {
            $this->error = "No page is specified.";
            return;
        }
        $current = $vars['page'];
        $this->inclpage = get_fullname($this->inclpage, $current);

        if (! $this->is_page($this->inclpage)) {
            $this->error =  "$this->inclpage does not eixst.";
            return;
        }
        if (! $this->check_readable($this->inclpage, false, false)) {
            $this->error = "$this->inclpage is not readable.";
            return;
        }
        if (isset($this->visited[$this->inclpage])) {
            $this->error = "$this->inclpage is already included.";
            return;
        }
    }

    function frontend()
    {
        global $vars, $get, $post;

        $titlestr = PluginIncludex::get_titlestr($this->inclpage, $this->options['titlestr'][1]);
        $title    = PluginIncludex::get_title($this->inclpage, $titlestr, $this->options['title'][1]);
        if ($this->error != "") { return; }

        // because included page would use these variables. 
        $tmp = $vars['page'];
        $get['page'] = $post['page'] = $vars['page'] = $this->inclpage;
        if (function_exists('convert_filter')) {
            $this->lines = convert_filter($this->lines); // plus
        }
        $body = convert_html($this->lines);
        $get['page'] = $post['page'] = $vars['page'] = $tmp;
        if ($this->error != "") { return; }

        $footer = '';
        if ($this->options['permalink'][1] !== false) {
            $linkstr = $this->make_inline($this->options['permalink'][1]);
            $footer = '<p class="permalink">' . 
                make_pagelink($this->inclpage, $linkstr) . '</p>';
        }

        return $title . "\n" . $body . $footer;
    }

    // static
    function get_titlestr($inclpage, $option = null, $current = null)
    {
        switch ($option) {
        case 'off':
            $titlestr = '';
            break;
        case 'name':
            $titlestr = htmlspecialchars($inclpage);
            break;
        case 'basename':
            $titlestr = htmlspecialchars(basename($inclpage));
            break;
        case 'relname':
            if (! isset($current)) $current = $GLOBALS['vars']['page'];
            if (($i = strpos($inclpage, $current . '/')) === 0) {
                $titlestr = htmlspecialchars(substr($inclpage, strlen($current)+1));
            } else {
                $titlestr = htmlspecialchars($inclpage);
            }
            break;
        case 'on':
        case 'title':
        default:
            if (exist_plugin('contentsx')) {
                $contentsx = new PluginContentsx();
                if (method_exists($contentsx, 'get_title')) {
                    $titlestr = $contentsx->get_title($inclpage);
                    $titlestr = strip_htmltag(make_link($titlestr));
                }
            }
            if ($titlestr == '') $titlestr = htmlspecialchars($inclpage);
            break;
        }
        return $titlestr;
    }

    // static
    function get_title($inclpage, $titlestr, $option = true)
    {
        global $fixed_heading_edited;
        $anchorlink = ' ' . PluginIncludex::get_page_anchorlink($inclpage);
        $editlink = $fixed_heading_edited ? ' ' . PluginIncludex::get_page_editlink($inclpage) : '';

        if ($titlestr == '') {
            //return $ret = '<div class="' .$this->plugin . '">' . $anchorlink . '</div>';
            return '';
        }
        switch ($option) {
        case false:
            $ret = '<h1 class="includex">' . $titlestr . $editlink . $anchorlink . '</h1>';
            break;
        case true:
        default:
            $link = make_pagelink($inclpage, $titlestr);
            $ret = '<h1 class="includex">' . $link . $editlink . $anchorlink . '</h1>';
            break;
        }
        return $ret;
    }

    function narrow_lines()
    {
        $parser = new PluginIncludexOptionParser();

        if (! empty($this->options['section'][1]) && exist_plugin('contentsx')) {
            $this->section_lines();
        }

        $this->filter_lines();
        $this->except_lines();

        $num = sizeof($this->lines);
        $this->options['num'][1] = $parser->parse_numoption($this->options['num'][1], 1, $num);
        if ($parser->error !== "") { $this->error = $parser->error; return; }
        $this->num_filter_lines();

        $this->cut_head_lines();
    }

    function cut_head_lines()
    {
        // cut the headline on the first line
        if ($this->options['head'][1] === FALSE) {
            $def_headline = '/^(\*{1,3})/';
            if (preg_match($def_headline, $this->lines[0])) {
                unset($this->lines[0]);
            }
        }
    }


    function section_lines()
    {
        if (empty($this->options['section'][1])) {
            return;
        }
        $parser = new PluginIncludexOptionParser();
        $this->section_options = $parser->parse_options($this->options['section'][1], $this->section_options);
        if ($parser->error != "") { $this->error = $parser->error; return; }
        
        // what a public class! hehehe
        $contentsx = new PluginContentsx();
        $contentsx->options['include'][1]  = false;
        $contentsx->options['fromhere'][1] = false;
        $contentsx->options['page'][1]     = $contentsx->check_page($this->inclpage);
        $this->headlines = $contentsx->get_metalines($this->inclpage);
        if ($contentsx->error != "") { $this->error = $contentsx->error; return; }
        foreach ($this->section_options as $key => $val) {
            $contentsx->options[$key] = $val;
        }
        
        $contentsx->narrow_metalines();
        if ($contentsx->error != "") { $this->error = $contentsx->error; return; }
        $this->narrowed_headlines = $contentsx->metalines;

        $size = sizeof($this->headlines);
        $this->section_options['num'][1] = $parser->parse_numoption($this->section_options['num'][1], 0, $size);
        $lines = array();
        if (in_array(0, $this->section_options['num'][1])) {
            $linenum = $this->headlines[0]['linenum'];
            $lines = array_merge($lines, array_splice($this->lines, 0, $linenum));
        }
        // FutureWork: Do no rely on contentsx's cache as much as possible. 
        $i = 0; $size = sizeof($this->headlines);
        foreach ($this->narrowed_headlines as $narrowed_headline) {
            $linenum = $narrowed_headline['linenum'];
            for (; $i < $size; $i++ ) {
                $current = $i;
                if ($linenum != $this->headlines[$current]['linenum']) {
                    continue;
                }
                $next = $i + 1;
                if ($next < $size) {
                    $len = $this->headlines[$next]['linenum'] - $linenum;
                    $lines = array_merge($lines, array_slice($this->lines, $linenum, $len));
                } else {
                    $lines = array_merge($lines, array_slice($this->lines, $linenum));
                }
                break;
            }
        }
        $this->lines = $lines;
    }

    function num_filter_lines()
    {
        if ($this->options['num'][1] === '') {
            return;
        }
        $lines = array();
        foreach ($this->options['num'][1] as $num) {
            $lines[] = $this->lines[$num - 1];
        }
        $this->lines = $lines;
    }

    function filter_lines()
    {
        if ($this->options['filter'][1] === "") {
            return;
        }
        $lines = array();
        foreach ($this->lines as $line) {
            if (ereg($this->options['filter'][1], $line)) {
                $lines[] = $line;
            }
        }
        $this->lines = $lines;
    }

    function except_lines()
    {
        if ($this->options['except'][1] === "") {
            return;
        }
        $lines = array();
        foreach ($this->lines as $line) {
            if (! ereg($this->options['except'][1], $line)) {
                $lines[] = $line;
            }
        }
        $this->lines = $lines;
    }

    function init_lines()
    {
        $this->lines = $this->get_source($this->inclpage);
    }
    
    // static
    function get_page_anchor($page) {
        // anchor must be '^[A-Za-z][A-Za-z0-9_-]*'
        return 'z' . md5($page);
    }
    
    // PukiWiki API Extension
    
    // convert inline plugins
    // PukiWiki API InlineConverter does htmlspecialchars, too. 
    // refer plugin/make_link.php#make_link
    // static
    function make_inline($string, $page = '')
    {
        global $vars;
        static $converter;
        
        if (! isset($converter)) $converter = new InlineConverter(array('plugin'));
        
        $clone = $converter->get_clone($converter);
        return $clone->convert($string, ($page != '') ? $page : $vars['page']);
    }
    
    // refer plugin/aname.inc.php
    // static
    function get_page_anchorlink($page) {
        global $_symbol_anchor;
        global $pkwk_dtd;
        
        $id = $this->get_page_anchor($page);
        $id = htmlspecialchars($id);

        // aname allows only fiexed_anchors such as x83dvkd8
        //if (exist_plugin_inline('aname')) {
        //$link = do_plugin_inline('aname', "$anchor,super,$_symbol_anchor");
        //}

        if (isset($pkwk_dtd) && $pkwk_dtd < PKWK_DTD_XHTML_1_1) {
            $attr_id = ' id="' . $id . '" name="' . $id . '"';
        } else {
            $attr_id = ' id="' . $id . '"';
        }
        $attr_href  = ' href="#' . $id . '"';
        $attr_title = ' title="' . $id . '"';
        $attr_class = ' class="anchor_super"';
        $link  = '<a' . $attr_class . $attr_id . $attr_href . $attr_title . '>' . $_symbol_anchor . '</a>';
        return $link;
    }
    
    // static
    function get_page_editlink($page) {
        $r_page = rawurlencode($page);
        $link  = '<a class="anchor_super" href="' . get_script_uri() . '?cmd=edit&amp;page=' . $r_page . '">';
        $link .= '<img class="paraedit" src="' . IMAGE_DIR . 'edit.png" alt="Edit" title="Edit" />';
        $link .= '</a>';
        return $link;
    }

    function get_source($page)
    {
        return get_source($page);
    }
    
    function is_page($page)
    {
        return is_page($page);
    }

    function check_readable($page, $flag, $flag)
    {
        return check_readable($page, $flag, $flag);
    }

}

///////////////////////////////////////
class PluginIncludexOptionParser
{
    var $error = "";

    function parse_options($args, $options)
    {
        if (! $this->is_associative_array($args)) {
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

    function option_debug_print($options) {
        foreach ($options as $key => $val) {
            $type = $val[0];
            $val = $val[1];
            if(is_array($val)) {
                $val=join(',', $val);
            }
            $body .= "$key=>($type, $val),";
        }
        return $body;
    }

    // php extension
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

// php extension
if (! function_exists('_')) {
    function &_($str)
    {
        return $str;
    }
}

////////////////////////////////////////////
function plugin_includex_common_init()
{
    global $plugin_includex;
    if (class_exists('PluginIncludexUnitTest')) {
        $plugin_includex = new PluginIncludexUnitTest();
    } elseif (class_exists('PluginIncludexUser')) {
        $plugin_includex = new PluginIncludexUser();
    } else {
        $plugin_includex = new PluginIncludex();
    }
}

function plugin_includex_convert()
{
    global $plugin_includex;  plugin_includex_common_init();
    $args = func_get_args();
    return call_user_func_array(array(&$plugin_includex, 'convert'), $args);
}

if (! defined('INIT_DIR')) // if not Plus! 
    if (file_exists(DATA_HOME . 'init/includex.ini.php')) 
        include_once(DATA_HOME . 'init/includex.ini.php');

?>
