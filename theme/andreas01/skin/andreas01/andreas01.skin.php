<?php
/**
 * Andreas01 for PukiWiki Skin basend on WP-Andreas01
 *
 * @author     sonots (originally andreas)
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GPL 2
 * @version    $Id: andreas01.skin.php v1.0 2007-12-27 10:44:48Z sonots $
 * @link       http://lsx.sourceforge.jp/?Skin%2Fandreas01
 * @link       http://themes.wordpress.net/columns/3-columns/704/wp-andreas01-12/
 */

// ------------------------------------------------------------
// Setting

// Set image
$frontphoto = SKIN_URI . 'andreas01/' . 'front.jpg';
$favicon = '';
$page_subtitle = '';
$adminlink = substr(get_script_uri(), 0, strrpos(get_script_uri(), '/')) . '/' . 'edit.php?' . rawurlencode($vars['page']);
$navi_plugin = 'navibar'; // 'navibar', 'toolbar', 'navibar2'
$navibar_arg = 'list,search,recent,help,|,new,edit,upload,freeze,rename,backup,diff';
$toolbar_arg = 'list,search,recent,help,new,edit,upload,freeze,rename,backup,diff';
// $page_title   = ''; // pukiwiki.ini.php
// $modifier     = ''; // pukiwiki.ini.php
// $modifierlink = ''; // pukiwiki.ini.php
// $foot_tag .= '';
// $head_tag .= '';

// ------------------------------------------------------------
// Code

if (! defined('UI_LANG')) die('UI_LANG is not set');
if (! isset($_LANG)) die('$_LANG is not set');
if (! defined('PKWK_READONLY')) die('PKWK_READONLY is not set');

// MenuBar & SideBar
$menu = exist_plugin_convert('menu') ? do_plugin_convert('menu') : '';
$side = exist_plugin_convert('side') ? do_plugin_convert('side') : '';
$headarea = exist_plugin_convert('headarea') ? do_plugin_convert('headarea') : '';
$footarea = exist_plugin_convert('footarea') ? do_plugin_convert('footarea') : '';

// NaviBar
if (defined('EDIT_OK') && EDIT_OK) {
    switch ($navi_plugin) {
    case 'navibar2':
        if (exist_plugin_convert('navibar2') && plugin_navibar2_search_navipage($vars['page']) != '') {
            $navi = do_plugin_convert('navibar2');
            if (exist_plugin('statichtml') && $is_read) { // PukiWiki Static!
                $statichtml = new PluginStatichtml();
                $htmllink = '<a rel="nofollow" href="' . $statichtml->get_dump_url($vars['page'])  . '">HTML</a>';
                $publishlink = '<a rel="nofollow" href="' . get_script_uri() . '?cmd=statichtml&amp;page=' . $vars['page'] . '">Publish</a>';
                $navi = str_replace('</tr>', '<td class="navimenu">' . $htmllink . '</td><td class="navimenu">'. $publishlink . '</td></tr>', $navi);
            }
        }
        break;
    case 'navibar':
        if (exist_plugin_convert('navibar')) {
            $navi = do_plugin_convert('navibar', $navibar_arg);
            if (exist_plugin('statichtml') && $is_read) { // PukiWiki Static!
                $statichtml = new PluginStatichtml();
                $htmllink = '<a rel="nofollow" href="' . $statichtml->get_dump_url($vars['page'])  . '">HTML</a>';
                $publishlink = '<a rel="nofollow" href="' . get_script_uri() . '?cmd=statichtml&amp;page=' . $vars['page'] . '">Publish</a>';
                $navi = str_replace('</div>', ' [ ' . $htmllink . ' | ' . $publishlink . ' ] ' . '</div>', $navi);
            }
        }
        break;
    case 'toolbar':
        if (exist_plugin_convert('toolbar')) {
            $navi = do_plugin_convert('toolbar', $toolbar_arg);
            if (exist_plugin('statichtml') && $is_read) { // PukiWiki Static!
                $statichtml = new PluginStatichtml();
                $htmllink = '<a rel="nofollow"  href="' . $statichtml->get_dump_url($vars['page'])  . '"><img width="20" height="20" title="HTML" alt="HTML" src="' . IMAGE_URI . 'reload.png" /></a>';
                $publishlink = '<a rel="nofollow" href="' . get_script_uri() . '?cmd=statichtml&amp;page=' . $vars['page'] . '"><img width="20" height="20" title="Publish" alt="Publish" src="' . IMAGE_URI . 'copy.png" /></a>';
                $navi = str_replace('</div>',  $htmllink . $publishlink . '</div>', $navi);
            }
        }
        break;
    }
}


