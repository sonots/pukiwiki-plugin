<?php
/**
 * Monobook for PukiWiki
 * by lunt
 *
 * Based on original Monobook's MediaWiki skin.
 *
 * @author     lunt
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GPL 2
 * @version    $Id: monobook.skin.php 298 2008-01-03 14:10:30Z lunt $
 */

// ------------------------------------------------------------
// Setting

// Set image
$logo = 'pukiwiki.plus_logo_trans.png';
$favicon = '';

// Select navigation tabs as follows:
/* add, attachlist, attachlistall, backup, copy, diff, edit, filelist, freeze, help, list, new, rdf,
   recent, refer, related, reload, rename, rss, rss10, rss20, search, source, top, trackback, upload, yetlist */
$tabs = array('edit', 'diff');

// Enable paraedit plugin
$enable_paraedit = FALSE;

// Add a external class to external links
$enable_relink = FALSE;

// Enable access counter in footer
$enable_footer_counter = FALSE;

// ------------------------------------------------------------
// Code

if (! defined('UI_LANG')) die('UI_LANG is not set');
if (! isset($_LANG)) die('$_LANG is not set');
if (! defined('PKWK_READONLY')) die('PKWK_READONLY is not set');
if (! exist_plugin('monobook_navigation')) die('monobook_navigation plugin not found');
if (! exist_plugin('wikinote')) die('wikinote plugin not found');

// MenuBar & SideBar
$menu = exist_plugin_convert('menu') ? do_plugin_convert('menu') : '';
$side = exist_plugin_convert('side') ? do_plugin_convert('side') : '';

// wikinote plugin
$wikinote = new PluginWikinote;

// Background color
global $rule_page, $whatsdeleted, $interwiki;
$specialpages = array(
	$help_page,
	$rule_page,
	$whatsnew,
	$whatsdeleted,
	$interwiki
);
$background = (empty($vars['page']) || $wikinote->is_notepage() || in_array($vars['page'], $specialpages)) ?
	' class="specialbg"' : '';

// Login
$login = exist_plugin('monobook_login') ? do_plugin_inline('monobook_login') : '';
$login = exist_plugin('login') ? str_replace('cmd=monobook_login', 'cmd=login', $login) : $login; // would be feasible

// Navigation tab
$navigation_tab = plugin_monobook_navigation($wikinote, $tabs, $background);

// Title
global $_monobook_navigation_messages;
if ($newtitle) {
	$display_title = $newtitle;
} else {
	$display_title = $plugin === 'read' ? $wikinote->get_title($vars['page']) : $title;
}

// paraedit.inc.php
if ($enable_paraedit === TRUE && exist_plugin('paraedit'))
	$body = _plugin_paraedit_mkeditlink($body);

// relink.inc.php
if ($enable_relink === TRUE && exist_plugin('relink')) {
	$body = plugin_relink($body);
	$menu = plugin_relink($menu);
	$side = plugin_relink($side);
	$navigation_tab = plugin_relink($navigation_tab);
}

// Footer
$lastmodified = empty($lastmodified) ? '' : '<li id="lastmod">Last-modified: ' . $lastmodified . '</li>';
$siteadmin = ! empty($modifierlink) && ! empty($modifier) ?
	'<li>Site admin: <a href="' . $modifierlink . '">' . $modifier . '</a></li>' : '';
$footer_counter = $enable_footer_counter && exist_plugin('counter') && do_plugin_inline('counter') ?
	'<li>This page has been accessed ' . number_format(do_plugin_inline('counter')) . ' times.</li>' : '';

// ------------------------------------------------------------
// Output

// HTTP headers
pkwk_common_headers();
header('Cache-control: no-cache');
header('Pragma: no-cache');
header('Content-Type: text/html; charset=' . CONTENT_CHARSET);

// HTML DTD
if (isset($pkwk_dtd)) {
	$meta_content_type = pkwk_output_dtd($pkwk_dtd);
} else {
	$meta_content_type = pkwk_output_dtd();
}
?>

<head>
 <?php echo $meta_content_type ?>
<?php if ($nofollow || ! $is_read)  { ?> <meta name="robots" content="NOINDEX,NOFOLLOW" /><?php } ?>
 <meta http-equiv="Content-Script-Type" content="text/javascript" />
 <title><?php echo $display_title ?> - <?php echo $page_title ?></title>
<?php if ($favicon) echo ' <link rel="shortcut icon" href="' . IMAGE_URI . $favicon . '" type="image/x-icon" />' ?>
 <link rel="stylesheet" type="text/css" media="screen" href="<?php echo SKIN_URI ?>monobook/monobook.css" />
<?php if ($side) echo ' <link rel="stylesheet" type="text/css" media="screen" href="' . SKIN_URI . 'monobook/monobook.threecolumn.css" />' . "\n" ?>
 <link rel="stylesheet" type="text/css" media="print" href="<?php echo SKIN_URI ?>monobook/monobook.print.css" />
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
<div id="globalWrapper">
 <div id="menubar"><?php echo $menu ?></div>
 <div id="mainColumnWrapper">
  <div id="main-column">
   <?php echo $navigation_tab ?>
   <div id="content"<?php echo $background ?>>
    <h1 class="firstHeading"><?php echo $display_title ?></h1><div id="contentSub"></div>
    <?php echo $body ?><?php echo $notes ?>
   </div>
  </div>
 </div>
 <?php if($side) echo '<div id="sidebar">' . $side . '</div>' ?>
 <div style="clear:both;height:1em;"></div>
 <div id="logo"><a href="<?php echo get_script_uri() ?>" style="background-image: url(<?php echo IMAGE_URI . $logo ?>);"></a></div>
 <div id="personal"><ul><?php echo $login ?></ul></div>
 <div id="footer">
  <div id="f-officialico">
   <a href="http://pukiwiki.cafelounge.net/plus/"><img src="<?php echo IMAGE_URI ?>pukiwiki-plus.png" alt="PukiWikiPlus" /></a>
  </div>
  <div id="f-officialdevico">
   <a href="http://pukiwiki.cafelounge.net/plus/"><img src="<?php echo IMAGE_URI ?>pukiwiki-plus.dev.png" alt="PukiWikiPlus-dev" /></a>
  </div>
  <div id="f-list">
   <ul><?php echo $lastmodified . $siteadmin . $footer_counter ?>
    <li>convert time: <?php echo function_exists('elapsedtime') ? elapsedtime() : $taketime ?> sec</li>
    <li>Powered by PukiWiki</li>
    <li><a href="http://lsx.sourceforge.jp/?monobook">Monobook for PukiWiki Plus! i18n</a></li>
   </ul>
  </div>
  <div style="clear:both;"></div>
 </div>
</div>
</body>
</html>
