<?php
/**
 *  Tag Plugin
 *
 *  @author     sonots
 *  @license    http://www.gnu.org/licenses/gpl.html    GPL
 *  @link       http://lsx.sourceforge.jp/?Plugin%2Ftag.inc.php
 *  @version    $Id: tag.inc.php,v 1.27 2008-01-04 07:23:17Z sonots $
 *  @package    plugin
 */

class PluginTag extends Tag
{
    function PluginTag()
    {
        parent::Tag();
        static $conf = array();
        if (empty($conf)) {
            $conf['listcmd'] = get_script_uri() . '?cmd=lsx&amp;tag=';
        }
        $this->conf = & $conf;
    }

    var $conf;

    function action() // clean cache
    {
        set_time_limit(0);
        global $vars;

        // remove all files
        $files = $this->get_items_filenames();
        $files = array_merge($files, $this->get_tags_filenames());
        $files = array_merge($files, (array)$this->get_tagcloud_filename());
        foreach ($files as $file) {
            unlink($file);
        }
        // execute all pages
        $exec_pages = exec_existpages('/&tag\([^;]*\);/');
        if (empty($exec_pages)) {
            $body = '';
        } else {
            $links = array_map('make_pagelink', $exec_pages);
            $body = '<p>Following pages were executed to assure:</p>'
                . '<p>' . implode("<br />\n", $links) . '</p>';
        }
        $body .= $this->display_tagcloud(NULL);
        return array('msg'=>'clean tag caches', 'body'=>$body);
    }

    function convert() // tagcloud
    {
        $limit = 20;
        $relate_tag = null;
        $args  = func_get_args();
        switch (count($args)) {
        case 2:
            $relate_tag = $args[1];
        case 1:
            $limit = $args[0];
            break;
        default:
            break;
        }
        if ($limit === "0") {
            $limit = null;
        }
        return $this->display_tagcloud($limit, $relate_tag);
    }

    function inline() // tagging
    {
        if (func_num_args() == 0){
            return 'tag(): no argument(s). ';
        }
        global $vars, $defaultpage; 
        $page = isset($vars['page']) ? $vars['page'] : $defaultpage;
        $args = func_get_args(); 
        array_pop($args);  // drop {}
        $tags = $args;
        
        if (isset($vars['preview']) || isset($vars['realview']) ||
            is_page_newer($page, $this->get_tags_filename($page))) {
            $this->save_tags($page, $tags);
        }
        return $this->display_tags($page);
    }

    function display_tagcloud($limit = null, $tag = null)
    {
        $view = new TagCloud();
        $tagcloud = $this->get_tagcloud($limit, $tag);
        foreach ($tagcloud as $tag => $count) {
            $url = $this->conf['listcmd'] . rawurlencode($tag);
            $view->add(htmlspecialchars($tag), $url, $count);
        }
        return $view->html();
    }

    function display_tags($page)
    {
        $tags = $this->get_tags($page);
        $ret = '<span class="tag">';
        $ret .= 'Tag: ';
        foreach ($tags as $tag) {
            $url = $this->conf['listcmd'] . rawurlencode($tag);
            $ret .= '<a href="' . $url . '">' . htmlspecialchars($tag) . '</a> ';
        }
        $ret .= '</span>';

        global $head_tags;
        $head_tags[] = ' <meta name="keywords" content="' . 
            htmlspecialchars(implode(', ', $tags)) . '" />';

        return $ret;
    }

    function get_items_filename($tag)
    {
        $tag = $this->normalize_tags($tag);
        return CACHE_DIR . encode($tag) . '_tag.tag';
    }
    function get_items_filenames()
    {
        return  $this->get_existfiles(CACHE_DIR, '_tag.tag');
    }
    function get_tags_filename($page)
    {
        return CACHE_DIR . encode($page) . '_page.tag';
    }
    function get_tags_filenames()
    {
        return $this->get_existfiles(CACHE_DIR, '_page.tag');
    }
    function get_tagcloud_filename()
    {
        return CACHE_DIR . 'tagcloud.tag';
    }
    /**
     * Obsolete
     *
     * @uses get_items_by_tagtok
     */
    function get_taggedpages($tagtok)
    {
        return $this->get_items_by_tagtok($tagtok);
    }
}

