<?php
/////////////// From index.php ///////////////////////////

define('SITE_HOME',	'../../../../site/');
define('DATA_HOME',	'../../../../data/');
define('WWW_HOME',  '../../../../www/');
define('ROOT_URI',  '../../../../www/');
define('LIB_DIR',	SITE_HOME . 'lib/');

//require(LIB_DIR . 'pukiwiki.php');
/////////////// LIB_DIR . 'pukiwiki.php' ///////////////////////////
require(LIB_DIR . 'func.php');
require(LIB_DIR . 'file.php');
require(LIB_DIR . 'funcplus.php');
require(LIB_DIR . 'fileplus.php');
require(LIB_DIR . 'plugin.php');
require(LIB_DIR . 'html.php');
require(LIB_DIR . 'backup.php');

require(LIB_DIR . 'convert_html.php');
require(LIB_DIR . 'make_link.php');
require(LIB_DIR . 'diff.php');
require(LIB_DIR . 'config.php');
require(LIB_DIR . 'link.php');
require(LIB_DIR . 'auth.php');
require(LIB_DIR . 'proxy.php');
require(LIB_DIR . 'lang.php');
require(LIB_DIR . 'timezone.php');
require(LIB_DIR . 'log.php');
require(LIB_DIR . 'proxy.cls.php');
require(LIB_DIR . 'auth.cls.php');
require(LIB_DIR . 'netbios.cls.php');
require(LIB_DIR . 'ua/user_agent.cls.php');

if (! extension_loaded('mbstring')) {
	require(LIB_DIR . 'mbstring.php');
}
if (! extension_loaded('gettext')) {
	require(LIB_DIR . 'gettext.php');
} else {
	function N_($message) { return $message; }
	if (! function_exists('bind_textdomain_codeset')) {
		function bind_textdomain_codeset($domain, $codeset) { return; }
	}
}


/////////////////////////////////////////////////
// Init grobal variables

$foot_explain = array();	// Footnotes
$related      = array();	// Related pages
$head_tags    = array();	// XHTML tags in <head></head>
$foot_tags    = array();

/////////////////////////////////////////////////
// Require INI_FILE

define('INI_FILE',  DATA_HOME . 'pukiwiki.ini.php');
$die = '';
if (! file_exists(INI_FILE) || ! is_readable(INI_FILE)) {
	$die .= 'File is not found. (INI_FILE)' . "\n";
} else {
	require(INI_FILE);
}
if ($die) die_message(nl2br("\n\n" . $die));

?>
