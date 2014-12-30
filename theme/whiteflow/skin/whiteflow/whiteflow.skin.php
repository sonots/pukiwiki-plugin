<?php
/**
 * Whiteflow Skin for PukiWiki
 *
 * @author     leva (http://www.geckdev.org)
 * @license    GNU General Public License v2.1
 */

// ------------------------------------------------------------
// Setting
$skin_uri = SKIN_DIR . 'whiteflow/';
$s_license = 'CopyRight &copy; 2007- GNU Free Documentation License.';

// ------------------------------------------------------------
// Code

// Initialization
$v_page		= $vars["page"];
$e_page		= rawurlencode($v_page);
$t_cmd		= $vars["cmd"];
$t_plugin	= $vars["plugin"];
if($t_cmd){
	$command = $t_cmd;
}elseif ($t_plugin){
	$command = $t_plugin;
}
$s_copyright = 'Powered by <strong>PukiWiki ' . S_VERSION . '</strong>' .
	' <a href="http://pukiwiki.sourceforge.jp/">PukiWiki Developers Team</a> (<a href="http://www.gnu.org/licenses/gpl.html">GPL</a>)'.
	' which based on "PukiWiki" 1.3 by <a href="http://factage.com/yu-ji/">yu-ji</a>';

// Classify User-agent
include 'module.classification.inc.php';

// HTML DTD
ob_start();
if (isset($pkwk_dtd)) {
	$meta_content_type = pkwk_output_dtd($pkwk_dtd);
} else {
	$meta_content_type = pkwk_output_dtd();
}
$pkwk_output_dtd = ob_get_contents();
ob_end_clean();
if (ereg("MSIE", $engine)) {
    // remove <?xml ... > for IE6
    $pkwk_output_dtd = preg_replace('/<\?xml[^>]*>\n?/', '', $pkwk_output_dtd);
}

// ------------------------------------------------------------
// Output

// HTTP headers
pkwk_common_headers();
header('Cache-control: no-cache');
header('Pragma: no-cache');
header ("Content-Type: text/html; charset=" . CONTENT_CHARSET);

// HTML DTD
echo $pkwk_output_dtd;
?>

<head profile="http://infomesh.net/2001/uriprofile/">
<?php if (!$is_read) echo '<meta name="robots" content="NOINDEX,NOFOLLOW" />'."\n"; ?>
<meta name="copyright" content="GNU Free Documentation License" />
<?php if ($lastmodified) echo '<meta name="WWWC" content="'.$lastmodified.'" />'."\n";

global $newtitle, $newbase;
if($title == $defaultpage){
	$t_title = sprintf("%s", $page_title);
} elseif ($newtitle && $is_read){ 
	$t_title = sprintf("%s: %s", $page_title, $newtitle);
} else{
	$t_title = sprintf("%s: %s", $page_title, $title);
}
echo "<title>".$t_title."</title>\n";
global $trackback, $referer;
if ($command == "edit"){
	echo '<script type="text/javascript" src="' . $skin_uri . 'js/tab.js"></script>'."\n";}
if ($trackback){
	echo '<script type="text/javascript" src="' . $skin_uri . 'js/trackback.js"></script>'."\n";}?>
<script type="text/javascript" src="<?php echo $skin_uri ?>js/showhide.js"></script>
<script type="text/javascript" src="<?php echo $skin_uri ?>js/stripe.js"></script>
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
<link rel="alternate" type="application/rss+xml" title="<?php echo $page_title;?> 更新情報" href="<?php echo get_script_uri();?>?cmd=rss&amp;ver=1.0" />
<link rel="stylesheet" type="text/css" href="<?php echo $skin_uri ?>css/style.right-menu.css" title="メニュー右配置" media="screen" />
<link rel="alternate stylesheet" type="text/css" href="<?php echo $skin_uri ?>css/style.left-menu.css" title="メニュー左配置" media="screen" />
<link rel="alternate stylesheet" type="text/css" href="<?php echo $skin_uri ?>css/style.no-menu.css" title="メニュー無し" media="screen" />
<link rel="stylesheet" type="text/css" href="<?php echo $skin_uri ?>css/print.css" media="print" />
<?php
if($engine == "MSIE 6" || $engine == "MSIE"){ ?>
	<link rel="stylesheet" type="text/css" href="<?php echo $skin_uri ?>css/fix.trident6.css" media="screen" />
<?php } elseif($engine == "MSIE 7"){ ?>
	<link rel="stylesheet" type="text/css" href="<?php echo $skin_uri ?>css/fix.trident7.css" media="screen" />
<?php } elseif($engine == "Gecko"){ ?>
	<link rel="stylesheet" type="text/css" href="<?php echo $skin_uri ?>css/advanced.css" media="screen" />
	<link rel="stylesheet" type="text/css" href="<?php echo $skin_uri ?>css/fix.gecko.css" media="screen" />
<?php } elseif($engine == "Opera"){ ?>
	<link rel="stylesheet" type="text/css" href="<?php echo $skin_uri ?>css/fix.presto.css" media="screen" />
<?php } elseif($engine == "KHTML Safari"){ ?>
	<link rel="stylesheet" type="text/css" href="<?php echo $skin_uri ?>css/advanced.css" media="screen" />
	<link rel="stylesheet" type="text/css" href="<?php echo $skin_uri ?>css/fix.safari.css" media="screen" />
<?php }
echo $head_tag; ?>
</head>
<body>

