<?php
exist_plugin('wikinote'); // require_once
$plugin = new PluginWikinote();
$plugin->default_options['prefix']       = 'Note/';
$plugin->default_options['except']       = '^$';
$plugin->default_options['title_prefix'] = 'ノート:'; //文字コードをPukiWikiに合わせて保存してください
?>