//////////////// PukiWiki API Extension
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

////////////////////////////////
function plugin_tag_init()
{
    global $plugin_tag_name;
    if (class_exists('PluginTagUnitTest')) {
        $plugin_tag_name = 'PluginTagUnitTest';
    } elseif (class_exists('PluginTagUser')) {
        $plugin_tag_name = 'PluginTagUser';
    } else {
        $plugin_tag_name = 'PluginTag';
    }
}

function plugin_tag_inline()
{
    global $plugin_tag, $plugin_tag_name;
    $plugin_tag = new $plugin_tag_name();
    $args = func_get_args();
    return call_user_func_array(array(&$plugin_tag, 'inline'), $args);
}

function plugin_tag_convert()
{
    global $plugin_tag, $plugin_tag_name;
    $plugin_tag = new $plugin_tag_name();
    $args = func_get_args();
    return call_user_func_array(array(&$plugin_tag, 'convert'), $args);
}

function plugin_tag_action()
{
    global $plugin_tag, $plugin_tag_name;
    $plugin_tag = new $plugin_tag_name();
    return call_user_func(array(&$plugin_tag, 'action'));
}

/**
 *  Tag Class
 *
 *  @author     sonots
 *  @license    http://www.gnu.org/licenses/gpl.html    GPL2
 *  @link       http://lsx.sourceforge.jp/?Plugin%2Ftag.inc.php
 */
class Tag
{
    /**
     * Items of a tag
     *
     * Associative array of items whose key is a tag 
     * and values are an array of items
     *
     * @var array
     *
     * [tag] = array(item1, item2, item3, ...)
     */
    var $items;
    /**
     * Tags of an item
     *
     * Associative array of tags whose key is an item
     * and values are an array of keys
     *
     * @var array
     *
     * [item] = array(tag1, tag2, tag3, ...)
     */
    var $tags;
    /**
     * Tagcloud
     *
     * Associative array whose key is a tag
     * and values are the number of items
     *
     * @var array
     */
    var $tagcloud;
    /**
     * Reserved keys
     *
     * Strings which have special meanings.
     * They can not be used for tag strings
     *
     * @static array
     */
    var $reserved_keys = array('prod' => '^', 'diff' => '-');

    function Tag($items = array(), $tags = array(), $tagcloud = null)
    {
        $this->items = $items; 
        $this->tags = $tags;
        $this->tagcloud = $tagcloud;
    }

    /**
     * Get filename which stores items tagged by a tag
     *
     * Overwrite Me!
     *
     * @param string $tag
     * @return string
     */
    function get_items_filename($tag)
    {
        $tag = $this->normalize_tags($tag);
        return str_replace('%', '', rawurlencode($tag)) . '_items.tag';
    }
    /**
     * Get all items_filename
     *
     * Overwrite Me!
     *
     * @return array
     */
    function get_items_filenames()
    {
        return $this->get_existfiles('.', '_items.tag');
    }
    /**
     * Get filename which stores tags of an item
     *
     * Overwrite Me!
     *
     * @param string $item
     * @return string
     */
    function get_tags_filename($item)
    {
        return str_replace('%', '', rawurlencode($item)) . '_tags.tag';
    }
    /**
     * Get all tags_filename
     *
     * Overwrite Me!
     *
     * @return array
     */
    function get_tags_filenames()
    {
        return $this->get_existfiles('.', '_tags.tag');
    }
    /**
     * Get filename which stores tagcloud
     *
     * Overwrite Me!
     *
     * @return string
     */
    function get_tagcloud_filename()
    {
        return 'tagcloud.tag';
    }

