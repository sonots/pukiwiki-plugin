<?php
exist_plugin('dump2html');
$plugin = new PluginDump2html();
$plugin->CONF['REDIRECT_AFTER_DUMP'] = TRUE;
$plugin->CONF['BLOCK_ADMINONLY']     = FALSE;
$plugin->CONF['method']              = 'http';
?>
