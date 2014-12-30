<?php
/**
 * Wikipedia-like Note(Discussion) Plugin
 *
 * @author     sonots
 * @license    http://www.gnu.org/licenses/gpl.html GPL v2
 * @link       http://lsx.sourceforge.jp/?Plugin%2Fwikinote.inc.php
 * @version    $Id: wikinote.inc.php,v 1.7 2007-08-03 16:28:39Z sonots $
 * @package    plugin
 */

class PluginWikinote
{
    // static
    var $default_options; 
    var $default_def_void;
    // var
    var $error   = '';
    var $plugin  = 'wikinote';
    var $options;
    var $def_wikinote;
    var $def_void;
    var $default_template_contents = "#comment\n";

    function PluginWikinote($args)
    {
        static $default_options = array();
        static $default_def_void;
        // Modify here for default values
        if (empty($default_options)) {
            $default_options['prefix']    = 'Note/';
            $default_options['except']    = '^$';
        }
        if (! isset($default_def_void)) {
            global $non_list;
            global $whatsnew;
            global $whatsdeleted;
            global $interwiki;
            global $menubar;
            global $sidebar;
            global $headarea;
            global $footarea;
            $default_def_void = $non_list . 
                '|^' . $whatsnew     . '$' .
                '|^' . $whatsdeleted . '$' .
                '|^' . $interwiki    . '$' .
                '|^' . $menubar      . '$' .
                '|(^|\/)template$' .         // i'm lazy
                '|^' . $sidebar      . '$' . // pukiwiki plus
                '|^' . $headarea     . '$' .
                '|^' . $footarea     . '$' .
                '|^' . 'Navigation'  . '$' .
                '|^' . 'Glossary'    . '$';
        }
        // until here

        // initialization
        $this->default_options = & $default_options;
        $this->options = $this->default_options;
        $this->default_def_void = & $default_def_void;
        $this->def_void = $this->default_def_void;
        foreach ($args as $key => $val) {
            $this->options[$key] = $val;
        }
        if (strrpos($this->options['prefix'], '/') !== strlen($this->options['prefix']) - 1) {
            $this->options['prefix'] .= '/';
        }
        $this->def_wikinote = '^' . preg_quote($this->options['prefix'], '/') . '(.*)' . '$';
        $this->def_void     = $this->def_void . '|' . $this->options['except'];
    }

    /**
     * Check if wikinote is effective for the page or not
     *
     * @param string $page pagename
     * @global array vars['page'] for the default page
     */
    function is_effect($page = '')
    {
        global $vars;
        static $is_effect = array();
        $page = ($page === '') ? $vars['page'] : $page;
        if (! isset($is_effect[$page])) {
            list($mainpage, $notepage) = $this->get_mainpage_notepage($page);
            if (! is_page($mainpage)) {
                $is_effect[$page] = FALSE;
            } else {
                $is_effect[$page] = ! preg_match('/' . $this->def_void . '/', $mainpage);
            }
        }
        return $is_effect[$page];
    }

    /**
     * Show tabs (ul list)
     *
     * @param array $tabs array of array('cmd'=>,'label'=>,'href'=>)
     *  - 'cmd' is a PukiWiki cmd such as 'edit' or 'diff', additionally 'main' for mainpage and 'note' for notepage
     *  - 'label' is a string to be shown on the tab (link). It could be an img tag. 
     *   Default: 'cmd' word. 
     *  - 'href' is a link href. Ex) 'href'=>'?cmd=diff&amp;page=$page. 
     *   Reserved words: $page => PageName. 
     *   Default: Guessed from 'cmd'. 
     * @param string $page $vars['page'] is used if not specified. 
     * @retrun string
     * @global array $vars['page'] and $vars['cmd']
     */
    function show_tabs($tabs = array(
                         array('cmd'=>'main', 'label'=>'Article'),
                         array('cmd'=>'note', 'label'=>'Comment'),
                         array('cmd'=>'edit', 'label'=>'Edit', 'href'=>'?cmd=edit&amp;page=$page'),
                         array('cmd'=>'diff', 'label'=>'Diff', 'href'=>'?cmd=diff&amp;page=$page'),
                       ),
                       $page = ''
    )
    {
        global $vars, $_LINK;
        $page = ($page === '') ? $vars['page'] : $page;
        list($mainpage, $notepage) = $this->get_mainpage_notepage($page);
        $lis = array();
        foreach ($tabs as $tab) {
            $cmd = $tab['cmd'];
            $label = isset($tab['label']) ? $tab['label'] : $cmd;
            switch ($cmd) {
            case 'main':
                $link = make_pagelink($mainpage, $label);
                $selected = ($page === $mainpage) ? ' class="selected"' : '';
                break;
            case 'note':
                $link = make_pagelink($notepage, $label);
                $selected = ($page === $notepage) ? ' class="selected"' : '';
                break;
            default:
                if (isset($tab['href'])) {
                    $href = get_script_uri() . str_replace('$page', rawurlencode($page), $tab['href']);
                } elseif (isset($_LINK[$cmd])) {
                    $href = $_LINK[$cmd]; // html.php#catbody, active only in skin
                } else {
                    $href = get_script_uri() . '?cmd=' . rawurlencode($cmd) . '&amp;page=' . rawurlencode($page);
                }
                $link = '<a href="' . $href . '">' . $label . '</a>';
                $selected = ($vars['cmd'] === $cmd) ? ' class="selected"' : '';
                break;
            }
            array_push($lis, '<li id="' . 'wn_' . htmlspecialchars($cmd) . '"' . $selected . '>' . $link . '</li>');
        }
        $html = '<ul class="wikinote">' . implode("\n", $lis) . '</ul>';
        return $html;
    }