    /**
     * Get tags of an item from a storage (file or db)
     *
     * Overwrite Me if you don't like the file format
     *
     * @param string $item
     * @param string $filename
     * @return array
     */
    function get_tags_from_storage($item, $filename = null)
    {
        if ($item === null) return false;
        if ($filename === null) $filename = $this->get_tags_filename($item);
        if (! file_exists($filename)) return array();
        $tags = array_map('rtrim', file($filename));
        return $tags;
    }

    /**
     * Get items tagged by a tag from a storage (file or db)
     *
     * Overwrite Me if you don't like the file format
     *
     * @param string $tag
     * @param string $filename
     * @return array
     */
    function get_items_from_storage($tag, $filename = null)
    {
        if ($filename === null) $filename = $this->get_items_filename($tag);
        if (! file_exists($filename)) return array();
        $items = array_map('rtrim', file($filename));
        return $items;
    }

    /**
     * Get tagcloud from a storage (file or db)
     *
     * Overwrite Me if you don't like the file format
     *
     * @return array
     */
    function get_tagcloud_from_storage()
    {
        $filename = $this->get_tagcloud_filename();
        $tagcloud = array();
        if (file_exists($filename)) {
            $lines = file($filename);
            if (empty($lines)) return array();
            $lines = array_map('rtrim', $lines);
            foreach ($lines as $line) {
                list($tag, $count) = explode("\t", $line);
                $tagcloud[$tag] = $count;
            }
        }
        return $tagcloud;
    }

    /**
     * Set tags of an item into a storage (file or db)
     *
     * Overwrite Me if you don't like the file format
     *
     * @param string $item
     * @param array  $tags
     * @param string $filename
     * @return boolean
     */
    function set_tags_into_storage($item, $tags, $filename = null)
    {
        if ($filename === null) $filename = $this->get_tags_filename($item);
        if (empty($tags) && file_exists($filename)) {
            return unlink($filename);
        }
        $contents = implode("\n", $tags) . "\n";
        return $this->file_put_contents($filename, $contents);
    }

    /**
     * Set items tagged by a tag into a storage (file or db)
     *
     * Overwrite Me if you don't like the file format
     *
     * @param string $tag
     * @param array  $items
     * @param string $filename
     * @return boolean
     */
    function set_items_into_storage($tag, $items, $filename = null)
    {
        if ($filename === null) $filename = $this->get_items_filename($tag);
        if (empty($items) && file_exists($filename)) {
            return unlink($filename);
        }
        $contents = implode("\n", $items) . "\n";
        return $this->file_put_contents($filename, $contents);
    }

    /**
     * Set tagcloud into a storage (file or db)
     *
     * Overwrite Me if you don't like the file format
     *
     * @param array $tagcloud
     * @param string filename
     * @return boolean
     */
    function set_tagcloud_into_storage($tagcloud, $filename = null)
    {
        if ($filename === null) $filename = $this->get_tagcloud_filename();
        $contents = '';
        ksort($tagcloud);
        foreach ($tagcloud as $tag => $count) {
            if ($count === 0) continue;
            $contents .= $tag . "\t" . $count . "\n";
        }
        return $this->file_put_contents($filename, $contents);
    }

    /**
     * Get tags from an item
     *
     * @param string $item
     * @param boolean $cache use memory cache
     * @return array
     */
    function get_tags($item, $cache = true)
    {
        if (isset($this->tags[$item]) & $cache) {
            return $this->tags[$item];
        }
        $tags = $this->get_tags_from_storage($item);
        if ($cache) $this->tags[$item] = $tags;
        return $tags;
    }

    /**
     * Get items tagged by a tag
     *
     * @param string $tag
     * @param boolean $cache use memory cache
     * @return array
     */
    function get_items($tag, $cache = true)
    {
        if (isset($this->items[$tag]) & $cache) {
            return $this->items[$tag];
        }
        $items = $this->get_items_from_storage($tag);
        if ($cache) $this->items[$tag] = $items;
        return $items;
    }

