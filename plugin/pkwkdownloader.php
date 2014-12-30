<?php
/**
 * PukiWiki Downloader
 *  
 * Requirement: PHP5, HTTP/Request.php, XML/XML_HTMLSax.php
 *
 * @author     sonots
 * @license    http://www.gnu.org/licenses/gpl.html GPL v2
 * @link       http://lsx.sourceforge.jp/?Plugin%2Fpkwkdownloader.php
 * @version    $Id: pkwkdownloader.php,v 1.6 2008-01-03 23:22:41Z sonots $
 * @package    pkwkdownloader
 * @uses       pkwklinkmodifier.inc.php
 */

/** 
 * PukiWiki URL including Scirpt Name (index.php)
 * @global string $GLOBALS['PKWKURL']
 */
$GLOBALS['PKWKURL']    = '';
/** 
 * Download Directory
 * @global string $GLOBALS['DOWNDIR']
 */
$GLOBALS['DOWNDIR']    = 'down/';
/** 
 * The Source Kind to Download
 *
 * 'wiki' or 'html' or 'attach'
 * @global string $GLOBALS['SOURCEKIND']
 */
$GLOBALS['SOURCEKIND'] = 'wiki';
/** 
 * Intervals of Downloading in micro seconds
 * @global int $GLOBALS['WAITTIME']
 */
$GLOBALS['WAITTIME']   = 2000000;
/** 
 * Continue Downloading
 * @global bool $GLOBALS['CONTINUE']
 */
$GLOBALS['CONTINUE']   = FALSE;
/** 
 * Administrator Password
 *
 * Plus! requires adminpass for cmd=filelist
 * @global string $GLOBALS['ADMINPASS']
 */
$GLOBALS['ADMINPASS']  = '';
/** 
 * PukiWiki Username
 *
 * BasicAuth Username for read_restricted page
 * @global string $GLOBALS['USERNAME']
 */
$GLOBALS['USERNAME']   = '';
/** 
 * The Password for the PukiWiki Username
 *
 * BasicAuth Password for 'USERNAME'
 * @global string $GLOBALS['USERPASS']
 */
$GLOBALS['USERPASS']   = '';
/** 
 * Filter Pages by a Regular Expression
 * @global string $GLOBALS['FILTER']
 */
$GLOBALS['FILTER']     = NULL;
/** 
 * Exclude Pages by a Regular Expression
 * @global string $GLOBALS['EXCEPT']
 */
$GLOBALS['EXCEPT']     = NULL;
/** 
 * Modify Links of Downloaded HTML Files
 * @global bool $GLOBALS['FORMAT']
 */
$GLOBALS['FORMAT']     = FALSE;
/** 
 * Use relative path or absolute path when FORMAT is executed. 
 * @global string $GLOBALS['URLSTYLE'] 'relative' or 'absolute'
 */
$GLOBALS['URLSTYLE']   = 'relative';
/** 
 * Encoding rule from page name into filename.
 * PHP script and use $str as a page name and a return value
 * @global string $GLOBALS['encode'] encode rule
 */
$GLOBALS['ENCODE']   = '
$str = rawurlencode($str);
$str = str_replace("-", "%2D", $str); # rawurlencode(chr(hexdec("2D"))) == "-"
$str = str_replace("%2F", "/", $str);
$str = preg_replace("/%([0-9a-f][0-9a-f])/i", "\\1-", $str);
';

set_time_limit(0);
main($argc, $argv);

/**
 * Main Function
 *
 * @access public
 * @param int $argc The number of command line arguments
 * @param array &$argv The command line arguments
 * @uses $GLOBALS['SOURCEKIND']
 * @uses downwiki()
 * @uses downhtml()
 * @uses downattach()
 */
