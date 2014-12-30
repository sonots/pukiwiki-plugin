<?php
/**
 *  Modify Links in the PukiWiki Output HTML
 *
 *  @author     sonots
 *  @license    http://www.gnu.org/licenses/gpl.html    GPL2
 *  @link       http://lsx.sourceforge.jp/?Plugin%2Fdump2html
 */
class PKWKLinkModifier
{
    /**
     * Configuration or option
     *
     * string $CONF['TOPURL'] PukiWiki Top URL<br />
     * string $CONF['DUMPDIR']: Directory where dumped html exists<br />
     * string $CONF['POSTFIX'] POSTFIX of files (usually .html)<br />
     * enum $CONF['href_urlstyle'] Use 'relative' or 'absolute' url on modifying urls<br />
     * enum $CONF['linkrel_urlstyle'] Use 'relative' or 'absolute' url on modifying urls<br />
     * boolean $CONF['modify_href'] Modify href links<br />
     * boolean $CONF['modify_linkrel'] Modify linkrel (css, javascript, img) links<br />
     * array $CONF['encode'] Encoding function<br />
     *
     * @var array
     */
    var $CONF;

    /**
     * Constructor
     */
    function PKWKLinkModifier()
    {
        $this->CONF = array(
            'TOPURL'           => '',
            'DUMPDIR'          => '',
            'POSTFIX'          => '',
            'href_urlstyle'    => 'relative', // 'relative' or 'asolute'
            'linkrel_urlstyle' => 'relative', // 'relative' or 'asolute'
            'modify_href'      => TRUE,
            'modify_linkrel'   => TRUE,
            'encode'           => array(), // array($object, $funcname)
        );
    }

    /**
     * Modify Links (main)
     *
     * @param string $contents contents to be modified
     * @param string $source source path
     * @return string modified contents
     */
    function &format($contents, $source)
    {
        if ($this->CONF['modify_href']) {
            $contents = $this->modify_pkwk_href($contents, $source);
        }
        if ($this->CONF['modify_linkrel']) {
            $contents = $this->modify_linkrel($contents, $source);
        }
        return $contents;
    }

    /**
     * Modify link rel, javascript, css links
     *
     * Currently only relative paths
     * ToDo: clean
     *
     * @param string &$contents contents to be modified
     * @param string &$source source path
     * @return string modified contents
     */
    function &modify_linkrel(&$contents, &$source)
    {
        $csss     = $this->get_css_links($contents);
        $scripts  = $this->get_javascript_links($contents);
        $imgs     = $this->get_img_links($contents);
        $searches = array_merge($csss[0], $scripts[0], $imgs[0]);
        $htmlprefixes = array_merge($csss[1], $scripts[1], $imgs[1]);
        $links    = array_merge($csss[2], $scripts[2], $imgs[2]);
        switch ($this->CONF['linkrel_urlstyle']) {
        case 'absolute':
            $replace_topurl = $this->CONF['TOPURL'];
            break;
        case 'relative':
        default:
            $replace_topurl = get_relative_path($source, '');
            break;
        }
        $replaces = array();
        for ($i = 0; $i < count($links); $i++) {
            $link = str_replace($this->CONF['TOPURL'], '', $links[$i]);
            $replaces[$i] = $htmlprefixes[$i] . $replace_topurl . $link . '"';
        }
        return str_replace($searches, $replaces, $contents);
    }

    /**
     * Modify href links (only pukiwiki ?PageName or ?cmd=read&amp;page=PageName links)
     *
     * @param string &$contents contents to be modified
     * @param string $source source path used to compute relative path
     * @return string modified contents
     */
    function &modify_pkwk_href(&$contents, $source)
    {
        $links = $this->get_pkwk_href_links($contents, $this->CONF['TOPURL']);
        $searches = &$links[0];
        $prefixes = &$links[1];
        $urls     = &$links[3];
        $replaces = array();
        foreach ($urls as $i => $url) {
            $parsed = parse_url($url);
            if (! isset($parsed['query'])) {
                if ($url == $this->CONF['TOPURL']) {
                    $page = $GLOBALS['defaultpage'];
                } else {
                    unset($searches[$i]); continue;
                }
            } else {
                $page = $this->get_read_pagename($parsed['query']);
            }
            if ($page === null) {unset($searches[$i]); continue;}
            $anchor = isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '';
            $dumpurl = $this->get_dump_url($page, $source);
            if ($dumpurl === null) {unset($searches[$i]); continue;}
            $dumpurl .= $anchor;
            $replaces[] = $prefixes[$i] .  $dumpurl . '"';
        }
        //array_multisort($hrefs, SORT_DESC, SORT_STRING, $replaces);
        return str_replace($searches, $replaces, $contents);
    }
    
