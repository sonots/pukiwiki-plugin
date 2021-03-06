<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: skin.inc.php,v 1.1.4 2006/06/05 02:23:00 miko Exp $
//
function plugin_skin_convert()
{
	global $skin_file;

	if (func_num_args() != 1) {
		return '';
	}
	list($skin_name) = func_get_args();
	$skin_name = basepagename($skin_name);

	$skin_temp = array(
		SKIN_URI . $skin_name . '.skin.php',
		WWW_HOME . SKIN_DIR . $skin_name . '.skin.php',
		// Back compat for Pukiwiki Plus!(1.4.4plus-u2)
		WWW_HOME . SKIN_DIR . $skin_name . '.skin.' . LANG . '.php',
		// sonots
		SKIN_URI . $skin_name . '/' . $skin_name . '.skin.php',
		WWW_HOME . SKIN_DIR . $skin_name . '/' . $skin_name . '.skin.php',
		// Back compat for Pukiwiki Plus!(1.4.4plus-u2)
		WWW_HOME . SKIN_DIR . $skin_name . '/' . $skin_name . '.skin.' . LANG . '.php',
	);
	foreach($skin_temp as $skin) {
		if (file_exists($skin) && is_readable($skin)) {
			$skin_file = $skin;
			return '';
		}
	}

	return '<!-- Cant replace skin. Use default. -->';
}
?>