function main($argc, &$argv)
{
    if ($argc < 2) {
        $help = '';
        $help .= '$ php pkwkdownloader.php [option...] pkwkurl' . "\n";
        $help .= '$ php pkwkdownloader.php --format [option...] pkwkurl' . "\n";
        $help .= 'Examples)' . "\n";
        $help .= '$ php pkwkdownloader.php -p [adminpass] -un [read username] -up [read userpass] -k html -d html/ http://example.com/pukiwiki/index.php' . "\n";
        $help .= '$ php pkwkdownloader.php -p [adminpass] -un [read username] -up [read userpass] -k wiki -d wiki/ http://example.com/pukiwiki/index.php' . "\n";
        $help .= '$ php pkwkdownloader.php -p [adminpass] -un [read username] -up [read userpass] -k attach -d attach/ http://example.com/pukiwiki/index.php' . "\n";
        $help .= '$ php pkwkdownloader.php --format -d html/ -s relative http://example.com/pukiwiki/index.php' . "\n";
        $help .= 'Please see http://lsx.sourceforge.jp/?pkwkdownloader.php for further details' . "\n";
        $help .= 'NOTE: PukiWiki Plus! requires adminpss always. This must be fixed in the future.' . "\n";
        print $help;
        exit;
    }
    while (($arg = next($argv)) !== FALSE) { // skip $argv[0]
        switch ($arg) {
        case '-d':
        case '--directory-prefix':
            $GLOBALS['DOWNDIR'] = next($argv);
            if (strrpos($GLOBALS['DOWNDIR'], '/') !== strlen($GLOBALS['DOWNDIR'])-1) {
                $GLOBALS['DOWNDIR'] .= '/';
            }
            break;
        case '-k':
        case '--kind':
            $GLOBALS['SOURCEKIND'] = next($argv);
            break;
        case '-c':
        case '--continue':
            $GLOBALS['CONTINUE'] = TRUE;
            break;
        case '-w':
        case '--waittime':
            $GLOBALS['WAITTIME'] = next($argv);
            break;
        case '-p':
        case '--password':
            $GLOBALS['ADMINPASS'] = next($argv);
            break;
        case '-un':
        case '--username':
            $GLOBALS['USERNAME'] = next($argv);
            break;
        case '-up':
        case '--userpass':
            $GLOBALS['USERPASS'] = next($argv);
            break;
        case '-g':
        case '--filter':
            $GLOBALS['FILTER'] = next($argv);
            break;
        case '-v':
        case '--except':
            $GLOBALS['EXCEPT'] = next($argv);
            break;
        case '-m':
        case '--format':
            $GLOBALS['FORMAT'] = TRUE;
            break;
        case '-s':
        case '-=urlstyle':
            $GLOBALS['URLSTYLE'] = next($argv);
            break;
        case '-e':
        case '--encode':
            $GLOBALS['ENCODE'] = next($argv);
            break;
        case '-p':
        case '-publichome':
            $GLOBALS['PUBLICHOME'] = next($argv);
            break;
        default:
            $GLOBALS['PKWKURL'] = $arg;
            $GLOBALS['PKWKBASEURL'] = substr($GLOBALS['PKWKURL'], 0, strrpos($GLOBALS['PKWKURL'], '/')) . '/';
            break;
        }
    }

    if ($GLOBALS['PKWKURL'] === '') {
        print 'No PukiWiki Top URL was specified. exit. ';
        exit;
    }

    if ($GLOBALS['FORMAT']) {
        format_html();
        exit;
    }

    switch ($GLOBALS['SOURCEKIND']) {
    case 'attach':
        downattach();
        break;
    case 'html':
        downhtml();
        break;
    case 'wiki':
    default:
        downwiki();
        break;
    }
}

/**
 * Encode strings
 * @access public
 * @param string $str string
 */
function encode($str)
{
    eval($GLOBALS['ENCODE']);
    return $str;
}

/**
 * Get attachment file name path on the local
 * @access public
 * @param string $page PageName
 * @param string $file attachment file name
 * @uses r_encode()
 * @uses encode()
 */
function get_attachfilename($page, $file)
{
    $name = encode($page) . '/' . encode($file);
    return $name;
}

/**
 * Get html file name path on the local
 * @access public
 * @param string $page PageName
 */
function get_htmlfilename($page)
{
    $name = encode($page);
    return $name . '.html';
}
    
/**
 * Get wiki source file name path on the local
 * @access public
 * @param string $page PageName
 * @uses r_encode()
 * @uses encode()
 */
function get_wikifilename($page)
{
    $name = strtoupper(bin2hex($page));
    return $name . '.txt';
}

