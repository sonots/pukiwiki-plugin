<?php
// Label for $defaultpage
define('PLUGIN_TOPICPATH_TOP_LABEL', $defaultpage);
 
// Separetor / of / topic / path
define('PLUGIN_TOPICPATH_TOP_SEPARATOR', ' &gt; ');

function plugin_whiteflow_topicpath_convert()
{
	return	'<div id="topic-path">'."\n".
			"<h2>現在の位置</h2>\n".
			'<p>' . plugin_whiteflow_topicpath_inline() ."</p>\n".
			"</div>\n";
}

function plugin_whiteflow_topicpath_inline()
{
	global $vars, $defaultpage, $title, $disp_mode; // disp_mode ?
	if (($title == $defaultpage) || ($vars["page"] == $defaultpage)){
		$val = '<a href="' . get_script_uri() . '">トップページ</a>';
		return $val. $disp_mode;
	}	
	if (! exist_plugin_inline('topicpath')) return;
	$args = func_get_args();
	$val = call_user_func_array('plugin_topicpath_inline', $args);
	return $val. $disp_mode;
}
?>