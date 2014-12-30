<?php
/**
 * Tag (General) and TagCloud (General) and PukiWiki Tag class
 *
 * @package    PluginSonots
 * @license    http://www.gnu.org/licenses/gpl.html GPL v2
 * @author     sonots <http://lsx.sourceforge.jp>
 * @version    $Id: tag.class.php,v 1.0 2008-06-05 07:23:17Z sonots $
 */

/**
 *  PukiWiki Tag Class
 *
 *  @package    PluginSonots
 *  @license    http://www.gnu.org/licenses/gpl.html GPL v2
 *  @author     sonots <http://lsx.sourceforge.jp>
 *  @version    $Id: v 1.0 2008-06-05 07:23:17Z sonots $
 */
class PluginSonotsTag extends Tag
{
    function PluginSonotsTag()
    {
        parent::Tag();
    }

     /**
     * Get an action plugin uri for tag listing
     *
     * @access public
     * @param string $tagtok
     * @return string uri
     */
    function get_tag_uri($tagtok)
    {
        return get_script_uri() . '?cmd=taglist&amp;tag=' . rawurlencode($tagtok);
    }

    /**
     * Get the tag link
     *
     * @access public
     * @param string $tagtok
     * @return string link html
     */
    function make_taglink($tagtok)
    {
        $href = $this->get_tag_uri($tagtok);
        return '<a href="' . $href . '">' . htmlspecialchars($tagtok) . '</a> ';
    }

    function get_items_filename($tag)
    {
        $tag = $this->normalize_tags($tag);
        return CACHE_DIR . encode($tag) . '_tag.tag';
    }
    function get_items_filenames()
    {
        return get_existfiles(CACHE_DIR, '_tag.tag');
    }
    function get_tags_filename($page)
    {
        return CACHE_DIR . encode($page) . '_page.tag';
    }
    function get_tags_filenames()
    {
        return get_existfiles(CACHE_DIR, '_page.tag');
    }
    function get_tagcloud_filename()
    {
        return CACHE_DIR . 'tagcloud.tag';
    }

    /**
     * Get tagged pages
     *
     * Syntax Sugar for get_items_by_tagtok
     *
     * @access public
     * @param string $tagtok
     * @return array
     * @uses get_items_by_tagtok
     */
    function get_taggedpages($tagtok)
    {
        return $this->get_items_by_tagtok($tagtok);
    }

   ////////////// display ///////////////////
    /**
     * Display tags of an item
     *
     * @access public
     * @param string $item
     * @return string HTML
     */
    function display_tags($item)
    {
        $tags = $this->get_tags($item);
        $ret = '<span class="tag">';
        $ret .= 'Tag: ';
        foreach ($tags as $tag) {
            $ret .= $this->make_taglink($tag);
        }
        $ret .= '</span>';
        return $ret;
    }

    /**
     * Display tags list
     *
     * @access public
     * @param integer $limit Number to show
     * @param string $relate_tag Show only related tags of this
     * @param string $cssclass
     * @return string HTML
     */
    function display_taglist($limit = null, $relate_tag = null, $cssclass = 'taglist tags')
    {
        $html = '<ul class="' . $cssclass . '">';
        $tagcloud = $this->get_tagcloud($limit, $relate_tag);
        foreach ($tagcloud as $tag => $count) {
            $html .= '<li>' . $this->make_taglink($tag) . '</li>';
        }
        $html .= '</ul>';
        return $html;
    }

    /**
     * Display tagcloud
     *
     * @access public
     * @param integer $limit Number to show
     * @param string $relate_tag Show only related tags of this
     * @param string $basehref base href
     * @return string HTML
     */
    function display_tagcloud($limit = null, $relate_tag = null)
    {
        $view = new TagCloud();
        $tagcloud = $this->get_tagcloud($limit, $relate_tag);
        foreach ($tagcloud as $tag => $count) {
            $url = $this->get_tag_uri($tag);
            $view->add(htmlspecialchars($tag), $url, $count);
        }
        return $view->html();
    }
}

