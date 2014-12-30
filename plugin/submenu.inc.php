<?php
/**
 * Submenu Plugin
 *
 * @author     sonots
 * @license    http://www.gnu.org/licenses/gpl.html GPL v2
 * @link       http://lsx.sourceforge.jp/?Plugin%2Fsubmenu.inc.php
 * @version    $Id: submenu.inc.php,v 1.1 2007-02-24 16:28:39Z sonots $
 * @package    plugin
 */

function plugin_submenu_convert() {
    global $vars;
    $args = func_get_args();
    $body = array_pop($args);
    $body = str_replace("\r", "\n", str_replace("\r\n", "\n", $body));
    $options = array(
        'prefix' => '',
        'filter' => '',
        'except' => '',
    );
    foreach ($args as $arg) {
        list($key, $val) = array_pad(explode('=', $arg), 2, '');
        $options[$key] = $val;
    }
    if ($options['prefix'] != '') {
        if (strpos($vars['page'], $options['prefix']) !== 0) return '';
    }
    if ($options['filter'] != '') {
        if (! preg_match('/' . str_replace('/', '\/', $options['filter']) . '/', $vars['page'])) return '';
    }
    if ($options['except'] != '') {
        if (preg_match('/' . str_replace('/', '\/', $options['except']) . '/', $vars['page'])) return '';
    }
    return convert_html($body);
}
?>
