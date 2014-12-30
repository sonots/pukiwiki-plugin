<?php
/**
 * Flash plugin
 *
 * @author     sonots
 * @license    http://www.gnu.org/licenses/gpl.html GPL v2
 * @version    $Id: flash.inc.php,v 1.1 2007-06-10 11:14:46 sonots $
 * @package    plugin
 */

function plugin_flash_convert()
{
    $args = func_get_args();
    if (count($args) < 1) {
        return '<div>flash(): #flash(URL,[option])<div>';
    }
    $url = htmlspecialchars(array_shift($args));
    $options = array(
         'style' => '',
         'width' => '',
         'height' => '',
         'flashvars' => '',
         'quality' => '',
         'bgcolor' => '',
         'loop' => '',
         'play' => '',
         'scale' => '',
         'salign' => '',
         'base' => '',
         'menu' => '',
         'wmode' => '',
         'allowScriptAccess' => '',
    );
    foreach ($args as $arg) {
        list($key, $val) = array_pad(explode('=', $arg, 2), 2, TRUE);
        if (isset($options[$key])) {
            $options[$key] = $val;
        }
    }
    $options['flashvars'] = rawurlencode($options['flashvars']);
    foreach ($options as $key => $val) {
        $options[$key] = htmlspecialchars($val);
    }

    return <<<HTML
<div class="flash" style="{$options['style']}">
<object data="{$url}" width="{$options['width']}" height="{$options['height']}" id="{$options['id']}" type="application/x-shockwave-flash">
<param name="movie" value="{$url}" />
<param name="FlashVars" value="{$options['flashvars']}" />
<param name="quality" value="{$options['quality']}" />
<param name="bgcolor" value="{$options['bgcolor']}" />
<param name="loop" value="{$options['loop']}" />
<param name="play" value="{$options['play']}" />
<param name="scale" value="{$options['scale']}" />
<param name="salign" value="{$options['salign']}" />
<param name="base" value="{$options['base']}" />
<param name="menu" value="{$options['menu']}" />
<param name="wmode" value="{$options['wmode']}" />
<param name="allowScriptAccess" value="{$options['allowScriptAccess']}" />
<p>Your browser is not supporting object tag. Please use one of the latest browsers.</p>
</object>
</div>
HTML;
}
?>
