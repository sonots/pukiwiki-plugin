<?php
/**
 * Unlike PukiWiki Skin
 *
 *  originally based on cloudwalk.skin.php by ari
 *  reference: Monobook for PukiWiki skin by lunt
 *
 * @author     sonots <http://note.sonots.com>
 * @license    http://www.gnu.org/licenses/gpl.html GPLv2
 * @link       http://lsx.sourceforge.jp/?Skin%2Funlike
 * @version    $Id:$
 */ 
// ------------------------------------------------------------
// Setting

// Set wikinote plugin
$wikinote_ini = array('prefix' => 'Note/', 'except' => '^$');
$wikinote_autocreate_notepage = false;
$wikinote_notepage_prefix_title = make_link('&multilang(en){Comment};&multilang(ja){コメント};');
$wikinote_mainpage_tabs = array(
     array('cmd'=>'main', 'label'=>make_link('&multilang(en){Article};&multilang(ja){本文};')),
     array('cmd'=>'note', 'label'=>make_link('&multilang(en){Comment};&multilang(ja){コメント};')),
);
$wikinote_notepage_tabs = array(
     array('cmd'=>'main', 'label'=>make_link('&multilang(en){Article};&multilang(ja){本文};')),
     array('cmd'=>'note', 'label'=>make_link('&multilang(en){Comment};&multilang(ja){コメント};')),
     array('cmd'=>'edit', 'label'=>make_link('&multilang(en){Edit};&multilang(ja){編集};'), 'href'=>'?cmd=edit&amp;page='),
     array('cmd'=>'diff', 'label'=>make_link('&multilang(en){Diff};&multilang(ja){差分};'), 'href'=>'?cmd=diff&amp;page='),
);

// Decide charset for CSS
// $css_charset = 'iso-8859-1';
switch(UI_LANG){
     case 'ja_JP': $css_charset = 'Shift_JIS'; break;
     default: $css_charset = 'utf-8'; break;
}

// ------------------------------------------------------------
// Code

if (! defined('DATA_DIR')) die('DATA_DIR is not set');
if (! defined('UI_LANG')) die('UI_LANG is not set');
if (! isset($_LANG)) die('$_LANG is not set');
if (! defined('PKWK_READONLY')) die('PKWK_READONLY is not set');
if (! exist_plugin('monobook_navigation')) die('monobook_navigation plugin not found');

// Background color
global $rule_page, $whatsdeleted, $interwiki;
$background = '';
if (
	empty($vars['page']) ||
	substr($vars['page'], 0, strlen($wikinote_prefix)) === $wikinote_prefix ||
	in_array($vars['page'], array($help_page, $rule_page, $whatsnew, $whatsdeleted, $interwiki))) {
		$background = ' class="specialbg"';
}

// Title
if ($is_read) {
    if ($newtitle) {
        $display_title = $newtitle . ' - ' . $page_title;
        $heading_title = make_pagelink($vars['page'], $newtitle); 
    } elseif (substr($vars['page'], 0, strlen($wikinote_ini['prefix'])) === $wikinote_ini['prefix']) {
        $wikinote_title = $wikinote_notepage_prefix_title . ':' . substr(strstr($vars['page'], '/'), 1);
        $display_title = $wikinote_title . ' - ' . $page_title;
        $heading_title = make_pagelink($vars['page'], $wikinote_title);
    } elseif ($vars['page'] == $defaultpage) {
        $display_title = $page_title;
        $heading_title = make_pagelink($vars['page'], $page_title);
    } else {
        $display_title = $vars['page'] . ' - ' . $page_title;;
        $heading_title = make_pagelink($vars['page'], get_short_pagename($vars['page']));
    }
} else {      
    $display_title = $title . ' - ' . $page_title;
    $heading_title = $title;
}

// Navigation tab (Wikinote)
$wikinote_navi = '';
if (exist_plugin('wikinote')) {
    $wikinote = new PluginWikinote($wikinote_ini);
    if ($wikinote->is_effect()) { 
        if ($wikinote->is_notepage()) {
            $wikinote_navi = $wikinote->show_tabs($wikinote_notepage_tabs);
        } else {
            $wikinote_navi = $wikinote->show_tabs($wikinote_mainpage_tabs);
        }
        if ($wikinote_autocreate_notepage) $wikinote->autocreate_notepage();
    }
}

// Footer
$lastmodified = empty($lastmodified) ? '' : '<li id="lastmod">Last-modified: ' . $lastmodified . '</li>';
$siteadmin = ! empty($modifierlink) && ! empty($modifier) ?
	'<li>Site admin: <a href="' . $modifierlink . '">' . $modifier . '</a></li>' : '';

// ------------------------------------------------------------
// Output

// HTTP headers
pkwk_common_headers();
header('Cache-control: no-cache');
header('Pragma: no-cache');
header('Content-Type: text/html; charset=' . CONTENT_CHARSET);

// Output HTML DTD, <html>, and receive content-type
if (isset($pkwk_dtd)) {
	$meta_content_type = pkwk_output_dtd($pkwk_dtd);
} else {
	$meta_content_type = pkwk_output_dtd();
}
// Plus! not use $meta_content_type. because meta-content-type is most browser not used. umm...
?>
<head>
 <meta http-equiv="content-type" content="application/xhtml+xml; charset=<?php echo(CONTENT_CHARSET); ?>" />
 <meta http-equiv="content-style-type" content="text/css" />
 <meta http-equiv="content-script-type" content="text/javascript" />