/**
 * Modify Links of Downloaded HTML files
 * @access public
 */
function format_html()
{
    require_once('pkwklinkmodifier.inc.php');
    $modifier = new PKWKLinkModifier();
    $modifier->CONF['TOPURL']   = $GLOBALS['PKWKBASEURL'];
    $modifier->CONF['DUMPDIR'] = $GLOBALS['DOWNDIR'];
    $modifier->CONF['POSTFIX'] = '.html';
    $modifier->CONF['PUBLICHOME'] = $GLOBALS['PUBLICHOME']; // reserve
    $modifier->CONF['urlstyle'] = $GLOBALS['URLSTYLE'];
    $modifier->CONF['encode']   = 'encode';
    $files = get_existfiles($GLOBALS['DOWNDIR'], '.html', TRUE);
    if (empty($files)) print 'No file exist in ' . $GLOBALS['DOWNDIR'] . "\n";
    foreach ($files as $i => $file) {
        $contents = file_get_contents($file);
        $contents = $modifier->format($contents, $file);
        file_put_contents($file, $contents);
        print $file . "\n";
    }
}

/**
 * Download Attachments
 * @access public
 * @uses $GLOBALS['PKWKURL']
 * @uses $GLOBALS['DOWNDIR']
 * @uses $GLOBALS['CONTINUE']
 * @uses $GLOBALS['WAITTIME']
 * @see downwiki(), downhtml()
 * @uses pkwk_get_existattaches()
 * @uses get_attachfilename()
 */
function downattach()
{
    $url = $GLOBALS['PKWKURL'] . '?cmd=attach';
    if (($links = pkwk_get_existattaches($url, $GLOBALS['FILTER'], $GLOBALS['EXCEPT'])) === FALSE) {
        print 'Failed to download attach list. exit. ';
        exit;
    }
    if (! is_dir($GLOBALS['DOWNDIR'])) {
        mkdir($GLOBALS['DOWNDIR'], 0755, TRUE);
    }
    $failed = array();
    foreach($links as $i => $link) {
        $file = $GLOBALS['DOWNDIR'] . get_attachfilename($link['page'], $link['file']);
        if (! is_dir(($dir = dirname($file)))) {
            mkdir($dir, 0755, TRUE);
        }
        if ($GLOBALS['CONTINUE'] && file_exists($file)) {
            continue;
        }
        $url = unhtmlspecialchars($link['href']);
        if (($bin = http_get_contents($url, $GLOBALS['USERNAME'], $GLOBALS['USERPASS'])) === FALSE) {
            $failed[] = $link;
            continue;
        }
        print $link['page'] . '/' . $link['file'] . ' => ' . $file . "\n";
        if ($bin == '') {
            print 'Error: No content was downloaded.' . "\n";
        } elseif (file_put_contents($file, $bin) === FALSE) {
            print 'Failed to write, exit. ';
            exit;
        }
        usleep($GLOBALS['WAITTIME']);
    }
}

/**
 * Download PukiWiki HTML
 * @access public
 * @uses $GLOBALS['PKWKURL']
 * @uses $GLOBALS['DOWNDIR']
 * @uses $GLOBALS['CONTINUE']
 * @uses $GLOBALS['WAITTIME']
 * @see downwiki(), downattach()
 * @uses pkwk_get_existpages()
 * @uses get_htmlfilename()
 */
function downhtml()
{
    $url = $GLOBALS['PKWKURL'] . '?cmd=filelist';
    if (($links = pkwk_get_existpages($url, $GLOBALS['FILTER'], $GLOBALS['EXCEPT'])) === FALSE) {
        print 'Failed to download list url. exit. ';
        exit;
    }
    if (! is_dir($GLOBALS['DOWNDIR'])) {
        mkdir($GLOBALS['DOWNDIR'], 0755, TRUE);
    }
    $failed = array();
    foreach($links as $i => $link) {
        $file = $GLOBALS['DOWNDIR'] . get_htmlfilename($link['page']);
        if (! is_dir(($dir = dirname($file)))) {
            mkdir($dir, 0755, TRUE);
        }
        if ($GLOBALS['CONTINUE'] && file_exists($file)) {
            continue;
        }
        $url = unhtmlspecialchars($link['href']);
        if (($html = http_get_contents($url, $GLOBALS['USERNAME'], $GLOBALS['USERPASS'])) === FALSE) {
            $failed[] = $link;
            continue;
        }
        print $link['page'] . ' => ' . $file . "\n";
        if ($html == '') {
            print 'Error: No content was downloaded.' . "\n";
        } elseif (file_put_contents($file, $html) === FALSE) {
            print 'Failed to write, exit. ';
            exit;
        }
        usleep($GLOBALS['WAITTIME']);
    }
}