    /**
     * Set tags into an item
     *
     * @param string $item
     * @param array  $tags
     * @param boolean $storage write to storage
     * @return boolean
     */
    function set_tags($item, $tags, $storage = true)
    {
        $tags = $this->normalize_tags($tags);
        // $tags == $this->tags[$item] // key/val pair
        list($minus, $plus) = $this->ya_array_diff($this->get_tags($item), $tags);
        if (empty($minus) & empty($plus)) {
            return true;
        }
        $this->tags[$item] = $tags;
        if ($storage) {
            return $this->set_tags_into_storage($item, $tags);
        }
        return true;
    }

    /**
     * Set items to have a tag
     *
     * @param string $tag
     * @param array  $items
     * @param boolean $storage write to storage
     * @return boolean
     */
    function set_items($tag, $items, $storage = true)
    {
        // $items == $this->items[$tag] // key/val pair
        list($minus, $plus) = $this->ya_array_diff($this->get_items($tag), $items);
        if (empty($minus) & empty($plus)) {
            return true;
        }
        $this->items[$tag] = $items;
        if ($storage) {
            return $this->set_items_into_storage($tag, $items);
        }
        return true;
    }

    /**
     * Save tags into an item
     *
     * @param string $item
     * @param string or array $tags
     * @return boolean Success or Failure
     */
    function save_tags($item, $new_tags)
    {
        $new_tags = (array)$new_tags;
        $old_tags = $this->get_tags($item);
        $new_tags = $this->normalize_tags($new_tags);
        $ret = true;
        $ret &= $this->set_tags($item, $new_tags);
        list($minus, $plus) = $this->ya_array_diff($old_tags, $new_tags);
        foreach ($minus as $tag) {
            $old_items = $this->get_items($tag);
            $new_items = array_diff($old_items, (array)$item);
            $ret &= $this->set_items($tag, $new_items);
        }
        foreach ($plus as $tag) {
            $old_items = $this->get_items($tag);
            $new_items = array_unique(array_merge($old_items, (array)$item));
            $ret &= $this->set_items($tag, $new_items);
        }
        $ret &= $this->update_tagcloud();
        return $ret;
    }
    /**
     * Add tags into an item
     *
     * @param string $item
     * @param string or array $tags
     * @return boolean Success or Failure
     */
    function add_tags($item, $tags) 
    {
        $tags = (array)$tags;
        $tags = $this->normalize_tags($tags);
        $new_tags = array_unique(array_merge($this->get_tags($item), $tags));
        // return $this->save_tags($item, $new_tags);
        $ret = true;
        $ret &= $this->set_tags($item, $new_tags);
        foreach ($tags as $tag) {
            $old_items = $this->get_items($tag);
            $new_items = array_unique(array_merge($old_items, (array)$item));
            $ret &= $this->set_items($tag, $new_items);
        }
        $ret &= $this->update_tagcloud();
        return $ret;
    }
    /**
     * Remove tags from an item
     *
     * @param string $item
     * @param string or array $tags
     * @return boolean Success or Failure
     */
    function remove_tags($item, $tags)
    {
        $tags = (array)$tags;
        $tags = $this->normalize_tags($tags);
        $new_tags = array_diff($this->get_tags($item), $tags);
        // return $this->save_tags($item, $new_tags);
        $ret = true;
        $ret &= $this->set_tags($item, $new_tags);
        foreach ($tags as $tag) {
            $old_items = $this->get_items($tag);
            $new_items = array_diff($old_items, (array)$item);
            $ret &= $this->set_items($tag, $new_items);
        }
        $ret &= $this->update_tagcloud();
        return $ret;
    }
    /**
     * Remove tags from the system
     *
     * @uses Tag::remove_tags()
     *
     * @param string $item
     * @param string or array $tags
     * @return boolean Success or Failure
     */
    function remove_system_tags($tags)
    {
        $tags = (array)$tags;
        $tags = $this->normalize_tags($tags);
        $ret = true;
        foreach ($tags as $tag) {
            foreach ($this->get_items($tag) as $item) {
                $ret &= $this->remove_tags($item, $tag);
            }
            $ret &= $this->set_items($tag, array());
        }
        $ret &= $this->update_tagcloud();
        return $ret;
    }
    /**
     * Edit tag property (change tag name)
     *
     * @param string $old_tag
     * @param string $new_tag
     * @return boolean Success or Failure
     */
    function edit_tag($old_tag, $new_tag)
    {
        $old_tag = $this->normalize_tags($old_tag);
        $new_tag = $this->normalize_tags($new_tag);
        $items = $this->get_items($old_tag);
        if (empty($items)) return false;
        $ret = true;
        $ret &= $this->set_items($old_tag, array());
        $ret &= $this->set_items($new_tag, $items);
        foreach ($items as $item) {
            $ret &= $this->remove_tags($item, $old_tag);
            $ret &= $this->add_tags($item, $new_tag);
        }
        $ret &= $this->update_tagcloud();
        return $ret;
    }

