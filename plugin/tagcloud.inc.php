<?php
require_once(dirname(__FILE__) . '/sonots/sonots.class.php');
require_once(dirname(__FILE__) . '/sonots/tag.class.php');
//error_reporting(E_ALL);

/**
 *  TagCloud Plugin
 *
 *  @package    plugin
 *  @license    http://www.gnu.org/licenses/gpl.html GPL v2
 *  @author     sonots <http://lsx.sourceforge.jp>
 *  @link       http://lsx.sourceforge.jp/?Plugin%2Ftag.inc.php
 *  @version    $Id: tagcloud.inc.php,v 2.0 2008-06-10 07:23:17Z sonots $
 *  @require    sonots/sonots    v 1.9
 *  @require    sonots/tag       v 1.0
 *  @compatible taglist.inc.php  v 2.0
 *  @compatible tag.inc.php      v 2.0
 */

class PluginTagcloud
{
    function PluginTagcloud()
    {
        static $conf_options = array();
        if (empty($conf_options)) {
            $conf_options['limit']   = NULL;
            $conf_options['related'] = NULL;
            $conf_options['cloud']   = TRUE;
        }
        // static
        $this->conf_options = & $conf_options;
    }

    var $conf_options;

    function convert() // tagcloud
    {
        $args  = func_get_args();
        $options = $this->conf_options;
        $options = sonots::parse_options($args, $options);

        // check_options
        if ($options['limit'] === "0") {
            $options['limit'] = NULL;
        }
        if ($options['cloud'] === 'off' ||
            $options['cloud'] === 'false' ) {
            $options['cloud'] = FALSE;
        }
        //print_r($options);

        $plugin_tag = new PluginSonotsTag();
        if ($options['cloud']) {
            $html = $plugin_tag->display_tagcloud($options['limit'], $options['related']);
        } else {
            $html = $plugin_tag->display_taglist($options['limit'], $options['related']);
        }
        return $html;
    }
}

function plugin_tagcloud_init()
{
    global $plugin_tagcloud_name;
    if (class_exists('PluginTagcloudUnitTest')) {
        $plugin_tagcloud_name = 'PluginTagcloudUnitTest';
    } elseif (class_exists('PluginTagcloudUser')) {
        $plugin_tagcloud_name = 'PluginTagcloudUser';
    } else {
        $plugin_tagcloud_name = 'PluginTagcloud';
    }
}

function plugin_tagcloud_convert()
{
    global $plugin_tagcloud, $plugin_tagcloud_name;
    $plugin_tagcloud = new $plugin_tagcloud_name();
    $args = func_get_args();
    return call_user_func_array(array(&$plugin_tagcloud, 'convert'), $args);
}

if (! defined('INIT_DIR')) // if not Plus! 
    if (file_exists(DATA_HOME . 'init/tagcloud.ini.php')) 
        include_once(DATA_HOME . 'init/tagcloud.ini.php');
?>