/**
 * Download PukiWiki Source
 * @access public
 * @uses $GLOBALS['PKWKURL']
 * @uses $GLOBALS['DOWNDIR']
 * @uses $GLOBALS['CONTINUE']
 * @uses $GLOBALS['WAITTIME']
 * @see downhtml(), downattach()
 * @uses pkwk_get_existpages()
 * @users pkwk_get_source()
 * @see get_wikifilename()
 */
function downwiki()
{
    $url = $GLOBALS['PKWKURL'] . '?cmd=filelist';
    if (($links = pkwk_get_existpages($url, $GLOBALS['FILTER'], $GLOBALS['EXCEPT'])) === FALSE) {
        print 'Failed to download list url. exit. ';
        exit;
    }
    if (! is_dir($GLOBALS['DOWNDIR'])) {
        mkdir($GLOBALS['DOWNDIR'], 0755, TRUE);
    }
    $failed = array();
    foreach($links as $i => $link) {
        $file =  $GLOBALS['DOWNDIR'] . $link['file'];
        if (! is_dir(($dir = dirname($file)))) {
            mkdir($dir, 0755, TRUE);
        }
        if ($GLOBALS['CONTINUE'] && file_exists($file)) {
            continue;
        }
        $url = unhtmlspecialchars($link['href']);
        if (($source = pkwk_get_source($url)) === FALSE) {
            $failed[] = $link;
            continue;
        }
        $source = unhtmlspecialchars($source);
        print $link['page'] . ' => ' . $file . "\n";
        if ($source == '') {
            print 'Error: No content was downloaded.' . "\n";
        } elseif (file_put_contents($file, $source) === FALSE) {
            print 'Failed to write, exit. ';
            exit;
        }
        usleep($GLOBALS['WAITTIME']);
    }
}

/**
 * Get PukiWiki Page Source via http using cmd=source
 * @access public
 * @param string $url Page URL
 * @return mixed PukiWiki Page Source. FALSE if HTTP GET failed. 
 * @uses PKWKSourceHandler
 * @uses PEAR XML/XML_HTMLSax.php
 */
function &pkwk_get_source($url)
{
    // pkwk source (cmd=source&page=PAGE)
    $parsed = parse_url($url);
    $queries = array();
    parse_str($parsed['query'], $queries); // rawurldecode
    $page = isset($queries['page']) ? $queries['page'] : rawurldecode($parsed['query']);
    $queries = array();
    $queries['cmd'] = 'source';
    $queries['page'] = $page;
    $parsed['query'] = glue_str($queries);
    $url = glue_url($parsed);
    if (($html = http_get_contents($url, $GLOBALS['USERNAME'], $GLOBALS['USERPASS'])) === FALSE) {
        return FALSE;
    }

    require_once 'XML/XML_HTMLSax.php';
    $parser = new XML_HTMLSax;
    $handler = new PKWKSourceHandler;
    $parser->set_object($handler);
    $parser->set_element_handler('openHandler', 'closeHandler');
    $parser->set_data_handler('dataHandler');
    $parser->parse($html);
    return $handler->source;
}

/**
 * Get PukiWiki Attachment List via http using cmd=attach
 * @access public
 * @param string $url PukiWiki URL (cmd=attach)
 * @return array 
 *     Attachment list whose each element has keys 'href', 'page', 'file'. 
 *     FALSE if HTTP GET failed. 
 * @uses PKWKAttachHandler
 * @uses PEAR XML/XML_HTMLSax.php
 */