    /**
     * Check if a tag exists in a certain item
     *
     * @param string $tag
     * @return boolean
     */
    function has_tag($item, $tag)
    {
        return in_array($tag, $this->get_tags($item));
    }
    /**
     * Check if a tag exists in the system
     *
     * @param string $tag
     * @return boolean
     */
    function has_system_tag($tag)
    {
        $items = $this->get_items($tag);
        return ! empty($items);
    }
    /**
     * Normalize tags
     *
     * @param string or array $tags
     * @return string or array normalized tags
     */
    function normalize_tags($tags)
    {
        $isarray = is_array($tags);
        $tags = (array)$tags;
        // if (extension_loaded('mbstring'))
        //if (function_exists('mb_strtolower')) {
        //foreach ($tags as $i => $tag) {
        //$tags[$i] = mb_strtolower($tag, SOURCE_ENCODING);
        //}
        //}

        // Reserved keys can not be used for tag strings
        foreach ($tags as $i => $tag) {
            $tags[$i] = str_replace($this->reserved_keys, '', $tag);
        }
        $tags = array_unique($tags);
        if ($isarray) return $tags;
        else return $tags[0];
    }

    /**
     * Get tagcloud
     *
     * @param integer $limit
     * @param string $relate_tag
     * @param boolean $cache use memory cache
     * @return array
     */
    function get_tagcloud($limit = null, $relate_tag = null, $cache = true)
    {
        if (isset($this->tagcloud) & $cache) {
            $tagcloud = $this->tagcloud;
        } else {
            $tagcloud = $this->get_tagcloud_from_storage();
        }
        if (isset($relate_tag)) {
            $related_tags = $this->get_related_tags($relate_tag);
            $r_tagcloud = array();
            foreach ($related_tags as $tag) {
                $r_tagcloud[$tag] = $tagcloud[$tag];
            }
            $tagcloud = $r_tagcloud;
        }
        if (isset($limit)) {
            arsort($tagcloud);
            $tagcloud = array_slice($tagcloud, 0, $limit);
            ksort($tagcloud);
        }
        return $tagcloud;
    }

    /**
     * Update tagcloud
     *
     * @param boolean $cache use memory cache
     * @return boolean
     */
    function update_tagcloud($cache = true)
    {
        if (isset($this->tagcloud) & $cache) {
            $tagcloud = $this->tagcloud;
        } else {
            $tagcloud = $this->get_tagcloud();
        }
        $ret = true;
        if (! empty($this->items)) { // update
            foreach ($this->items as $tag => $items) {
                $count = count($items);
                if ($count === 0) {
                    unset($tagcloud[$tag]);
                } else {
                    $tagcloud[$tag] = $count;
                }
            }
            ksort($tagcloud);
            $ret &= $this->set_tagcloud_into_storage($tagcloud);
        }
        if ($cache) $this->tagcloud = $tagcloud;
        return $ret;
    }

