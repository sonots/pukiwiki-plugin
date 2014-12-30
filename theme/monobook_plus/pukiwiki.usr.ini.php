<?php
/* default.ini.php */
define('SKIN_DIR', WWW_HOME . 'skin/');
define('SKIN_FILE_DEFAULT', SKIN_DIR . 'monobook/monobook.skin.php');

/* pukiwiki.ini.php */
$fixed_heading_anchor = 1;
$fixed_heading_edited = 1;
/* edit.inc.php */
define('PLUGIN_EDIT_PARTAREA', 'level');

// PLUS_ALLOW_SESSION - Allow / Prohibit using Session
// Disable if writing fails BugTrack/95
define('PLUS_ALLOW_SESSION', 0);
?>