<?php if (! $is_read) { ?>
 <meta name="robots" content="NOINDEX,NOFOLLOW" />
<?php } ?>
 <title><?php echo $display_title ?></title>
 <link rel="stylesheet" href="<?php echo SKIN_URI ?>unlike_left/unlike_left.css" title="unlike" type="text/css" charset="<?php echo $css_charset ?>" />
 <link rel="stylesheet" href="<?php echo SKIN_URI ?>unlike_left/print.css" type="text/css" media="print" charset="<?php echo $css_charset ?>" />
 <link rel="alternate" href="<?php echo $_LINK['mixirss'] ?>" type="application/rss+xml" title="RSS" />
 <link rel="shortcut icon" href="<?php echo IMAGE_URI ?>favicon.ico" type="image/x-icon" />
 <script type="text/javascript">
 <!--
<?php if (exist_plugin_convert('js_init')) echo do_plugin_convert('js_init'); ?>
 // -->
 </script>
<?php global $language,$use_local_time; ?>
 <script type="text/javascript" src="<?php echo SKIN_URI.'lang/'.$language ?>.js"></script>
 <script type="text/javascript" src="<?php echo SKIN_URI ?>default.js"></script>
 <script type="text/javascript" src="<?php echo SKIN_URI ?>ajax/textloader.js"></script>
 <script type="text/javascript" src="<?php echo SKIN_URI ?>ajax/glossary.js"></script>
<?php if (! $use_local_time) { ?>
 <script type="text/javascript" src="<?php echo SKIN_URI ?>tzCalculation_LocalTimeZone.js"></script>
<?php } ?>
<?php
/*<script type="text/javascript" src="<?php echo SKIN_URI ?>kanzaki.js"></script>*/
/*<script type="text/javascript" src="<?php echo SKIN_URI ?>greybox/AmiJS.js"></script>*/
/*<script type="text/javascript" src="<?php echo SKIN_URI ?>greybox/greybox.js"></script>*/
/*<link rel="stylesheet" href="<?php echo SKIN_URI ?>greybox/greybox.css" type="text/css" media="all" charset="<?php echo $css_charset ?>" />*/
?>
<?php echo $head_tag ?>
</head>

<body>
<div id="wrapper"><!-- BEGIN id:wrapper -->

<!-- Navigator  ======================================================= -->
<div id="popUpContainer">
<?php
 if (exist_plugin('navibar2')) {
  echo do_plugin_convert('navibar2');
 } else if (exist_plugin('navibar')) {
  echo do_plugin_convert('navibar','top,list,search,recent,help,|,new,edit,upload,|,trackback');
  echo $hr;
 }
?>
</div>

<!-- Content ========================================================= -->
<div id="main"><!-- BEGIN id:main -->
<div id="wrap_content"><!-- BEGIN id:wrap_content -->
<div id="content"><!-- BEGIN id:content -->

<?php if ($wikinote->is_effect()) { echo $wikinote_navi; } ?>
<?php if ($wikinote->is_effect()) { echo '<div style="clear:both;"></div>'; } ?>

<?php if ($wikinote->is_effect()) { echo '<div id="wrap_body">'; } ?><!-- BEGIN id:wrap_body -->
<div id="body"><!-- BEGIN id:body -->
<h1 class="title"><?php echo $heading_title ?></h1>
<div id="subtitle">
<?php if ($is_read && exist_plugin_inline('topicpath')) { echo '<div class="topicpath">' . do_plugin_inline('topicpath') . '</div>'; } ?>
</div>
<?php echo $body ?>
</div><!-- END id:body -->

<div id="summary"><!-- BEGIN id:summary -->
<?php if ($notes != '') { ?><!-- BEGIN id:note -->
<div id="note">
<?php echo $notes ?>
</div>
<?php } ?><!-- END id:note -->
</div><!--  END id:summary -->

<?php if ($wikinote->is_effect()) { echo '</div>'; } ?><!-- END id:wrap_body -->

</div><!-- END id:content -->
</div><!--  END id:wrap_content -->
</div><!-- END id:main -->

<!-- Menubar ========================================================== -->
<?php if (exist_plugin_convert('menu') && do_plugin_convert('menu') != '') { ?>
<div id="wrap_menubar"><!-- BEGIN id:wrap_menubar -->
<div id="menubar">
<?php echo do_plugin_convert('menu') ?>
</div><!-- END id:menubar -->
</div><!-- END id:wrap_menubar -->
<?php } ?>

<!-- Footer ========================================================== -->
<div id="wrap_footer"><!-- BEGIN id:wrap_footer -->
<div id="footer"><!-- BEGIN id:footer -->
<div id="copyright"><!-- BEGIN id:copyright -->
 <?php echo S_COPYRIGHT ?>.
 <a href="http://lsx.sourceforge.jp/?Skin%2Funlike">Unlike Skin</a>. 
 Powered by PHP <?php echo PHP_VERSION ?>. HTML convert time: <?php echo $taketime ?> sec. <br />
</div><!-- END id:copyright -->
</div><!-- END id:footer -->
</div><!-- END id:wrap_footer -->
<!-- END ============================================================= -->
</div><!-- END id:wrapper -->

</body>
</html>