function &pkwk_get_existattaches($url, $filterpage = NULL, $exceptpage = NULL)
{
    if (($html = http_get_contents($url, $GLOBALS['USERNAME'], $GLOBALS['USERPASS'])) === FALSE) {
        return FALSE;
    }
    require_once 'XML/XML_HTMLSax.php';
    $parser = new XML_HTMLSax;
    $handler = new PKWKAttachHandler;
    $parser->set_object($handler);
    $parser->set_element_handler('openHandler', 'closeHandler');
    $parser->set_data_handler('dataHandler');
    $parser->parse($html);

    foreach ($handler->links as $i => $link) {
        $url = unhtmlspecialchars($link['href']);
        $parsed = parse_url($url);
        $queries = array();
        parse_str($parsed['query'], $queries);
        $handler->links[$i]['page'] = urldecode($queries['refer']);
        //$handler->links[$i]['file'] = $queries['file'];
    }
    if ($filterpage !== NULL) {
        $pregfilterpage = '/' . str_replace('/', '\/', $filterpage) . '/';
        foreach ($handler->links as $i => $link) {
            if (! preg_match($pregfilterpage, $link['page'])) {
                unset($handler->links[$i]);
            }
        }
    }
    if ($exceptpage !== NULL) {
        $pregexceptpage = '/' . str_replace('/', '\/', $exceptpage) . '/';
        foreach ($handler->links as $i => $link) {
            if (preg_match($pregexceptpage, $link['page'])) {
                unset($handler->links[$i]);
            }
        }
    }
    return $handler->links;
}

/**
 * Get PukiWiki Page List via http using cmd=filelist
 * @access public
 * @param string $url PukiWiki URL (cmd=filelist)
 * @return array 
 *     Page list whose each element has keys 'href', 'page', 'file'. 
 *     FALSE if HTTP GET failed. 
 * @uses PKWKFilelistHandler
 * @uses PEAR XML/XML_HTMLSax.php
 * @uses $GLOBALS['ADMINPASS']
 * @uses $GLOBALS['USERNAME']
 * @uses $GLOBALS['USERPASS']
 */
function &pkwk_get_existpages($url, $filter = NULL, $except = NULL)
{
    $parsed = parse_url($url);
    $queries = array();
    parse_str($parsed['query'], $queries);
    $cmd = $queries['cmd'];
    if ($cmd == 'filelist' && $GLOBALS['ADMINPASS'] != '') {
        // POST adminpass
        require_once('HTTP/Request.php');
        $req = new HTTP_Request($url);
        $req->setMethod(HTTP_REQUEST_METHOD_POST);
        $req->addPostData('pass', $GLOBALS['ADMINPASS']);
        $req->setBasicAuth($GLOBALS['USERNAME'], $GLOBALS['USERPASS']);
        if (PEAR::isError($req->sendRequest())) {
            return FALSE;
        }
        $html = $req->getResponseBody();
    } else {
        if (($html = http_get_contents($url, $GLOBALS['USERNAME'], $GLOBALS['USERPASS'])) === FALSE) {
            return FALSE;
        }
    }
    require_once 'XML/XML_HTMLSax.php';
    $parser = new XML_HTMLSax;
    $handler = new PKWKFilelistHandler;
    $parser->set_object($handler);
    $parser->set_element_handler('openHandler', 'closeHandler');
    $parser->set_data_handler('dataHandler');
    $parser->parse($html);
    if ($filter !== NULL) {
        $pregfilter = '/' . str_replace('/', '\/', $filter) . '/';
        foreach ($handler->pages as $i => $page) {
            if (! preg_match($pregfilter, $page['page'])) {
                unset($handler->pages[$i]);
            }
        }
    }
    if ($except !== NULL) {
        $pregexcept = '/' . str_replace('/', '\/', $except) . '/';
        foreach ($handler->pages as $i => $page) {
            if (preg_match($pregexcept, $page['page'])) {
                unset($handler->pages[$i]);
            }
        }
    }
    if ($cmd != 'filelist') {
        foreach ($handler->pages as $i => $page) {
            $handler->pages[$i]['file'] = get_wikifilename($page['page']);
        }
    }

    // unique (probably this can be done in html parsing process concurrently, though)
    $uniq_pages = array();
    foreach ($handler->pages as $page) {
        $uniq_pages[] = $page['page'];
    }
    $uniq_pages = array_unique($uniq_pages);
    $pages = array();
    foreach ($uniq_pages as $i => $page) {
        $pages[] = $handler->pages[$i];
    }

    return $pages;
}