// Title
if ($is_read) {
    if ($newtitle) {
        $display_title = $newtitle . ' - ' . $page_title;
        $heading_title = make_pagelink($vars['page'], $newtitle);
    } elseif ($vars['page'] == $defaultpage) {
        $display_title = $page_title;
        $heading_title = make_pagelink($vars['page'], $page_title);
    } else {
        $display_title = htmlspecialchars($vars['page']) . ' - ' . $page_title;;
        $heading_title = make_pagelink($vars['page'], get_short_pagename($vars['page']));
    }
} else {
    $display_title = $title . ' - ' . $page_title;
    $heading_title = $title;
}


// Footer
$siteadmin = ! empty($modifierlink) && ! empty($modifier) ?
	'Site admin: <a href="' . $modifierlink . '">' . $modifier . '</a>' : '';

// HTML DTD
ob_start();
if (isset($pkwk_dtd)) {
	$meta_content_type = pkwk_output_dtd($pkwk_dtd);
} else {
	$meta_content_type = pkwk_output_dtd();
}
$pkwk_output_dtd = ob_get_contents();
ob_end_clean();
//if (CONTENT_CHARSET == 'UTF-8' && ereg("MSIE (3|4|5|6)", getenv("HTTP_USER_AGENT"))) {
    // remove <?xml ... > for IE6
    $pkwk_output_dtd = preg_replace("/<\?xml.*\?>\n/", '', $pkwk_output_dtd);
//}

// ------------------------------------------------------------
// Output

// HTTP headers
pkwk_common_headers();
header('Cache-control: no-cache');
header('Pragma: no-cache');
header('Content-Type: text/html; charset=' . CONTENT_CHARSET);

// HTML DTD
echo $pkwk_output_dtd;
?>

<head>
 <?php echo $meta_content_type ?>
<?php if ($nofollow || ! $is_read)  { ?> <meta name="robots" content="NOINDEX,NOFOLLOW" /><?php } ?>
 <meta http-equiv="Content-Script-Type" content="text/javascript" />
 <title><?php echo $display_title ?></title>
<?php if ($favicon) echo ' <link rel="shortcut icon" href="' . $favicon . '" type="image/x-icon" />' ?>
 <link rel="stylesheet" type="text/css" media="screen" href="<?php echo SKIN_URI ?>andreas01/andreas01.css" />
 <link rel="stylesheet" type="text/css" media="print" href="<?php echo SKIN_URI ?>andreas01/andreas01.print.css" />
 <link rel="alternate" type="application/rss+xml" title="RSS" href="<?php echo $_LINK['rss'] ?>" />
 <script type="text/javascript">
 <!--
<?php if (exist_plugin_convert('js_init')) echo do_plugin_convert('js_init'); ?>
 // -->
 </script>
 <script type="text/javascript" src="<?php echo SKIN_URI.'lang/'.$language ?>.js"></script>
 <script type="text/javascript" src="<?php echo SKIN_URI ?>default.js"></script>
 <script type="text/javascript" src="<?php echo SKIN_URI ?>ajax/textloader.js"></script>
 <script type="text/javascript" src="<?php echo SKIN_URI ?>ajax/glossary.js"></script>
<?php if (! $use_local_time) { ?>
 <script type="text/javascript" src="<?php echo SKIN_URI ?>tzCalculation_LocalTimeZone.js"></script>
<?php } ?>
<?php echo $head_tag ?>
</head>
<body>
<div id="wrap">
 <div id="header">
  <h1><a href="<?php echo get_script_uri() ?>"><?php echo $page_title; ?></a></h1>
  <?php if ($page_subtitle) { ?><div id="subtitle"><?php echo $page_subtitle ?></div><?php } ?>
 </div>
 <img id="frontphoto" src="<?php echo $frontphoto ?>" width="760" height="175" alt="" />
 <?php if ($headarea) echo $headarea ?>
 <?php if ($navi) echo $navi ?>

 <?php if ($menu) echo '<div id="leftside">' . $menu . '</div>' ?>
 <?php if ($vars['cmd'] != 'edit' && $side) { ?>
 <div id="extras"><?php echo $side ?></div>
 <div id="content">
 <?php } else { ?>
 <div id="contentwide">
 <?php } ?>
  <div class="post">
    <h2 class="firstHeading"><?php echo $heading_title ?></h2>
    <?php echo $body ?><?php echo $notes ?>
  </div>
 </div>

 <div id="footer">
  <?php if ($footarea) echo $footarea ?>
  <p>
   <?php if ($siteadmin) { ?><span class="credits"><?php echo $siteadmin ?></span><br /><?php } ?>
   Powered by <a href="http://pukiwiki.cafelounge.net/plus/">PukiWiki Plus!</a> 
   - Theme design by <a href="http://lsx.sourceforge.jp/index.php?Skin%2Fandreas01">Andreas01 for PukiWiki</a>
   <?php if ($adminlink) { ?>- <a href="<?php echo $adminlink ?>">Admin</a><?php } ?>
  </p>
  <?php echo $foot_tag ?>
 </div>
</div>
</body>
</html>
