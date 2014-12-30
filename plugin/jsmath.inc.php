<?php 
/**
 * Display Math Equations using jsMath
 *
 * @author     sonots
 * @license    http://www.gnu.org/licenses/gpl.html GPL v2
 * @link       http://lsx.sourceforge.jp/?Plugin%2Fjsmath.inc.php
 * @version    $Id: jsmath.inc.php,v 1.1 2008-04-08 06:10:21Z sonots $
 * @package    plugin
 */
if (! defined('INIT_DIR')) // if not Plus! 
    if (file_exists(DATA_HOME . 'init/jsmath.ini.php')) 
        include_once(DATA_HOME . 'init/jsmath.ini.php');

if (! defined('JSMATH_PATH')) {
    define('JSMATH_PATH', (defined('SKIN_URI') ? SKIN_URI : SKIN_DIR) . 'jsMath/');
}
if (! isset($GLOBALS['JSMATH_CUSTOM'])) {
    $GLOBALS['JSMATH_CUSTOM'] = array(
        "'<math>','</math>',':<math>','</math>'",
        //"'$ ',' $','$$ ',' $$'",
    );
}

function plugin_jsmath_body($args)
{
    global $head_tags;
    $options = array(
         'mimeTeX' => FALSE,
         'smallFonts' => FALSE,
         'noImageFonts' => FALSE,
         'lobal' => FALSE,
         'noGlobal' => FALSE,
         'noCache' => FALSE,
         'CHMmode' => FALSE,
         'spriteImageFonts' => FALSE,
    );
    $rest = array();
    foreach ($args as $arg) {
        list($key, $val) = array_pad(explode('=', $arg, 2), 2, TRUE);
        if (array_key_exists($key, $options)) {
            $options[$key] = $val;
        } else {
            array_push($rest, $arg);
        }
    }
    $body = implode(',', $rest);
    foreach ($options as $option => $bool) {
        if ($bool) {
            $script = ' <script type="text/javascript">jsMath.Extension.Require("' . $option . '");</script>';
            $diff = array_diff((array)$script, $head_tags);
            $head_tags = array_merge($head_tags, $diff);
        }
    }
    return $body;
}
function plugin_jsmath_init()
{
    global $head_tags;
    $head_tags[] = ' <script type="text/javascript" src="' . JSMATH_PATH . 'easy/load.js"></script>';
    foreach ($GLOBALS['JSMATH_CUSTOM'] as $custom) {
        $head_tags[] = ' <script type="text/javascript">jsMath.CustomSearch(' . $custom . ');</script>';
        $head_tags[] = ' <script type="text/javascript">jsMath.ConvertCustom(document);</script>';
    }
}
function plugin_jsmath_convert()
{
    $args = func_get_args();
    $body = plugin_jsmath_body($args);
    return '<div class="math">' . htmlspecialchars($body) . '</div>';
}
function plugin_jsmath_inline()
{
    $args = func_get_args();
    $end = array_pop($args); // {}
    $body = plugin_jsmath_body($args);
    $body = ($body) ? htmlspecialchars($body) : $end;
    return '<span class="math">' . $body . '</span>';
}
?>