/**
 * Handler class to parse cmd=source
 *
 * Assuming below:
 * - correct XHTML
 * - structure as below
 * <code>
 * <html>
 * ...
 * <pre id="source">
 * NO HTML TAG
 * </pre>
 * ...
 * </html>
 * </code>
 *
 * @package pkwkdownloader
 */
class PKWKSourceHandler
{
    var $source;
    var $parentElem;
    var $here;
    function PKWKSourceHandler()
    {
        $this->source = '';
        $this->parentElem = array();
        $this->here  = false;
    }
    function dataHandler(&$parser, $data)
    {
        if ($this->here) {
            $this->source = $data;
        }
    }
    function openHandler(&$parser, $name, $attrs)
    {
        if ($name == 'pre' && $attrs['id'] == 'source') {
            $this->here = true;
        }
    }
    function closeHandler(&$parser, $name)
    {
        if ($this->here && $name == 'pre') {
            $this->here = false;
        }
    }
}

/**
 * Handler class to parse cmd=attach
 *
 * Assuming below:
 * - correct XHTML
 *
 * @package pkwkdownloader
 */
class PKWKAttachHandler
{
    var $links; // 'href', 'file'
    var $parentElem;
    var $here;
    function PKWKAttachHandler()
    {
        $this->links = array();
        $this->parentElem = array();
        $this->here  = false;
    }
    function dataHandler(&$parser, $data)
    {
        if ($this->here) {
            if (end($this->parentElem) == 'a') {
                end($this->links);
                $this->links[key($this->links)]['file'] = $data;
            }
        }
    }
    function openHandler(&$parser, $name, $attrs)
    {
        if (count($this->parentElem) == 0) {
            if ($name == 'div' && $attrs['id'] == 'body') {
                array_push($this->parentElem, $name);
            }
            return; 
        }
        end($this->parentElem);
        $paren = key($this->parentElem);
        if ($this->parentElem[$paren-2] == 'li' &&
            $this->parentElem[$paren-1] == 'ul' &&
            $this->parentElem[$paren] == 'li' && $name == 'a') {
            if (strpos($attrs['href'], 'http') === 0) {
                $this->here = true;
                $this->links[] = array('href' => $attrs['href']);
            }
        }
        array_push($this->parentElem, $name);
    }
    function closeHandler(&$parser, $name)
    {
        if ($this->here && $name == 'a') {
            $this->here = false;
        }
        array_pop($this->parentElem);
    }
}

/**
 * Handler class to parse cmd=filelist
 *
 * Assuming below:
 * <code>
 * <html>
 * ...
 * <div id="body">
 * ...
 * <ul>
 *  <li><a href="http://..../?pagename">page</a><small>(16d)</small>
 *   <ul><li>filename</li></ul>
 *  </li>
 *  <li><a href="http://..../?pagename">page</a><small>(16d)</small>
 *   <ul><li>filename</li></ul>
 *  </li>
 *  <li><a href="http://..../?pagename">page</a><small>(16d)</small>
 *   <ul><li>filename</li></ul>
 *  </li>
 *  <li><a href="http://..../?pagename">page</a><small>(16d)</small>
 *   <ul><li>filename</li></ul>
 *  </li>
 * </ul>
 * ...
 * </div>
 * ...
 * </html>
 * </code>
 *
 * UPDATE: does not require id="body"
 * UPDATE: do not use filename, create it from pagename
 * UPDATE: allow cmd=read for href (support old pukiwiki versions such as 1.4.3)
 *
 * @package pkwkdownloader
 */
