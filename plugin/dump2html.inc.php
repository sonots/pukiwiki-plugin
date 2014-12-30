<?php
/**
 * Dumps wiki outputs into static HTMLs
 * 
 * @author     sonots
 * @license    http://www.gnu.org/licenses/gpl.html GPL v2
 * @link       http://lsx.sourceforge.jp/?Plugin%2Fdump2html.inc.php
 * @version    $Id: dump2html.inc.php,v 1.29 2008-01-03 16:53:22Z sonots $
 * @package    plugin
 * @uses       statichtml.inc.php
 * @uses       pkwklinkmodifier.cls.php
 */

/**
 *  dump2html plugin class
 *
 *  @author     sonots
 *  @license    http://www.gnu.org/licenses/gpl.html GPL v2
 *  @link       http://lsx.sourceforge.jp/?Plugin%2Fdump2html
 */
exist_plugin('statichtml') or die_message('statichtml.inc.php does not exist.');
exist_plugin('pkwklinkmodifier') or die_message('pkwklinkmodifier.inc.php does not exist.');

class PluginDump2html extends PluginStatichtml
{
    function PluginDump2html()
    {
        parent::PluginStatichtml();
        global $pkwklinkmodifier_name;
        $this->modifier = new $pkwklinkmodifier_name();
        // static
        static $CONF = array();
        $this->CONF = &$CONF;
        if (empty($this->CONF)) {
            global $whatsnew, $whatsdeleted;
            $this->CONF['DUMPDIR']              = DATA_HOME . 'html/';
            $this->CONF['POSTFIX']              = '.html';
            $this->CONF['SPECIAL_PAGES']        = array($whatsnew, $whatsdeleted);
            $this->CONF['readauth']             = TRUE;
            $this->CONF['username']             = ''; // for BasicAuth in the case of method='http'
            $this->CONF['userpass']             = '';
            // config for action plugin
            $this->CONF['ADMINONLY']            = TRUE;
            $this->CONF['WAITTIME']             = 200000; // for method=http in micro seconds
            $this->CONF['overwrite']            = FALSE;
            // config for pkwklinkmodifier
            $this->modifier->CONF['modify_href']      = TRUE;
            $this->modifier->CONF['modify_linkrel']   = TRUE;
            $this->modifier->CONF['href_urlstyle']    = 'relative'; // 'relative' or 'absolute'
            $this->modifier->CONF['linkrel_urlstyle'] = 'relative'; // 'relative' or 'absolute'
            $this->modifier->CONF['DUMPDIR']          = $this->CONF['DUMPDIR'];
            $this->modifier->CONF['POSTFIX']          = $this->CONF['POSTFIX'];
            $this->modifier->CONF['TOPURL']           = get_pkwk_topurl();
            $this->modifier->CONF['encode']           = array(&$this, 'encode');
            // obsolete
            $this->CONF['REDIRECT_AFTER_DUMP'] = FALSE;
            $this->CONF['404_PAGE']            = '404';
            //$this->CONF['BLOCK_ADMINONLY']     = TRUE;
            //$this->CONF['method']              = 'http'; // 'http' or 'dump'
            //$this->CONF['treedump']            = TRUE;
        }
    }

    var $plugin = 'dump2html';
    var $modifier;

    /**
     * Dump the PukiWiki output of a page into a html file
     *
     * @param string $page Pagename
     * @param string $file Filename to be dumped. Default is computed from $page. 
     * @param boolean $overwrite Force to overwrite. Default overwrites if $page is newer than $file
     * @return mixed
     *   TRUE : Success
     *   FALSE: Failure
     *   -1   : It is already up2date
     *   -2   : Exit by read-restriction
     *   -3   : Exit because statichtml USER_AGENT called statichtml again (infinite loop)
     */
    function dump_page($page, $file = null, $overwrite = FALSE)
    {
        $ret = parent::dump_page($page, $file, $overwrite);
        if ($ret !== TRUE) return $ret;

        if ($this->modifier->CONF['modify_href'] || $this->modifier->CONF['modify_linkrel']) {
            $file = isset($file) ? $file : $this->get_dump_filename($page);
            $contents = file_get_contents($file);

            $modified = $this->modifier->format($contents, $file);
            if (! file_put_contents($file, $modified)) {
                $this->error = 'Failed to create ' . $file; return FALSE;
            }

            // Extra: $defaultpage -> index.html
            if ($page == $GLOBALS['defaultpage'] && ! is_page('index')) {
                $file = $this->CONF['DUMPDIR'] . 'index.html';
                $modified = $this->modifier->format($contents, $file);
                if (! file_put_contents($file, $modified)) {
                    $this->error = 'Failed to create ' . $file; return FALSE;
                }
            }
        }
        return TRUE;
    }

    /**
     * Experiment: Write After Plugin Main Function
     *
     * @param string &$page
     * @param string &$postdata
     * @param boolean &$notimestamp
     * @return void or exit;
     */
    function write_after()
    {
        parent::write_after();
        if ($this->CONF['REDIRECT_AFTER_DUMP']) {
            if (is_page($page)) {
                header('Location: ' . $this->get_dump_url($page));
            } else {
                // autocreate
                if (! is_page($this->CONF['404_PAGE'])) {
                    page_write($this->CONF['404_PAGE'], 'Page was Deleted');
                }
                $this->dump_page($this->CONF['404_PAGE'], null, FALSE);
                header('Location: ' . $this->get_dump_url($this->CONF['404_PAGE']));
            }
            exit;
        }
    }
}

///////////////////////////////////////////
function plugin_dump2html_init()
{
    global $plugin_dump2html_name;
    if (class_exists('PluginDump2htmlUnitTest')) {
        $plugin_dump2html_name = 'PluginDump2htmlUnitTest';
    } elseif (class_exists('PluginDump2htmlUser')) {
        $plugin_dump2html_name = 'PluginDump2htmlUser';
    } else {
        $plugin_dump2html_name = 'PluginDump2html';
    }
    global $pkwklinkmodifier_name;
    if (class_exists('PKWKLinkModifierUnitTest')) {
        $pkwklinkmodifier_name = 'PKWKLinkModifierUnitTest';
    } elseif (class_exists('PKWKLinkModifierUser')) {
        $pkwklinkmodifier_name = 'PKWKLinkModifierUser';
    } else {
        $pkwklinkmodifier_name = 'PKWKLinkModifier';
    }
}

function plugin_dump2html_action()
{
    global $plugin_dump2html_name; 
    $plugin_dump2html = new $plugin_dump2html_name();
    return $plugin_dump2html->action();
}

function plugin_dump2html_onread()
{
    global $plugin_dump2html_name; 
    $plugin_dump2html = new $plugin_dump2html_name();
    $args = func_get_args();
    return call_user_func_array(array(&$plugin_dump2html, 'onread'), $args);
}

function plugin_dump2html_write_after()
{
    global $plugin_dump2html_name; 
    $plugin_dump2html = new $plugin_dump2html_name();
    $args = func_get_args();
    return call_user_func_array(array(&$plugin_dump2html, 'write_after'), $args);
}

if (! defined('INIT_DIR')) // if not Plus! 
    if (file_exists(DATA_HOME . 'init/dump2html.ini.php')) 
        include_once(DATA_HOME . 'init/dump2html.ini.php');

?>
