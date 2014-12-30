<?php

function plugin_kisekae_action()
{
	global $vars;
	global $skin_file;

	$page = isset($vars['page']) ? $vars['page'] : '';
	$skin = isset($vars['skin']) ? $vars['skin'] : '';

	$parsed_url = parse_url($script);
	$path = $parsed_url['path'];
	if (($pos = strrpos($path, '/')) !== FALSE) {
		$path = substr($path, 0, $pos + 1);
	}
	if (preg_match('#(^|/)\.\./#', $skin)) {
		return array('msg'=>_('Kisekae'), 
					 'body'=>'<p>Skin file must not include ../</p>');
	} elseif (! preg_match('#\.skin\.php$#', $skin)) {
		return array('msg'=>_('Kisekae'), 
					 'body'=>'<p>Skin file must have .skin.php extension.</p>');
	} elseif (! file_exists(SKIN_DIR . $skin)) {
		return array('msg'=>_('Kisekae'), 
					 'body'=>'<p>Skin file ' . htmlspecialchars($skin) . ' does not exist. </p>');
	}
	setcookie('skin', $skin, 0, $path);
	$_COOKIE['skin'] = $skin; /* To effective promptly */
	// UPDATE
	$skin_file = SKIN_DIR . $skin;

	// Location ヘッダーで飛ばないような環境の場合は、この部分を
	// 有効にして対応下さい。
	// if(exist_plugin_action('read')) return do_plugin_action('read');
	header('Location: ' . get_script_uri() . '?' . rawurlencode($page) );
	exit;
}

function plugin_kisekae_inline()
{
	global $vars;
	$page = isset($vars['page']) ? $vars['page'] : '';

	$args = func_get_args();
	$skin_name = array_pop($args);
	if (count($args) === 0) {
		return 'kisekae(): no argument(s).';
	}
	$skin_file = array_shift($args);
	$skin_name = ($skin_name === '') ? htmlspecialchars($skin_file) : $skin_name;

	if (preg_match('#(^|/)\.\./#', $skin_file)) {
		return 'kisekae(): Skin file must not include ../';
	} elseif (! preg_match('#\.skin\.php$#', $skin_file)) {
		return 'kisekae(): Skin file must have .skin.php extension.';
	} elseif (! file_exists(SKIN_DIR . $skin_file)) {
		return 'kisekae(): Skin file ' . htmlspecialchars($skin_file) . ' does not exist. ';
	}

	$body = '<span class="kisekae">';
	$body .= '<a href="' . get_script_uri() . '?cmd=kisekae&amp;page=' . rawurlencode($page) .
		'&amp;skin=' . rawurlencode($skin_file) . '">' . $skin_name . '</a>';
	$body .= '</span>';
	return $body;
}

?>