    /**
     * Get dump url
     *
     * @param string $page pagename
     * @param string $source source path used to compute relative path
     * @return string 
     */
    function get_dump_url($page, $source)
    {
        if (is_callable('get_autoaliases')) { // support lower versions
            $real = get_autoaliases($page);
            if (is_array($real)) { // plus
                if (count($real) === 1) {
                    $page = $real[0];
                } elseif (count($real) >= 2) {
                    // can not replace url to html address
                    return null;
                }
            } elseif (is_string($real)) { // org or old plus
                if ($real !== '') {
                    $page = $real;
                }
            }
        }
        if ($page == $GLOBALS['defaultpage']) {
            $url = $this->CONF['TOPURL'];
        } else {
            $filename = $this->get_dump_filename($page);
            switch ($this->CONF['href_urlstyle']) {
            case 'absolute':
                $url = $this->CONF['TOPURL'] . $filename;
                break;
            case 'relative':
                $url = $filename;
                $url = get_relative_path($source, $url);
            default:
                break;
            }
        }
        return $url;
    }

    /**
     * Get pagename from pukiwiki url query if cmd is read
     *
     * @static
     * @param string &$query
     * @return string or null
     */
    function &get_read_pagename(&$query)
    {
        $page = null;
        if (! isset($query)) {
            return $defaultpage;
        }
        $queries = array();
        parse_str($query, $queries);
        $cmd = isset($queries['cmd']) ? $queries['cmd'] :
            (isset($queries['plugin']) ? $queries['plugin'] : null);
        if (! isset($cmd)) {
            $page = urldecode($query);
        } elseif ($cmd === 'read') {
            $page = $queries['page'];
        }
        return $page;
    }
    
    /**
     * Extract href links which have a common top url
     *
     * @static
     * @param string &$contents
     * @param string $topurl The top url 
     * @return array 
     * [0] whole tag matches
     * [1] prefix words to hrefs
     * [2] hrefs
     * [3] absolute urls
     */
    function &get_pkwk_href_links(&$contents, $topurl)
    {
        // Must be <a....href="....." ....>
        $pattern = '#' . '(<a[^>]+href=")([^> "]*)"' . '#';
        $maches = array();
        preg_match_all($pattern, $contents, $matches);
        $matches[3] = array();
        foreach ($matches[2] as $i => $href) {
            $url = unhtmlspecialchars($href);
            $url = realurl($topurl, $url);
            if (strpos($url, $topurl) === 0) {
                $matches[3][$i] = $url;
            } else {
                unset($matches[0][$i]);
                unset($matches[1][$i]);
                unset($matches[2][$i]);
            }
        }
        return $matches;
    }

    /**
     * Extract css links
     *
     * @static
     * @param string &$contents
     * @return array
     * [0] whole tag matches
     * [1] prefix words to links
     * [2] hrefs
     */
    function &get_css_links(&$contents)
    {
        // Must be <link rel="stylesheet" href="....." .... />
        $pattern = '#' . '(<link +rel="stylesheet"[^>]+href=")([^> "]*)"' . '#';
        $matches = array();
        preg_match_all($pattern, $contents, $matches);
        return $matches;
    }

    /**
     * Extract javascript links
     *
     * @static
     * @param string &$contents
     * @return array
     * [0] whole tag matches
     * [1] prefix words to links
     * [2] links
     */
    function &get_javascript_links(&$contents)
    {
        // Must be <script type="text/javascript" src="...." .... />
        $pattern = '#' . '(<script +type="text/javascript"[^>]+src=")([^> "]*)"' . '#';
        $matches = array();
        preg_match_all($pattern, $contents, $matches);
        return $matches;
    }

    /**
     * Extract img links
     *
     * @static
     * @param string &$contents
     * @return array
     * [0] whole tag matches
     * [1] prefix words to links
     * [2] links
     */
    function &get_img_links(&$contents)
    {
        // Must be <img src="...." .... />
        $pattern = '#' . '(<img[^>]+src=")([^> "]*)"' . '#';
        $matches = array();
        preg_match_all($pattern, $contents, $matches);
        return $matches;
    }

    /**
     * Get the filename of dumped html
     *
     * @param string $page
     * @return string 
     */
    function get_dump_filename($page)
    {
        $args = array($page);
        return $this->CONF['DUMPDIR'] . 
            call_user_func_array($this->CONF['encode'], $args) . 
            $this->CONF['POSTFIX'];
    }
}