/**
 *  Tag Class
 *
 *  When you to use, extend this class to overwrite file 
 *  configuration functions that determine storage filenames 
 *  and file format, or you can even overwrite to use DBs. 
 *  
 *  Tag class reads data from storage dynamically when it is required, 
 *  thus, users of this class can work as if a tag class object
 *  already has all data
 *
 *  Example)
 *  <code>
 *  $tag = new Tag();
 *  $tag->save_tags('Hoge', 'PukiWiki');
 *  $tag->save_tags('Joge', array('PukiWiki', 'Plugin', 'Plugin'));
 *  $tag->save_tags('Moge', array('Plugin', 'Moge'));
 *  $tag->save_tags('Koge', array('Koge'));
 *  $tag->display_tags('Hoge');
 *  $tag->display_tags('Joge');
 *  $tag->display_tags('Moge');
 *  $tag->display_tags('Koge');
 *  $tag->display_items('PukiWiki');
 *  $tag->display_items('Plugin');
 *  $tag->display_tagcloud();
 *  $tag->display_tagcloud(null, 'Plugin');
 *  $tag = new Tag();
 *  $tag->display_tagcloud(null, 'Plugin'); // get from storage
 *  </code>
 *
 *  Note:
 *  All functions should not be accessed statically because
 *  this class will be extended into a child class 
 *  to overwrite filename configuration functions. 
 *  The overwritten functions are accessible via $this->,
 *  but not via Tag::(statically). 
 *  (Tag:: calls functions in Tag class not in the child.)
 *
 *  @package    PluginSonots
 *  @license    http://www.gnu.org/licenses/gpl.html GPL v2
 *  @author     sonots <http://lsx.sourceforge.jp>
 *  @version    $Id: v 1.0 2008-06-05 07:23:17Z sonots $
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
    var $items = array();
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
    var $tags = array();
    /**
     * Tagcloud
     *
     * Associative array whose key is a tag
     * and values are the number of items
     *
     * @var array
     */
    var $tagcloud = array();
    /**
     * Reserved keys
     *
     * Strings which have special meanings.
     * They can not be used for tag strings
     *
     * @var array
     * @static
     */
    var $reserved_keys;

    function Tag()
    {
        // php4 static variable trick
        static $reserved_keys = array('prod' => '^', 'diff' => '-');
        $this->reserved_keys = &$reserved_keys;
    }

    /**
     * Get tags of an item
     *
     * @param string $item
     * @param boolean $cache use memory cache
     * @return array
     * @uses get_tags_from_storage
     * @access public
     */
    function get_tags($item, $cache = true)
    {
        if (! empty($this->tags[$item]) && $cache) {
            return $this->tags[$item];
        }
        $tags = $this->get_tags_from_storage($item);
        if ($cache) $this->tags[$item] = $tags;
        return $tags;
    }

    /**
     * Get items by a tag
     *
     * @param string $tag
     * @param boolean $cache use memory cache
     * @return array
     * @uses get_items_from_storage
     * @access public
     */
    function get_items($tag, $cache = true)
    {
        if (isset($this->items[$tag]) && $cache) {
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
     * @uses normalize_tags
     * @uses set_tags_into_storage
     * @access public
     */
    function set_tags($item, $tags, $storage = true)
    {
        $tags = $this->normalize_tags($tags);
        if (count($tags) == count(array_intersect($tags, $this->get_tags($item)))) {
            return true;
        }
        $this->tags[$item] = $tags;
        if ($storage) {
            return $this->set_tags_into_storage($item, $tags);
        }
        return true;
    }

    /**
     * Set items into a tag
     *
     * @param string $tag
     * @param array  $items
     * @param boolean $storage write to storage
     * @return boolean
     * @uses set_items_into_storage
     * @access public
     */
    function set_items($tag, $items, $storage = true)
    {
        if (count($items) == array_intersect($items, $this->get_items($tag))) {
            return true;
        }
        $this->items[$tag] = $items;
        if ($storage) {
            return $this->set_items_into_storage($tag, $items);
        }
        return true;
    }

    /**
     * Save tags into an item (This does more than set_tags)
     *
     * @param string $item
     * @param mixed $tags string or array of tag(s)
     * @return boolean Success or Failure
     * @uses normalize_tags
     * @uses get_tags
     * @uses set_tags
     * @uses get_items
     * @uses set_items
     * @uses update_tagcloud
     * @access public
     */
    function save_tags($item, $new_tags)
    {
        $new_tags = (array)$new_tags;
        $old_tags = $this->get_tags($item);
        $new_tags = $this->normalize_tags($new_tags);
        $ret = true;
        $ret &= $this->set_tags($item, $new_tags);
        $common_tags = array_intersect($old_tags, $new_tags);
        $minus = array_diff($old_tags, $common_tags);
        $plus  = array_diff($new_tags, $common_tags);
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
     * @param mixed $tags string or array of tag(s)
     * @return boolean Success or Failure
     * @uses normalize_tags
     * @uses get_tags
     * @uses set_tags
     * @uses get_items
     * @uses set_items
     * @uses update_tagcloud
     * @access public
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
     * @param mixed $tags string or array of tag(s)
     * @return boolean Success or Failure
     * @uses normalize_tags
     * @uses get_tags
     * @uses set_tags
     * @uses get_items
     * @uses set_items
     * @uses update_tagcloud
     * @access public
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
     * Remove tags from the system (in all items)
     *
     * @param mixed $tags string or array of tag(s)
     * @return boolean Success or Failure
     * @uses normalize_tags
     * @uses get_items
     * @uses remove_tags
     * @uses set_items
     * @uses update_tagcloud
     * @access public
     */
    function remove_tags_in_system($tags)
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
     * Rename a tag
     *
     * @param string $old_tag
     * @param string $new_tag
     * @return boolean Success or Failure
     * @uses normalize_tags
     * @uses get_items
     * @uses set_items
     * @uses remove_tags
     * @uses add_tags
     * @access public
     */
    function rename_tag($old_tag, $new_tag)
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
        // $ret &= $this->update_tagcloud();
        return $ret;
    }

    /**
     * Rename an item
     *
     * @param string $old_item
     * @param string $new_item
     * @return boolean Success or Failure
     * @uses get_tags
     * @uses save_tags
     * @access public
     */
    function rename_item($old_item, $new_item)
    {
        $tags = $this->get_tags($old_item);
        if (empty($tags)) return false;
        $ret = true;
        $ret &= $this->save_tags($old_item, array());
        $ret &= $this->save_tags($new_item, $tags);
        // $ret &= $this->update_tagcloud();
        return $ret;
    }

    /**
     * Check if a tag exists in a certain item
     *
     * @param string $tag
     * @return boolean
     * @access public
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
     * @access public
     */
    function has_tag_in_system($tag)
    {
        $items = $this->get_items($tag);
        return ! empty($items);
    }

    /**
     * Check if a tag exists in the system
     *
     * Syntax Sugar for has_tag_in_system
     *
     * @param string $tag
     * @return boolean
     * @access public
     * @uses has_tag_in_system
     */
    function tag_exists($tag)
    {
        return $this->has_tag_in_system($tag);
    }

    /**
     * Get all existing tags in the system
     *
     * @param boolean $cache use memory cache
     * @return array
     * @access public
     * @uses get_tagcloud
     */
    function get_tags_in_system($cache = true)
    {
        return array_keys($this->get_tagcloud(null, null, $cache));
    }

    /**
     * Get all existing tags in the sytem
     *
     * Syntax Sugar for get_tags_in_system
     *
     * @param boolean $cache use memory cache
     * @return array
     * @access public
     * @uses get_tags_in_system
     */
    function get_existtags($cache = true)
    {
        return $this->get_tags_in_system($cache);
    }

    /**
     * Get tagcloud
     *
     * @param integer $limit
     * @param string $relate_tag
     * @param boolean $cache use memory cache
     * @return array
     * @access public
     */
    function get_tagcloud($limit = null, $relate_tag = null, $cache = true)
    {
        if (! empty($this->tagcloud) && $cache) {
            $tagcloud = $this->tagcloud;
        } else {
            $tagcloud = $this->get_tagcloud_from_storage();
        }
        if (isset($relate_tag)) { // reduce
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
     * Excute after set_tags and set_items
     *
     * @param boolean $cache use memory cache
     * @return boolean
     * @access public
     */
    function update_tagcloud($cache = true)
    {
        if (! empty($this->tagcloud) && $cache) {
            $tagcloud = $this->tagcloud; // read cache
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
     * @access public
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
     * Get items by a tag token
     *
     * TagA^TagB => intersection
     * TagA-TagB => subtraction
     *
     * @param string $tagtok
     * @return array items
     * @access static
     */
    function get_items_by_tagtok($tagtok)
    {
        // token analysis
        $tags = array();
        $operands = array();
        $tokpos = -1;

        $reserved_keys = $this->reserved_keys;
        $token  = implode('', $reserved_keys);
        $substr = strtok($tagtok, $token);
        array_push($tags, $substr);
        $tokpos = $tokpos + strlen($substr) + 1;
        $substr = strtok($token);
        while ($substr !== false) {
            switch ($tagtok[$tokpos]) {
            case $reserved_keys['diff']:
                array_push($operands, $reserved_keys['diff']);
                break;
            case $reserved_keys['prod']:
            default:
                array_push($operands, $reserved_keys['prod']);
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
            case $reserved_keys['diff']:
                $items = array_diff($items, $this->get_items($tag));
                break;
            case $reserved_keys['prod']:
            default:
                $items = array_intersect($items, $this->get_items($tag));
                break;
            }
        }
        return $items;
    }

    /**
     * Normalize tags
     *
     * @param string or array $tags
     * @return string or array normalized tags
     * @access static
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
    ///////////// Overwrite Me! functions //////////////
    /**
     * Display tags of an item
     *
     * Overwrite Me!
     *
     * @param string $item
     * @access public
     */
    function display_tags($item)
    {
        $tags = $this->get_tags($item);
        print_r($tags);
    }

    /**
     * Display items having a tag
     *
     * Overwrite Me!
     *
     * @param array $items
     * @access public
     */
    function display_items($tag)
    {
        $items = $this->get_items($tag);
        print_r($items);
    }

    /**
     * Display tagcloud
     *
     * Overwrite Me!
     *
     * @param int $limit number of tags to be shown
     * @param string $relate_tag show only related tags to this
     * @access static
     */
    function display_tagcloud($limit = null, $relate_tag = null)
    {
        $tagcloud = $this->get_tagcloud($limit, $relate_tag);
        print '[Tabcloud]';
        print_r($tagcloud);
    }

    /**
     * Get filename which stores items by a tag
     *
     * Overwrite Me! to define the filename
     *
     * @param string $tag
     * @return string
     * @access protected
     */
    function get_items_filename($tag)
    {
        $tag = $this->normalize_tags($tag);
        return str_replace('%', '', rawurlencode($tag)) . '_items.tag';
    }
    /**
     * Get all items_filename
     *
     * Overwrite Me! if you changed get_items_filename
     *
     * @return array
     * @access protected
     */
    function get_items_filenames()
    {
        return get_existfiles('.', '_items.tag');
    }
    /**
     * Get filename which stores tags of an item
     *
     * Overwrite Me! to define the filename
     *
     * @param string $item
     * @return string
     * @access protected
     */
    function get_tags_filename($item)
    {
        return str_replace('%', '', rawurlencode($item)) . '_tags.tag';
    }
    /**
     * Get all tags_filename
     *
     * Overwrite Me! if you changed get_tags_filename
     *
     * @return array
     * @access protected
     */
    function get_tags_filenames()
    {
        return get_existfiles('.', '_tags.tag');
    }
    /**
     * Get filename which stores tagcloud
     *
     * Overwrite Me! to define the filename
     *
     * @return string
     * @access protected
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
     * @access protected
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
     * Get items by a tag from a storage (file or db)
     *
     * Overwrite Me if you don't like the file format
     *
     * @param string $tag
     * @param string $filename
     * @return array
     * @access protected
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
     * @access protected
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
     * Set tags into an item, and store into a storage (file or db)
     *
     * Overwrite Me if you don't like the file format
     *
     * @param string $item
     * @param array  $tags
     * @param string $filename
     * @return boolean
     * @access protected
     */
    function set_tags_into_storage($item, $tags, $filename = null)
    {
        if ($filename === null) $filename = $this->get_tags_filename($item);
        if (empty($tags) && file_exists($filename)) {
            return unlink($filename);
        }
        $contents = implode("\n", $tags) . "\n";
        return file_put_contents($filename, $contents);
    }

    /**
     * Set items into a tag, and store into a storage (file or db)
     *
     * Overwrite Me if you don't like the file format
     *
     * @param string $tag
     * @param array  $items
     * @param string $filename
     * @return boolean
     * @access protected
     */
    function set_items_into_storage($tag, $items, $filename = null)
    {
        if ($filename === null) $filename = $this->get_items_filename($tag);
        if (empty($items) && file_exists($filename)) {
            return unlink($filename);
        }
        $contents = implode("\n", $items) . "\n";
        return file_put_contents($filename, $contents);
    }

    /**
     * Store tagcloud into a storage (file or db)
     *
     * Overwrite Me if you don't like the file format
     *
     * @param array $tagcloud
     * @param string filename
     * @return boolean
     * @access protected
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
        return file_put_contents($filename, $contents);
    }

}

/**
 * Generate An HTML Tag Cloud
 *
 * Example
 * <code>
 * $tags = array(
 *     array('tag' => 'blog', 'count' => 20),
 *     array('tag' => 'ajax', 'count' => 10),
 *     array('tag' => 'mysql', 'count'  => 5),
 *     array('tag' => 'hatena', 'count'  => 12),
 *     array('tag' => 'bookmark', 'count'  => 30),
 *     array('tag' => 'rss', 'count' => 1),
 *     array('tag' => 'atom', 'count' => 2),
 *     array('tag' => 'misc', 'count' => 10),
 *     array('tag' => 'javascript', 'count' => 11),
 *     array('tag' => 'xml', 'count' => 6),
 *     array('tag' => 'perl', 'count' => 32),
 * );
 * $cloud = new TagCloud();
 * foreach ($tags as $t) {
 *     $cloud->add($t['tag'], "http://<your.domain>/{$t['tag']}/", $t['count']);
 * }
 * print "<html><body>";
 * print $cloud->htmlAndCSS(20);
 * print "</body></html>";
 * </code>
 *
 * @package    PluginSonots
 * @license    http://www.gnu.org/licenses/gpl.html GPL v2
 * @author     astronote <http://astronote.jp/>
 * @author     sonots <http://lsx.sourceforge.jp>
 * @version    $Id: v 1.0 2008-06-05 07:23:17Z sonots $
 */
class TagCloud
{
    /**
     * Counts of tags
     *
     * Associative array of integers whose key is a tag and value
     * is its count (number of items associated with the tag)
     *
     * @var array
     *
     * [tag] = count
     */
    var $counts;
    /**
     * Urls of tags
     *
     * Associative array of strings whose key is a tag
     * and value is its link to be displayed in tagcloud
     *
     * @var array
     *
     * [tag] = url
     */
    var $urls;
    
    function TagCloud()
    {
        $this->counts = array();
        $this->urls = array();
    }

    /**
     * Add a tag
     *
     * @param $tag tag
     * @param $url associated url to be displayed in tagcloud
     * @param $count number of items associated with tag.
     * @return void
     * @access public
     */
    function add($tag, $url, $count)
    {
        $this->counts[$tag] = $count;
        $this->urls[$tag] = $url;
    }

    /**
     * Generate embedded CSS HTML
     *
     * You may create a .css file instead of using this function everytime
     *
     * @return string CSS
     */
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

    /**
     * Generate tagcloud HTML
     *
     * @param $limit number of limits to be displayed
     * @return string HTML
     * @access public
     */
    function html($limit = null)
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

    /**
     * Generate tagcloud HTML and embedded CSS HTML concurrently
     *
     * @param $limit number of limits to be displayed in tagcloud
     * @return string HTML
     * @access public
     */
    function htmlAndCSS($limit = null)
    {
        $html = "<style type=\"text/css\">\n" . $this->css() . "</style>" 
            . $this->html($limit);
        return $html;
    }
}

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
     * @version $Id: v 1.0 2008-06-05 11:14:46 sonots $
     */
    function &get_existfiles($dir, $ext = '', $recursive = false) 
        {
            if (($dp = @opendir($dir)) == false)
                return false;
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

if (! function_exists('file_put_contents')) {
    /**
     * Write a string to a file (PHP5 has this function)
     *
     * PHP Compat
     *
     * @param string $filename
     * @param string $data
     * @param int $flags
     * @return int the amount of bytes that were written to the file, or false if failure
     * @version $Id: v 1.0 2008-06-05 11:14:46 sonots $
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
