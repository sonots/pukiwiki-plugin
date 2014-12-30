<?php
/**
 * Multi-Column or Split-Body Plugin
 *
 * @author     sonots
 * @license    http://www.gnu.org/licenses/gpl.html GPL v2
 * @link       http://lsx.sourceforge.jp/?Plugin%2Fsplitbody.inc.php
 * @version    $Id: splitbody.inc.php,v 1.2 2007-02-24 16:28:39Z sonots $
 * @package    plugin
 */

class PluginSplitbody
{
    // static
    var $default_options = array();
    // var
    var $options = array();
    var $border = 'border-right: 1px solid black;';
    var $splittag = '#split';

    function PluginSplitbody()
    {
        static $default_options = array();
        if (empty($default_options)) {
            $default_options['tag'] = 'table'; // 'div' or 'table'
            $default_options['style'] = '';
            $default_options['border'] = false;
            $default_options['width'] = '100%';
        }
        $this->default_options = &$default_options;

        // init
        $this->options = $this->default_options;
    }

    function convert()
    {
        // arguments
        if (func_num_args() === 0) { return; }
        $args = func_get_args();
        $body = array_pop($args);
        $body = str_replace("\r", "\n", $body);
        foreach ($args as $arg) {
            list($key, $val) = array_pad(explode('=', $arg, 2), 2, true);
            $this->options[$key] = $val;
        }
        $this->options['style'] = htmlspecialchars($this->options['style']);
        $this->options['width'] = htmlspecialchars($this->options['width']);
        
        // main
        list($bodies, $splitargs) = $this->splitbody($body);
        $splitoptions = array();
        foreach ($splitargs as $i => $splitarg) {
            $splitoptions[$i] = array();
            foreach ($splitarg as $arg) {
                list($key, $val) = array_pad(explode('=', $arg, 2), 2, true);
                $splitoptions[$i][$key] = htmlspecialchars($val);
            }
        }
        if ($this->options['tag'] == 'table') {
            $output = $this->table($bodies, $splitoptions);
        } else {
            $output = $this->div($bodies, $splitoptions);
        }
        return $output;
    }
    
    function table(&$bodies, &$splitoptions)
    {
        $num      = count($bodies);
        $border   = $this->options['border'] === true ? $this->border : '';
        $width    = $this->options['width'];
        $colstyle = $this->options['style'];
        $colwidth = intval(100 / $num) . '%';

        $html = '<table class="splitbody" style="width:' . $width . ';"><tr>' . "\n";
        for ($i = 0; $i < $num; $i++) {
            $body = $bodies[$i];
            $width = isset($splitoptions[$i]['width']) ? $splitoptions[$i]['width'] : $colwidth;
            $style = isset($splitoptions[$i]['style']) ? $splitoptions[$i]['style'] : $colstyle;
            $html .= '<td style="vertical-align:top;width:' . $width . ';min-width:' . $width . ';max-width:' . $width . ';' . $style;
            if ($i < $num - 1) {
                $html .= $border;
            }
            $html .= '">' . "\n";
            $html .= convert_html($body);
            $html .= '</td>' . "\n";
        }
        $html .= '</tr></table>' . "\n";
        return $html;
    }
    
    function div(&$bodies, &$splitoptions)
    {
        $num      = count($bodies);
        $border   = $this->options['border'] === true ? $this->border : '';
        $width    = $this->options['width'];
        $colwidth = intval(96 / $num) . '%'; // 96%....
        $colstyle = $this->options['style'];

        $html = '<div class="splitbody" style="width:' . $width . ';">' . "\n";
        for ($i = 0; $i < $num; $i++) {
            $body = $bodies[$i];
            $width = isset($splitoptions[$i]['width']) ? $splitoptions[$i]['width'] : $colwidth;
            $style = isset($splitoptions[$i]['style']) ? $splitoptions[$i]['style'] : $colstyle;
            $html .= '<div style="float:left;width:' . $width . ';min-width:' . $width . ';max-width:' . $width . ';' . $style;
            if ($i < $num - 1) {
                $html .= $border;
            }
            $html .= '">' . "\n";
            $html .= convert_html($body);
            $html .= '</div>' . "\n";
        }
        $html .= '<div style="clear:both;"></div>' . "\n";
        $html .= '</div>' . "\n";
        return $html;
    }

    function splitbody(&$body)
    {
        $lines = explode("\n", $body);
        $splitargs = array();
        $bodies = array(); 

        $line = current($lines);
        $matches = array(); 
        if (preg_match('/' . $this->splittag . '(?:\(([^)]*)\))?(.*)$/', $line, $matches)) {
            $splitargs[] = csv_explode(',', $matches[1]);
            $bodies[0] = '';
        } else {
            $bodies[0] = $line . "\n";
        }

        $i = 0; 
        while (($line = next($lines)) !== false) { 
            $matches = array();
            if (preg_match('/' . $this->splittag . '(?:\(([^)]*)\))?(.*)$/', $line, $matches)) {
                $splitargs[] = csv_explode(',', $matches[1]);
                $bodies[++$i] = '';
            } else {
                $bodies[$i] .= $line . "\n";
            }
        }

        if (count($bodies) > count($splitargs)) {
            array_unshift($splitargs, array());
        }
        return array(&$bodies, &$splitargs);
    }
}

function plugin_splitbody_common_init()
{
    global $plugin_splitbody;
    if (class_exists('PluginSplitbodyUnitTest')) {
        $plugin_splitbody = new PluginSplitbodyUnitTest();
    } elseif (class_exists('PluginSplitbodyUser')) {
        $plugin_splitbody = new PluginSplitbodyUser();
    } else {
        $plugin_splitbody = new PluginSplitbody();
    }
}

function plugin_splitbody_convert()
{
    global $plugin_splitbody; plugin_splitbody_common_init();
    $args = func_get_args();
    return call_user_func_array(array(&$plugin_splitbody, 'convert'), $args);
}

?>
