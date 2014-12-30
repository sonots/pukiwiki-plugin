<?php
/**
 * Highlight Code Syntax using dp.SyntaxHighlighter (valid with 1.5.1)
 *
 * @author     sonots
 * @license    http://www.gnu.org/licenses/gpl.html GPL v2
 * @link       http://lsx.sourceforge.jp/?Plugin%2Fhighlight.inc.php
 * @version    $Id: highlight.inc.php,v 1.2 2008-03-12 16:28:39Z sonots $
 * @package    plugin
 */

define(PLUGIN_HIGHLIGHT_URI, (defined('SKIN_URI') ? SKIN_URI : SKIN_DIR) . 'dp.SyntaxHighlighter/');

function plugin_highlight_init()
{
    global $head_tags;
    $head_tags[] = '<link type="text/css" rel="stylesheet" href="' . PLUGIN_HIGHLIGHT_URI . 'Styles/SyntaxHighlighter.css"></link>';
}

function plugin_highlight_convert()
{
    global $head_tags;
    static $jssrc = array(
        "cpp"        => "shBrushCpp.js",
        "c"          => "shBrushCpp.js",
        "c++"        => "shBrushCpp.js",
        "c#"         => "shBrushCSharp.js",
        "c-sharp"    => "shBrushCSharp.js",
        "csharp"     => "shBrushCSharp.js",
        "css"        => "shBrushCss.js",
        "delphi"     => "shBrushDelphi.js",
        "pascal"     => "shBrushDelphi.js",
        "java"       => "shBrushJava.js",
        "js"         => "shBrushJScript.js",
        "jscript"    => "shBrushJScript.js",
        "javascript" => "shBrushJScript.js",
        "php"        => "shBrushPhp.js",
        "py"         => "shBrushPython.js",
        "python"     => "shBrushPython.js",
        "rb"         => "shBrushRuby.js",
        "ruby"       => "shBrushRuby.js",
        "rails"      => "shBrushRuby.js",
        "ror"        => "shBrushRuby.js",
        "sql"        => "shBrushSql.js",
        "vb"         => "shBrushVb.js",
        "vb.net"     => "shBrushVb.js",
        "xml"        => "shBrushXml.js",
        "html"       => "shBrushXml.js",
        "xhtml"      => "shBrushXml.js",
        "xslt"       => "shBrushXml.js",
    );
    static $languages = array();
    
    $args   = func_get_args();
    $end    = in_array("end", $args);
    $body   = array_pop($args);
    $class  = array_shift($args);
    list($language) = explode(':', $class);
    
    $ret = '';
    if (in_array($language, array_keys($jssrc))) {
        $languages[$language] = true;
        
        $ret .= '<div>';
        $ret .= '<textarea name="code" cols="60" rows="10" class="' . htmlspecialchars($class). '">';
        $ret .= htmlspecialchars($body);
        $ret .= '</textarea>';
        $ret .= '</div>';
    } elseif (! $end) {
        return "<p>highlight(): the language, $language, is not supported.</p>";
    }
    
    if ($end) {
        $tags = array();
        $tags[] = '<script type="text/javascript" src="' . PLUGIN_HIGHLIGHT_URI . 'Scripts/shCore.js"></script>';
        foreach (array_keys($languages) as $language) {
            $tags[] = '<script type="text/javascript" src="' . PLUGIN_HIGHLIGHT_URI . 'Scripts/' . $jssrc[$language] . '"></script>';
        }
        $tags[] = '<script type="text/javascript">';
        $tags[] = 'dp.SyntaxHighlighter.ClipboardSwf = "' . PLUGIN_HIGHLIGHT_URI . 'Scripts/clipboard.swf";';
        $tags[] = 'dp.SyntaxHighlighter.HighlightAll("code");';
        $tags[] = '</script>';
        return implode($tags, "\n");
    } 
    return $ret;
}
?>