////////////////// PHP Extension ///////////////////////
if (! function_exists('get_relative_path')) {
    /**
     * Get relative path from a source path to a target path
     *
     * @static
     * @param string $source
     * @param string $target
     * @return string 
     */
    function get_relative_path($source, $target)
    {
        $source_dirs = explode('/', $source);
        $target_dirs = explode('/', $target);
        foreach ($source_dirs as $i => $source_dir) {
            if ($source_dirs[$i] == $target_dirs[$i]) {
                unset($source_dirs[$i]);
                unset($target_dirs[$i]);
            } else {
                break;
            }
        }
        $source = implode('/', $source_dirs);
        $target = implode('/', $target_dirs);
        $relative = str_repeat('../', substr_count($source, '/'));
        return $relative . $target;
    }
}

if (! function_exists('unhtmlspecialchars')) {
    /**
     * Undo htmlspecialchars
     *
     * @access public
     * @param string 
     * @return string Undone htmlspecialchars
     * @see htmlspecialchars()
     * @example unhtmlspecialchars.php
     */
    function &unhtmlspecialchars($string)
    {
        $string = str_replace('&amp;' , '&' , $string);
        $string = str_replace('&#039;', '\'', $string);
        $string = str_replace('&quot;', '"', $string);
        $string = str_replace('&lt;'  , '<' , $string);
        $string = str_replace('&gt;'  , '>' , $string);
        return $string;
    }
}
if (! function_exists('glue_url')) {
    /**
     * reverse parse_url
     *
     * PHP Extension
     *
     * @access public
     * @param array $parsed outputs by parse_url
     * @return string reversed parse_url
     * @see parse_url()
     */
    function glue_url($parsed) 
    {
        if (!is_array($parsed)) return false;
        $uri = isset($parsed['scheme']) ? $parsed['scheme'].':'.((strtolower($parsed['scheme']) == 'mailto') ? '' : '//') : '';
        $uri .= isset($parsed['user']) ? $parsed['user'].(isset($parsed['pass']) ? ':'.$parsed['pass'] : '').'@' : '';
        $uri .= isset($parsed['host']) ? $parsed['host'] : '';
        $uri .= isset($parsed['port']) ? ':'.$parsed['port'] : '';
        if(isset($parsed['path'])) {
            $uri .= (substr($parsed['path'], 0, 1) == '/') ? $parsed['path'] : ('/'.$parsed['path']);
        }
        $uri .= isset($parsed['query']) ? '?'.$parsed['query'] : '';
        $uri .= isset($parsed['fragment']) ? '#'.$parsed['fragment'] : '';
        return $uri;
    }
}
if (! function_exists('realurl')) {
    /**
     * Get absolute URL
     *
     * PHP Extension
     *
     * @access public
     * @param string $base base url
     * @param string $url relative url
     * @return string absolute url
     * @see parse_url()
     * @see realpath()
     * @uses glue_url()
     */
    function realurl($base, $url)
    {
        if (! strlen($base)) return $url;
        if (! strlen($url)) return $base;
        
        if (preg_match('!^[a-z]+:!i', $url)) return $url;
        $base = parse_url($base);
        if ($url{0} == "#") { 
            // fragment
            $base['fragment'] = substr($url, 1);
            return glue_url($base);
        }
        unset($base['fragment']);
        unset($base['query']);
        if (substr($url, 0, 2) == "//") {
            // FQDN
            $base = array(
                'scheme'=>$base['scheme'],
                'path'=>substr($url,2),
            );
            return glue_url($base);
        } elseif ($url{0} == "/") {
            // absolute path reference
            $base['path'] = $url;
        } else {
            // relative path reference
            $path = explode('/', $base['path']);
            $url_path = explode('/', $url);
            // drop file from base
            array_pop($path);
            // append url while removing "." and ".." from
            // the directory portion
            $end = array_pop($url_path);
            foreach ($url_path as $segment) {
                if ($segment == '.') {
                    // skip
                } elseif ($segment == '..' && $path && $path[sizeof($path)-1] != '..') {
                    array_pop($path);
                } else {
                    $path[] = $segment;
                }
            }
            // remove "." and ".." from file portion
            if ($end == '.') {
                $path[] = '';
            } elseif ($end == '..' && $path && $path[sizeof($path)-1] != '..') {
                $path[sizeof($path)-1] = '';
            } else {
                $path[] = $end;
            }
            $base['path'] = join('/', $path);
        }
        return glue_url($base);
    }
}

?>