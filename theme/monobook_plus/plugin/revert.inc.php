<?php
/**
 * @author     lunt
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GPL 2
 * @version    $Id: revert.inc.php 294 2007-11-03 23:28:43Z lunt $
 */

define('PLUGIN_REVERT_USE_ADMIN_ONLY', FALSE);

function plugin_revert_init()
{
	$messages['_revert_messages'] = array(
		'adminpass'   => '<p>管理者パスワードを入力してください。</p>',
		'invalidpass' => '<p>管理者パスワードが一致しません。</p>',
		'caution'     => '<p><strong>警告: あなたはこのページの古い版を編集しています。' .
			'もしこの文章を保存すると、この版以降に追加された全ての変更が無効になってしまいます。</strong></p>',
		'button'      => '送信',
	);
	set_plugin_messages($messages);
}

function plugin_revert_action()
{
	global $vars, $post, $_revert_messages, $_msg_preview;

	$pass = isset($post['pass']) ? $post['pass'] : FALSE;
	$page = isset($vars['page']) ? $vars['page'] : '';
	$age  = isset($vars['age']) ? $vars['age'] : '';

	if ($page === '') return;
	if (PLUGIN_REVERT_USE_ADMIN_ONLY && $pass === FALSE)
		return array('msg' => 'revert plugin', 'body' => plugin_revert_auth($page, $age));
	if (PLUGIN_REVERT_USE_ADMIN_ONLY && ! pkwk_login($pass))
		return array('msg' => 'revert plugin', 'body' => $_revert_messages['invalidpass']);

	if ($age) {
		// get_backup($page, $age)の形式だと最後の世代だけ取得できず全世代取得になる
		$backups = get_backup($page);
		if (empty($backups[$age]['data']))
			return array('msg' => 'revert plugin', 'body' => 'Backup file not found.');
		$revertdata = $backups[$age]['data'];
		unset($backups);
	} else {
		$filename = DIFF_DIR . encode($page) . '.txt';
		if (! file_exists($filename))
			return array('msg' => 'revert plugin', 'body' => 'Diff file not found.');
		$revertdata = array();
		foreach (file($filename) as $line)
			if ($line[0] !== '+') $revertdata[] = substr($line, 1);
	}

	$vars['preview'] = $post['preview'] = 1;
	$vars['msg']     = $post['msg']     = join('', $revertdata);
	$vars['digest']  = $post['digest']  = is_page($page) ? md5(join('', get_source($page))) : FALSE;
	$_msg_preview    = $_revert_messages['caution'] . "<br />\n" . $_msg_preview;

	return do_plugin_action('edit');
}

function plugin_revert_auth($page, $age)
{
	global $_revert_messages;

	$script = get_script_uri();
	$s_page = htmlspecialchars($page);
	$s_age  = htmlspecialchars($age);

	return <<<EOD
{$_revert_messages['adminpass']}
<form action="$script" method="post">
 <div>
  <input type="hidden"   name="cmd"  value="revert" />
  <input type="hidden"   name="page" value="$s_page" />
  <input type="hidden"   name="age"  value="$s_age" />
  <input type="password" name="pass" size="12" />
  <input type="submit"   value="{$_revert_messages['button']}" />
 </div>
</form>
EOD;
}

function plugin_revert_getlink()
{
	global $vars, $plugin, $cantedit;
	static $link;

	if (isset($link)) return $link;

	$page = isset($vars['page']) ? $vars['page'] : '';
	$age  = isset($vars['age']) ? (int)$vars['age'] : 0;
	$link = '';

	if ($page && ! in_array($page, $cantedit) &&
		(($plugin === 'backup' && $age > 0) || ($plugin === 'diff') || ($plugin === 'revert')))
	{
		$link = get_script_uri() . '?cmd=revert&amp;page=' . rawurlencode($page) .
			($age ? '&amp;age=' . $age : '');
	}

	return $link;
}
?>