class PKWKFilelistHandler
{
    /**
     * Page List 
     * 
     * array('key'=>'', 'href'=>'', 'file'=>'')
     * @var array
     */
    var $pages; // 'href', 'page', 'file'
    /** 
     * parent elements
     * @var array
     */
    private $parentElem;
    /** 
     * target link is here
     * @var bool
     */
    private $here;
    function PKWKFilelistHandler()
    {
        $this->pages = array();
        $this->parentElem = array();
        $this->here  = false;
    }
    function dataHandler(&$parser, $data)
    {
        if ($this->here) {
            if (end($this->parentElem) == 'a') {
                end($this->pages);
                $this->pages[key($this->pages)]['page'] = $data;
            } elseif (end($this->parentElem) == 'li') {
                end($this->pages);
                $this->pages[key($this->pages)]['file'] = $data;
            }
        }
    }
    function openHandler(&$parser, $name, $attrs)
    {
        if (end($this->parentElem) == 'li' && $name == 'a') {
            if (strpos($attrs['href'], 'http') === 0) {
                if (preg_match('/cmd=(?!read)/', $attrs['href'])) {
                    return;
                } elseif (in_array_by($attrs['href'], $this->pages, 'href')) {
                    return;
                } else {
                    $this->here = true;
                    $this->pages[] = array('href' => $attrs['href']);
                }
            }
        }
        array_push($this->parentElem, $name);
    }
    function closeHandler(&$parser, $name)
    {
        if ($this->here && $name == 'li') {
            $this->here = false;
        }
        array_pop($this->parentElem);
    }
}

//////////////// PHP Extension ////////////////

/**
 * reverse parse_str
 *
 * PHP Extension
 *
 * @access public
 * @param array outputs by parse_str
 * @return string reversed parse_str
 * @see parse_str()
 */
function &glue_str(&$queries)
{
    if (! is_array($queries))
        return false;
    
    $url_query = array();
    foreach ($queries as $key => $value) {
        $arg = ($value === '') ? rawurlencode($key) : 
            rawurlencode($key) . '=' . rawurlencode($value);
        array_push($url_query, $arg);
    }
    return implode('&', $url_query);
}

/**
 * get http contents with Basic Authentication if required
 *
 *  Requirement: HTTP/Request.php
 *
 * @access public
 * @param string $url URL
 * @param string $user BasicAuth username
 * @param string $pass BasicAuth pass
 * @return mixed (string)contents if succeeded, FALSE if failed
 * @see file_get_contents()
 */
function http_get_contents($url, $user = '', $pass = '')
{
    if ($user == '') {
        return file_get_contents($url);
    }
    require_once('HTTP/Request.php');
    $req = new HTTP_Request($url);
    $req->setMethod(HTTP_REQUEST_METHOD_GET);
    $req->setBasicAuth($user, $pass);
    if (PEAR::isError($req->sendRequest())) {
        return FALSE;
    }
    return $req->getResponseBody();
}

/**
 * reverse parse_url
 *
 * PHP Extension
 *
 * @access public
 * @param array outputs by parse_url
 * @return string reversed parse_url
 * @see parse_url()
 */
function &glue_url(&$parsed) 
{
    if (! is_array($parsed))
        return false;
    
    $url = $parsed['scheme'] ? $parsed['scheme'].':'
        .((strtolower($parsed['scheme']) == 'mailto') ? '':'//'): '';
    $url .= $parsed['user'] ? $parsed['user']
        .($parsed['pass']? ':'.$parsed['pass']:'').'@':'';
    $url .= $parsed['host'] ? $parsed['host'] : '';
    $url .= $parsed['port'] ? ':'.$parsed['port'] : '';
    $url .= $parsed['path'] ? $parsed['path'] : '';
    $url .= $parsed['query'] ? '?'.$parsed['query'] : '';
    $url .= $parsed['fragment'] ? '#'.$parsed['fragment'] : '';
    return $url;
}

/**
 * reverse htmlspecialchars
 *
 * PHP Extension
 *
 * @access public
 * @param string 
 * @return string reversed htmlspecialchars
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

function in_array_by($value, $array, $fieldname = null)
{
    //foreach ($array as $i => $befree) {
    //    $field_array[$i] = $array[$i][$fieldname];
    //}
    //return in_array($value, $field_array);
    
    foreach ($array as $i => $val) {
        if ($value == $val[$fieldname]) {
            return true;
        }
    }
    return false;
}
?>