<div id="popUpContainer"></div>
<!-- START #containar-->
<div id="wide-container">
<?php
if ($command == "read"){?>
	<div id="container">
<?php }else { ?>
	<div id="container" class="work">
<?php } ?>

<!-- START #header -->
<div id="header">
<h1><a href="<?php echo get_script_uri()."?".$v_page;?>"><?php echo $page_title;?></a></h1>
<p id="description">PukiWikiです、編集はご自由にどうぞ</p>

<div id="selection">
<?php include 'module.search.inc.php';?>
</div>

</div>
<!-- END #header -->

<!-- START #content -->
<div id="content">

<!-- START #content > #additional -->
<div id="additional">
<?php
include 'module.lastmodified.inc.php';
if (!isset($topicpath) && exist_plugin('whiteflow_topicpath')) {
	$topicpath = do_plugin_convert('whiteflow_topicpath');
}
if ($command == "read" || $command == "edit"){
	echo "\n".$topicpath;
}else { ?>
	<div id="topic-path">
	<h2>現在の位置</h2>
	<p><?php echo $command;?> プラグインを使用中</p>
	</div>
<?php } ?>
</div>
<!-- END #content > #additional -->

<!-- START #content > #edit-area -->
<?php
if (arg_check('read') && exist_plugin_convert('menu') && do_plugin_convert('menu')){
	echo '<div id="edit-area" class="display">';
}else { // If on edit
	echo '<div id="edit-area" class="work">';
}
echo "<div id=\"navigator\">\n";
echo $body;

// 注釈
if ($notes) { ?>
<div id="note">
<h3><a href="#note" accesskey="r">注釈</a><span class="accesskey">(<kbd>R</kbd>)</span></h3>
<ol><?php echo $notes; ?></ol>
</div>
<?php }

// 添付ファイルの表示
if ($attaches) { ?>
<h3>添付ファイル一覧</h3>
<dl id="attach"><?php echo $attaches; ?></dl>
<p><a href="<?php echo get_script_uri() ?>?cmd=attach&amp;pcmd=upload&amp;page=<?php echo $e_page ?>">ファイルのアップロード</a></p>
<?php }

// 関連ページ
if ($related) { ?>
<dl id="related">
<dt>関連ページ</dt>
<dd><ol>
<li><?php echo $related; ?></li>
</ol></dd>
</dl>
<?php } ?>

</div>
</div>
<!-- END #content > #edit-area -->

<!-- START #content > #menu -->
<div id="menu">

<?php
include 'module.navigation.inc.php';
echo '<div id="sitemap">'."\n";
if(!isset($menu)){
	$menu = do_plugin_convert('menu');
}
if(CONVERT_CACHE_TYPE == 'all'){
	$obj =& Convert_Cache_Read_All::getInstance();
	$_menu = $obj->mergeMenu($menu);
}else {
	$_menu = $menu;
}
echo $_menu;
?>
</div>

</div>
<!-- END #content > #menu -->

</div>
<!-- END #content -->

<!-- START #footer -->
<?php
if (exist_plugin_convert('footarea') && do_plugin_convert('footarea') != ''){
	echo '<div id="footer">'."\n";
	echo do_plugin_convert('footarea');
	echo '</div>'."\n";
}else {
	// or In this skin?>
<div id="footer">
<ul id="signature">
<li class="inquiry"><a href="<?php echo get_script_uri();?>?">サイトについて</a></li>
<li class="inquiry"><a href="<?php echo get_script_uri();?>?">仕様</a></li>
<li class="request"><a href="<?php echo get_script_uri();?>?">このサイトへの要望</a></li>
<li class="help"><a href="<?php echo get_script_uri() ?>?Help">ヘルプ</a></li>
<li><?php echo $s_license;?></li>
<li><?php echo $s_copyright;?></li>
</ul>
</div>
<!-- END #footer -->
<?php } ?>
</div>
<!-- #END #container -->
</div>
<?php echo $foot_tag;?>
</body>
</html>
<?php
if (CONVERT_CACHE_TYPE == 'all') {
	$obj =& Convert_Cache_Read_All::getInstance();
	switch ($obj->mode) {
		case 'basic':
			$array['def'] = $obj->parseINI('def.ini');
			$array['var'] = $obj->parseINI('var.ini');

			foreach ($array['def'] as $value) {
				$_array['def'][$value] = constant($value);
			}
			foreach ($array['var'] as $value) {
				$_array['var'][$value] = $$value;
			}
			$obj->makeBasicCache($_array);
			break;

		case 'make':
			if (is_page($obj->page)) {
				$obj->parse();
				if ($obj->mode == 'make') {
					$body = $obj->removeBodyStatus($body);
					$array['page'] = $obj->parseINI('page.ini');
					if (! SKIN_DEFAULT_DISABLE_TOPICPATH) {
						$array['page'][] = 'topicpath';
					}

					foreach ($array['page'] as $value) {
						$_array['page'][$value] = $$value;
					}
					$obj->makePageCache($_array['page']);
				}
			}
			break;

		default:
			break;
	}
}
?>