    /**
     * Get related tags
     *
     * @param string $tag
     * @return array
     */
    function get_related_tags($tag = null)
    {
        if ($tag === null) return false;
        $items = $this->get_items($tag);
        $tags = array();
        foreach ($items as $item) {
            $tags = array_merge($tags, (array)$this->get_tags($item));
        }
        $tags = array_unique($tags);
        return $tags;
    }

    /**
     * Get items from tag token
     *
     * TagA^TagB => intersection
     * TagA-TagB => subtraction
     *
     * @param string $tagtok
     * @return array items
     */
    function get_items_by_tagtok($tagtok)
    {
        // token analysis
        $tags = array();
        $operands = array();
        $tokpos = -1;

        $token  = implode('', $this->reserved_keys);
        $substr = strtok($tagtok, $token);
        array_push($tags, $substr);
        $tokpos = $tokpos + strlen($substr) + 1;
        $substr = strtok($token);
        while ($substr !== false) {
            switch ($tagtok[$tokpos]) {
            case $this->reserved_keys['diff']:
                array_push($operands, $this->reserved_keys['diff']);
                break;
            case $this->reserved_keys['prod']:
            default:
                array_push($operands, $this->reserved_keys['prod']);
                break;
            }
            array_push($tags, $substr);
            $tokpos = $tokpos + strlen($substr) + 1;
            $substr = strtok($token);
        }

        // narrow items
        $items = $this->get_items(array_shift($tags));
        foreach ($tags as $i => $tag) {
            switch ($operands[$i]) {
            case $this->reserved_keys['diff']:
                $items = array_diff($items, $this->get_items($tag));
                break;
            case $this->reserved_keys['prod']:
            default:
                $items = array_intersect($items, $this->get_items($tag));
                break;
            }
        }
        return $items;
    }

    /**
     * Display tags of an item
     *
     * Overwrite Me!
     *
     * @param string $item
     */
    function display_tags($item)
    {
        print '[Item:' . $item . ']';
        print_r($this->get_tags($item));
    }

    /**
     * Display items tagged by a tag
     *
     * Overwrite Me!
     *
     * @param string $tag
     */
    function display_items($tag)
    {
        print '[Tag:' . $tag . ']';
        print_r($this->get_items($tag));
    }

    /**
     * Display tagcloud
     *
     * Overwrite Me!
     *
     * @param integer $limit
     * @param string $relate_tag
     */
    function display_tagcloud($limit = null, $relate_tag = null)
    {
        $tagcloud = $this->get_tagcloud($limit, $relate_tag);
        print '[Tabcloud]';
        print_r($tagcloud);
    }

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
            if($file != '.' && $file != '..' && is_dir($dir . $file)) {
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
    
    /**
     * Find positions of occurrence of a string
     *
     * PHP Extension
     *
     * @param string $str
     * @param string $substr
     * @return array of integers
     */
    function r_strpos($str, $substr)
    {
        $r_pos = array();
        while(true) {
            $pos = strpos($str, $substr);
            if ($pos === false) break;
            array_push($r_pos, $pos);
            $str = substr($str, $pos + 1);
        }
        return $r_pos;
    }

    /**
     * Computes the difference of arrays
     * and returns removed elements and added elements
     *
     * PHP Extension
     *
     * @param string $oldarray
     * @param string $newarray
     * @return array of (array)minus and (array)plus
     */
    function ya_array_diff($oldarray, $newarray)
    {
        $common = array_intersect($oldarray, $newarray);
        $minus  = array_diff($oldarray, $common);
        $plus   = array_diff($newarray, $common);
        return array($minus, $plus);
    }

    /**
     * Write a string to a file
     *
     * PHP5.0 has file_put_contents($file, $contents),  though
     *
     * @param string $file filename
     * @param string $contents
     * @return boolean
     */
    function file_put_contents($file, &$contents)
    {
        if (($fp = fopen($file, "w")) === FALSE) {
            return FALSE;
        }
        if (fwrite($fp, $contents) === FALSE) {
            fclose($fp);
            return FALSE;
        } 
        return fclose($fp);
    }
}
/*
$tag = new Tag();
$tag->save_tags('Hoge', 'PukiWiki');
$tag->save_tags('Joge', array('PukiWiki', 'Plugin', 'Plugin'));
$tag->save_tags('Moge', array('Plugin', 'Moge'));
$tag->save_tags('Koge', array('Koge'));
$tag->display_tags('Hoge');
$tag->display_tags('Joge');
$tag->display_tags('Moge');
$tag->display_tags('Koge');
$tag->display_items('PukiWiki');
$tag->display_items('Plugin');
$tag->display_tagcloud();
$tag->display_tagcloud(null, 'Plugin');
*/

////////////////////////////////
/**
 * Generate An HTML Tag Cloud
 * @author astronote <http://astronote.jp/>
 */
class TagCloud
{
    var $counts;
    var $urls;
    