    /**
     * Check if a page is a notepage
     *
     * @param string $page
     * @return boolean
     * @global array vars['page'] for the default page
     */
    function is_notepage($page = '')
    {
        global $vars;
        $page = ($page === '') ? $vars['page'] : $page;
        static $is_notepage = array();
        if (! isset($is_notepage[$page])) {
            $is_notepage[$page] = preg_match('/' . $this->def_wikinote . '/', $page);
        }
        return $is_notepage[$page];
    }

    /**
     * Get mainpage (article) and notepage (discuss) pair from a pagename
     *
     * @param string $page
     * @return array(string, string)
     */
    function get_mainpage_notepage($page)
    {
        if ($this->is_notepage($page)) {
            $notepage  = $page;
            $mainpage  = $this->get_mainpagename($page);
        } else {
            $mainpage  = $page;
            $notepage  = $this->get_notepagename($page);
        }
        return array($mainpage, $notepage);
    }

    /**
     * Get notepage name from mainpage name
     *
     * @param string $mainpage
     * @return string notepage
     */
    function get_notepagename($mainpage)
    {
        return $this->options['prefix'] . $mainpage;
    }

    /**
     * Get mainpage name from notepage name
     *
     * @param string $notepage
     * @return string mainpage
     */
    function get_mainpagename($notepage)
    {
        $matches = array();
        preg_match('/' . $this->def_wikinote . '/', $notepage, $matches);
        return $matches[1];
    }

    /**
     * Create a wikinote page automatically
     *
     * @param string $page
     * @param boolean created or not
     */
    function autocreate_notepage($page = '')
    {
        global $vars;
        if ($vars['cmd'] != 'read') return FALSE;
        $page = ($page === '') ? $vars['page'] : $page;
        list($mainpage, $notepage) = $this->get_mainpage_notepage($page);
        if (! $this->is_effect($mainpage)) return FALSE;
        if (is_page($notepage)) return FALSE;

        $contents = auto_template($notepage);
        if ($contents == '') {
            $contents = $this->default_template_contents;
        }
        if (file_put_contents(get_filename($notepage), $contents) === FALSE) {
            return FALSE;
        }
        update_recent($notepage);
        return TRUE;
    }

    /**
     * Obsolete: Use is_effect
     * @see is_effect
     */
    function is_valid($page = '')
    {
        return $this->is_effect($page);
    }

    /**
     * Obolete: Use show_tabs
     * @see show_tabs
     */
    function show_links($cmds = array('main' => 'Article', 'note' => 'Comment'), $page = '')
    {
        $tabs = array();
        foreach ($cmds as $cmd => $label) {
            $tabs[] = array('cmd'=>$cmd, 'label'=>$label);
        }
        return $this->show_tabs($tabs, $page);
    }

    /**
     * Obsolete: Use is_notepage
     * @see is_notepage
     */
    function is_wikinote($page = '')
    {
        return $this->is_notepage($page);
    }

    /**
     * Obsolete: Use autocreate_notepage
     * @see autocreate_notepage
     */
    function autocreate_wikinote($page = '')
    {
        return $this->autocreate_notepage($page);
    }
}

if (! function_exists('update_recent')) {
    /**
     * Update recent
     * 
     * PukiWiki Version Adapter
     *
     * @param string $page
     */
    function update_recent($page)
    {
        if (is_page($page) && function_exists('lastmodified_add')) {
            lastmodified_add($page); // 1.4.7 or higher
        } elseif (function_exists('put_lastmodified')) {
            put_lastmodified();
        }
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

?>
