<?php
/**
 * CSS Style Plugin
 *  
 * @author     sonots
 * @license    http://www.gnu.org/licenses/gpl.html GPL v2
 * @link       http://lsx.sourceforge.jp/?Plugin%2Fstyle.inc.hp
 * @version    $Id: style.inc.php,v 1.5 2008-01-04 19:02:47Z sonots $
 * @package    plugin
 */

function plugin_style_convert()
{
    $args = func_get_args();
    $end = end($args);
    if (substr($end, -1) == "\r") {
        $body = array_pop($args);
    }
    $options = array(
        'style' => NULL,
        'class' => NULL,
        'addstyle' => NULL,
        'addclass' => NULL,
        'putclass' => NULL,
        'putstyle' => NULL,
        'end'   => FALSE,
    );
    foreach ($args as $arg) {
        list($key, $val) = array_pad(explode('=', $arg, 2), 2, TRUE);
        if (array_key_exists($key, $options)) {
            $options[$key] = $val;
        } else { // default
            $options['style'] = $arg;
        }
    }

    $open = '';
    $open .= (! is_null($options['class'])) ? ' class="' . htmlspecialchars($options['class']) . '"' : '';
    $open .= (! is_null($options['style'])) ? ' style="' . htmlspecialchars($options['style']) . '"' : '';
    $open = ($open != '') ? '<div' . $open . '>' . "\n" : '';
    if (isset($body)) {
        $body = str_replace("\r", "\n", $body);
        $body = convert_html($body);
        plugin_style_replace_leadingtag($body, $options['addstyle'], $options['addclass'], 
                                        $options['putstyle'], $options['putclass']);
    }
    $close = (isset($body) && $open != '') || $options['end'] ? '</div>' : '';
    return $open . $body . $close;
}

function plugin_style_replace_leadingtag(&$html, $addstyle, $addclass, $putstyle, $putclass)
{
    if (is_null($addclass) && is_null($putclass) && is_null($addstyle) && is_null($putstyle)) return;
    $addstyle = ! is_null($addstyle) ? htmlspecialchars($addstyle) : NULL;
    $putstyle = ! is_null($putstyle) ? htmlspecialchars($putstyle) : NULL;
    $addclass = ! is_null($addclass) ? htmlspecialchars($addclass) : NULL;
    $putclass = ! is_null($putclass) ? htmlspecialchars($putclass) : NULL;
    $matches = array();
    preg_match('#^([^<]*)(<[^>]*>)(.*)#s', $html, $matches);
    $head = $matches[1];
    $tag  = $matches[2]; // leading tag
    $rest = $matches[3];

    if (! is_null($putclass) || ! is_null($addclass)) {
        $matches = array();
        if (preg_match('#^(.*)class="([^"]*)"(.*)$#', $tag, $matches)) {
            $putclass = ! is_null($putclass) ? $putclass : $matches[2] . ' ' . $addclass;
            $tag = $matches[1] . 'class="' . $putclass . '"' . $matches[3];
        } else {
            $putclass = ! is_null($putclass) ? $putclass : $addclass;
            $tag = str_replace('>', ' class="' . $putclass . '">', $tag);
        }
    }
    if (! is_null($putstyle) || ! is_null($addstyle)) {
        $matches = array();
        if (preg_match('#^(.*)style="([^"]*)"(.*)$#', $tag, $matches)) {
            $addstyle = preg_match('#; *$#', $matches[2]) ? $matches[2] . $addstyle : $matches[2] . ';' . $addstyle; 
            $putstyle = ! is_null($putstyle) ? $putstyle : $addstyle;
            $tag = $matches[1] . 'style="' . $putstyle . '"' . $matches[3];
        } else {
            $putstyle = ! is_null($putstyle) ? $putstyle : $addstyle;
            $tag = str_replace('>', ' style="' . $putstyle . '">', $tag);
        }
    }
    $html = $head . $tag . $rest;
}

?>