    function TagCloud()
    {
        $this->counts = array();
        $this->urls = array();
    }

    function add($tag, $url, $count)
    {
        $this->counts[$tag] = $count;
        $this->urls[$tag] = $url;
    }

    function css()
    {
        $css = '#htmltagcloud { text-align: center; line-height: 16px; }';
        for ($level = 0; $level <= 24; $level++) {
            $font = 12 + $level;
            $css .= "span.tagcloud$level { font-size: ${font}px;}\n";
            $css .= "span.tagcloud$level a {text-decoration: none;}\n";
        }
        return $css;
    }

    function html($limit = NULL)
    {
        $a = $this->counts;
        arsort($a);
        $tags = array_keys($a);
        if (isset($limit)) {
            $tags = array_slice($tags, 0, $limit);
        }
        $n = count($tags);
        if ($n == 0) {
            return '';
        } elseif ($n == 1) {
            $tag = $tags[0];
            $url = $this->urls[$tag];
            return "<div class=\"htmltagcloud\"><span class=\"tagcloud1\"><a href=\"$url\">$tag</a></span></div>\n"; 
        }
        
        $min = sqrt($this->counts[$tags[$n - 1]]);
        $max = sqrt($this->counts[$tags[0]]);
        $factor = 0;
        
        // specal case all tags having the same count
        if (($max - $min) == 0) {
            $min -= 24;
            $factor = 1;
        } else {
            $factor = 24 / ($max - $min);
        }
        $html = '';
        sort($tags);
        foreach($tags as $tag) {
            $count = $this->counts[$tag];
            $url   = $this->urls[$tag];
            $level = (int)((sqrt($count) - $min) * $factor);
            $html .=  "<span class=\"tagcloud$level\"><a href=\"$url\">$tag</a></span>\n"; 
        }
        $html = "<div class=\"htmltagcloud\">$html</div>";
        return $html;
    }

    function htmlAndCSS($limit = NULL)
    {
        $html = "<style type=\"text/css\">\n" . $this->css() . "</style>" 
            . $this->html($limit);
        return $html;
    }
}
/* test
$tags = array(
    array('tag' => 'blog', 'count' => 20),
    array('tag' => 'ajax', 'count' => 10),
    array('tag' => 'mysql', 'count'  => 5),
    array('tag' => 'hatena', 'count'  => 12),
    array('tag' => 'bookmark', 'count'  => 30),
    array('tag' => 'rss', 'count' => 1),
    array('tag' => 'atom', 'count' => 2),
    array('tag' => 'misc', 'count' => 10),
    array('tag' => 'javascript', 'count' => 11),
    array('tag' => 'xml', 'count' => 6),
    array('tag' => 'perl', 'count' => 32),
);

$cloud = new TagCloud();
foreach ($tags as $t) {
    $cloud->add($t['tag'], "http://<your.domain>/{$t['tag']}/", $t['count']);
}
print "<html><body>";
print $cloud->htmlAndCSS(20);
print "</body></html>";
*/
?>
