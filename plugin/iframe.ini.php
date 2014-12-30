<?php
exist_plugin('iframe');
$plugin_iframe = new PluginIframe();
$plugin_iframe->accept_regurl = array(
    '^' . preg_quote('http://www.google.com') . '$',
    '^' . preg_quote('http://pukiwiki.sourceforge.jp/dev/'),
);
$plugin_iframe->accept_url = array(
    'http://pukiwiki.sourceforge.jp',
);
?>
