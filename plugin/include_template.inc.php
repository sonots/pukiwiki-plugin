<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: include_template.inc.php,v 1.21 Time-stamp: <08/07/23(��) 10:29:41 hata>a> Exp $
//
// Include-page with parameter plugin

//--------
//	| PageA
//	|
//	| // #include_template(PageB)
//	---------
//		| PageB
//		|
//		| // #include_template(PageC)
//		---------
//			| PageC
//			|
//		--------- // PageC end
//		|
//		| // #include_template(PageD)
//		---------
//			| PageD
//			|
//		--------- // PageD end
//		|
//	--------- // PageB end
//	|
//	| #include_template(): Included already: PageC
//	|
//	| // #include_template(PageE)
//	---------
//		| PageE
//		|
//	--------- // PageE end
//	|
//	| #include_template(): Limit exceeded: PageF
//	| // When PLUGIN_INCLUDE_PGE_MAX == 4
//	|
//	|
//-------- // PageA end

// ----

/*-----------------------------------------------
*�ץ饰���� include_template
���ꤷ���ڡ�����ƥ�ץ졼�Ȥˤ��ơ��ѥ�᡼���ꥹ�ȤΥǡ�����ɽ�����롣~
Validation Check, �������ƥ����̤Υ����å��ϳƼ��ǹԤ����ȡ�~

*Usage
 #include_template(�ƥ�ץ졼�ȥڡ���̾) {{
  ����1 = ��1
  ����2 = ��2
  ........
 }}

*�ƥ�ץ졼�ȤΥڡ���
�̾��wiki�ڡ����ǡ��ͤ�ɽ���������{{{����}}}�������롣

*������
** wiki�ε���
 #include_template(template/jusho �ޤ��� html/jusho) {{
 ̾�� = ���Ģ���
 ͹���ֹ� = xxx-yyyy
 ���� = ����ԡߡߡߡߡߡߡߡߡ�
 ���� = 01-1234-4567
 }}

**�ƥ�ץ졼�ȥڡ���1(template/jusho)
|̾��|͹���ֹ�|����|����|
|{{{̾��}}}|{{{͹���ֹ�}}}|{{{����}}}|{{{����}}}|
==noinclude==
���δ֤�template/jusho�ǤΤ�ɽ�����졢plugin�����ߥڡ����ˤ�ɽ������ޤ���
==/noinclude==

**�ƥ�ץ졼�ȥڡ���2(html/jusho)
 <table>
  <tr><td>̾��</td><td>͹���ֹ�</td><td>����</td><td>����</td></tr>
  <tr><td>{{{̾��}}}</td><td>{{{͹���ֹ�}}}</td><td>{{{����}}}</td><td>{{{����}}}</td></tr>
 </table>
==noinclude==
���δ֤�template/jusho�ǤΤ�ɽ�����졢plugin�����ߥڡ����ˤ�ɽ������ޤ���
==/noinclude==

-----------------------------------------------------*/
// 
define('PLUGIN_INCLUDE_TEMPLATE_IS_EDIT_AUTH' , FALSE);     // Default: TRUE

// Default value of 'title|notitle' option
define('PLUGIN_INCLUDE_TEMPLATE_WITH_TITLE', FALSE);	// Default: FALSE(notitle)

// Max pages allowed to be included at a time

// �ѹ� Time-stamp: <07/02/16(��) 17:20:52 hata>
//define('PLUGIN_INCLUDE_MAX', 4);
define('PLUGIN_INCLUDE_TEMPLATE_MAX', 100);

// usage
define('PLUGIN_INCLUDE_TEMPLATE_USAGE', '#include_template(): Usage: (a-page-name-you-want-to-include[,title|,notitle])[{{key=value ...}}]');
// command usage
define('PLUGIN_INCLUDE_TEMPLATE_CMD_USAGE', 'include_template command Usage: http://your-wiki-site/?cmd=include_template&template=a-page-name-you-want-to-include&id=data-identifier');


// ���󥯥롼�ɤ�ػߤ���ڡ���������ɽ���Ǥ������������
define('PLUGIN_INCLUDE_TEMPLATE_PROTECT' , 'FrontPage');

// �ѥ�᡼������html�Υ�������Ĥ��뤫�ɤ�����
define('PLUGIN_INCLUDE_TEMPLATE_ALLOW_TAG' , TRUE);        // Default: FALSE(���Ĥ��ʤ�)

define('PLUGIN_INCLUDE_TEMPLATE_LDELIM' , '{{{');        // ��¦�ǥ�ߥ�
define('PLUGIN_INCLUDE_TEMPLATE_RDELIM' , '}}}');        // ��¦�ǥ�ߥ�
define('PLUGIN_INCLUDE_TEMPLATE_RAW_KW_DELIM' , '%');    // �ѥ�᡼���ꥹ�ȤǤ����ִ�����ʸ���Υǥ�ߥ�


// ���ޥ�ɷ��ǻ��Ѥ���Ȥ����ִ�����ǡ������Ǽ����ڡ���
define('PLUGIN_INCLUDE_TEMPLATE_DATA_PAGE' , ':config/plugin/include_template/data');   

// ��Ƭ����PHP(php)�Υڡ�����php�����ɤȤ��Ƽ¹Ԥ���Ĥ��뤫�ɤ���
// �ɲ� Time-stamp: <08/07/19(��) 14:51:41 kahata>
define('PLUGIN_INCLUDE_TEMPLATE_ALLOW_EVAL' , FALSE);   // Default: FALSE(���Ĥ��ʤ�)

function plugin_include_template_action()
{
	global $script, $vars, $get, $post, $menubar, $_msg_include_restrict;
	static $included = array();
	static $count = 1;

	$allow_tag = PLUGIN_INCLUDE_TEMPLATE_ALLOW_TAG;
	$data_page = PLUGIN_INCLUDE_TEMPLATE_DATA_PAGE;
	$include_template_is_edit_auth = PLUGIN_INCLUDE_TEMPLATE_IS_EDIT_AUTH;
	$allow_tag  = PLUGIN_INCLUDE_TEMPLATE_ALLOW_TAG;
	$include_template_protect = PLUGIN_INCLUDE_TEMPLATE_PROTECT;
	$allow_eval = PLUGIN_INCLUDE_TEMPLATE_ALLOW_EVAL;

	$href  = 'javascript:history.go(-1)';
	$ret = "<a href=\"$href\">[���]</a><br><p/>";
	$body = $ret;

	$include_template = new include_template();

	// $menubar will already be shown via menu plugin
	if (! isset($included[$menubar])) $included[$menubar] = TRUE;

	// Loop yourself
	$root = isset($vars['page']) ? $vars['page'] : '';
	$included[$root] = TRUE;


	// Get arguments
	// strip_bracket() is not necessary but compatible
	$page = isset($vars['template']) ? get_fullname(strip_bracket($vars['template']), $root) : '';
	if ($page == '') {
		$err_msg = $ret . PLUGIN_INCLUDE_TEMPLATE_CMD_USAGE . '<br />' . "\n";
		return array('msg'=>  'INCLUDE TEMPLATE ERROR !','body'=> $err_msg);
	}

	if (! is_page($page)) {
		$err_msg = $ret . 'include_template: No such page: ' . $page . '<br />' . "\n";
		return array('msg'=>  'TEMPLATE READ ERROR !','body'=> $err_msg);
	} 
	if ($include_template_is_edit_auth) {
		if (! (PKWK_READONLY > 0 or is_freeze($page) or $include_template->is_edit_auth($page))) {
			$err_msg = $ret . "<p>Template page, $page, must be edit_authed or frozen or whole system must be PKWK_READONLY.</p>" . "\n";
			return array('msg'=> 'INCLUDE TEMPLATE ERROR !','body'=> $err_msg);
		}
	}

	$id = isset($vars['id']) ? $vars['id'] : '';

	// I'm stuffed
	if (isset($included[$page])) {
		$err_msg = $ret . 'include_template(): Included already: ' . $page . '<br />' . "\n";
		return array('msg'=> 'INCLUDE TEMPLATE ERROR !','body'=> $err_msg);
	} if(preg_match("/$include_template_protect/" , $page)){
		$err_msg = $ret . 'include_template(): Can\'t include protected page: '. $page .'<br />' . "\n";
		return array('msg'=> 'INCLUDE TEMPLATE ERROR !','body'=> $err_msg);
	} if (! is_page($page)) {
		$err_msg = 'include_template(): No such page: ' . $page . '<br />' . "\n";
		return array('msg'=>  'INCLUDE TEMPLATE ERROR !','body'=> $err_msg);
	} if ($count > PLUGIN_INCLUDE_TEMPLATE_MAX) {
		$err_msg = 'include_template(): Limit exceeded: ' . $page . '<br />' . "\n";
		return array('msg'=>  'INCLUDE TEMPLATE ERROR !','body'=> $err_msg);
	} else {
		++$count;
	}

	// Include A page, that probably includes another pages
	$get['page'] = $post['page'] = $vars['page'] = $page;

	if (check_readable($page, false, false)) {
		$output = join('', get_source($page));

		$lines = get_source($data_page); 
		for($i= 0; $i < count($lines); $i++) {
        		$value = $include_template->get_values($lines[$i], $delim1 = "<>", $delim2 = '=');
    			if ($value['id'] == $id ) {
			// ��������ִ���������Time-stamp: <07/06/16(��) 10:18:17 kahata>
				foreach ($include_template->kw_replace as $key => $val) {
					$output = str_replace($key,$val, $output);
				}
			$title1  = $value['title'];
			$output = $include_template->param_replace($output, $value);
			}
		}
		if(preg_match("/^:?html.+/" , $page)){
		     $body .= str_replace("#freeze",'', $output);

		// PHP/page��php������ɾ����Time-stamp: <08/07/15(��) 17:55:30 hata>
		} else if(preg_match("/^php.+/" , strtolower($page)) && $allow_eval){
			$output = str_replace("#freeze",'', $output);
			$output = str_replace('<?php','', $output);
			$output = str_replace('?>','', $output);
    		ob_start();
			eval($output);
    		$body = ob_get_contents();
    		ob_end_clean();

		} else {
 	  	$body .= $allow_tag ? convert_html($output) : convert_html(htmlspecialchars($output));
		}
	} else {
		$body = str_replace('$1', $page, $_msg_include_restrict);
	}
	$get['page'] = $post['page'] = $vars['page'] = $root;

	return array(
		'msg'=>  $title1,
		'body'=> $body
	);
}

function plugin_include_template_convert()
{
	global $script, $vars, $get, $post, $menubar, $_msg_include_restrict;
	static $included = array();
	static $count = 1;

	$allow_eval = PLUGIN_INCLUDE_TEMPLATE_ALLOW_EVAL;

	$include_template = new include_template();

	$include_template_is_edit_auth = PLUGIN_INCLUDE_TEMPLATE_IS_EDIT_AUTH;

	if (func_num_args() == 0) return PLUGIN_INCLUDE_TEMPLATE_USAGE . '<br />' . "\n";;

	// $menubar will already be shown via menu plugin
	if (! isset($included[$menubar])) $included[$menubar] = TRUE;

	// Loop yourself
	$root = isset($vars['page']) ? $vars['page'] : '';
	$included[$root] = TRUE;

	// Get arguments
	$args = func_get_args();

	// strip_bracket() is not necessary but compatible
	$page = isset($args[0]) ? get_fullname(strip_bracket(array_shift($args)), $root) : '';

//	if ($include_template_is_edit_auth || preg_match("/^:?html.+/" , $page)) {
// (��)
	if ($include_template_is_edit_auth) {
		if (! (PKWK_READONLY > 0 or is_freeze($page) or $include_template->is_edit_auth($page))) {
		return "<p>include_template(): Template page, $page, must be edit_authed or frozen or whole system must be PKWK_READONLY.</p>";
		}
	}

	$params = array_pop($args);

	$with_title = PLUGIN_INCLUDE_TEMPLATE_WITH_TITLE;
	$allow_tag  = PLUGIN_INCLUDE_TEMPLATE_ALLOW_TAG;

	if ($params != '') {
		switch(strtolower($params)) {
		case 'title'  : $with_title = TRUE;  break;
		case 'notitle': $with_title = FALSE; break;
		default       :
		// �ѹ� Time-stamp: <08/07/19(��) 15:27:42 kahata>
		    if (substr($source, -1) != "\r") {
        		$value = $include_template->get_values($params, $delim1 = "<>", $delim2 = '=');
				break;
			} else {
				$value = $include_template->get_values($params);
				break;
			}
		}
	}

	if (isset($args[0])) {
		switch(strtolower(array_shift($args))) {
		case 'title'  : $with_title = TRUE;  break;
		case 'notitle': $with_title = FALSE; break;
		}
	}

	$s_page = htmlspecialchars($page);
	$r_page = rawurlencode($page);
	$link = '<a href="' . $script . '?' . $r_page . '">' . $s_page . '</a>'; // Read link

	// I'm stuffed
	$include_template_protect = PLUGIN_INCLUDE_TEMPLATE_PROTECT;

	if (isset($included[$page])) {
		return '#include_template(): Included already: ' . $link . '<br />' . "\n";
	} if(preg_match("/$include_template_protect/" , $page)){
		return '#include_template(): Can\'t include protected page: '. $page .'<br />' . "\n";
	} if (! is_page($page)) {
		return '#include_template(): No such page: ' . $s_page . '<br />' . "\n";
	} if ($count > PLUGIN_INCLUDE_TEMPLATE_MAX) {
		return '#include_template(): Limit exceeded: ' . $link . '<br />' . "\n";
	} else {
		++$count;
	}

	// One page, only one time, at a time
//	$included[$page] = TRUE;

	// Include A page, that probably includes another pages
	$get['page'] = $post['page'] = $vars['page'] = $page;

	if (check_readable($page, false, false)) {
		$output = join('', get_source($page));

		// ��������ִ���������Time-stamp: <07/06/16(��) 10:18:17 kahata>
		foreach ($include_template->kw_replace as $key => $val) {
			$output = str_replace($key,$val, $output);
		}
		$output = $include_template->param_replace($output, $value);
		if(preg_match("/^:?html.+/" , strtolower($page))){
			$body = str_replace("#freeze",'', $output);

		// PHP/page��php������ɾ����Time-stamp: <08/07/15(��) 17:55:30 hata>
		} else if(preg_match("/^php.+/" , strtolower($page)) && $allow_eval){
			$output = str_replace("#freeze",'', $output);
			$output = str_replace('<?php','', $output);
			$output = str_replace('?>','', $output);
    		ob_start();
			eval($output);
    		$body = ob_get_contents();
    		ob_end_clean();

		} else {
 			$body = $allow_tag ? convert_html($output) : convert_html(htmlspecialchars($output));
		}
	} else {
		$body = str_replace('$1', $page, $_msg_include_restrict);
	}
	$get['page'] = $post['page'] = $vars['page'] = $root;

	// Put a title-with-edit-link, before including document
	if ($with_title) {
		$link = '<a href="' . $script . '?cmd=edit&amp;page=' . $r_page .
			'">' . $s_page . '</a>';
		if ($page == $menubar) {
			$body = '<span align="center"><h5 class="side_label">' .
				$link . '</h5></span><small>' . $body . '</small>';
		} else {
			$body = '<h1>' . $link . '</h1>' . "\n" . $body . "\n";
		}
	}
	return $body;
}

///////////////////////////////////////
// include_template class
class include_template
{
	// ���������ִ�����ʸ����Ϣ���������� Time-stamp: <07/06/16(��) 10:14:00 kahata>
	var $kw_replace = array(
				"�СС�" => '{{{',
				"�ѡѡ�" => '}}}',
				"#hide(on)" => '',
				"#hide(off)" => '',
				"//==noinclude==" => '==noinclude==',
				"//==/noinclude==" => '==/noinclude==',
				"//==onlyinclude==" => '==onlyinclude==',
				"//==/onlyinclude==" => '==/onlyinclude==');

	// Ϣ������ʥϥå���ˤ��Ѥ����ִ�����
	function param_replace($output, $value)
	{	
		reset($value);

		for ($i=0;$i<count($value);$i++){
       			$k = key($value);
//			$pattern = "{{{" . $k . "}}}";
			$pattern = PLUGIN_INCLUDE_TEMPLATE_LDELIM . $k . PLUGIN_INCLUDE_TEMPLATE_RDELIM;


		// bug fix Time-stamp: <07/04/30(��) 08:31:06 kahata>
       			$v = $value[$k];
		//	$v = htmlspecialchars($value[$k]);
			$output = str_replace($pattern , $v , $output); // �������ִ�
       			next($value);
		}

		// bug fix Time-stamp: <07/05/20(��) 07:56:18 kahata>
//		$output = ereg_replace("==noinclude==.+==/noinclude==", '' , $output);
		$output = preg_replace("'==noinclude==.+?==\/noinclude=='s","",$output);


		// ==onlyinclude== �� ==/onlyinclude== �ɲ� Time-stamp: <07/06/07(��) 10:34:59 kahata>
		$onlyinclude = preg_match_all("'==onlyinclude==(.+?)==\/onlyinclude=='s",$output,$matches);
		if($onlyinclude){
			$tmp = '';
			for ($i = 0; $i<$onlyinclude; $i++) {
				$tmp = $tmp . $matches[1][$i];
			}
			$output = $tmp;
		}

		return $output;
	}

	// �ѥ�᡼���ꥹ�Ȥ�Ϣ������ؤμ����߽���
	function get_values($params, $delim1 = "\r", $delim2 = '=')
	{
		$delim = PLUGIN_INCLUDE_TEMPLATE_RAW_KW_DELIM;
//		$data   = explode("\r", $params);
                $data   = explode($delim1, $params);

		for($i=0;$i<count($data);$i++){
			$temp = explode($delim2, $data[$i] , 2);
			$key = trim(chop($temp[0]));
			$v = trim(chop($temp[1]));

	//���ִ�����ʸ����ѥ�᡼���ꥹ�Ȥ���Ϣ������˼�����
	// Time-stamp: <07/06/16(��) 10:31:50 kahata>
//			if(preg_match("'$delim(.+?)$delim's",$key,$matches)) {
			if(ereg("$delim(.+)$delim",$key,$matches)) {
    				$this->array_push_associative($this->kw_replace, array($matches[1] => $v));
			} else {
    				$value[$key] = $v;
  			}

		}
		return $value;
	}

	// template page ��ǧ�ڽ���
	function is_edit_auth($page, $user = '')
	{
		global $edit_auth, $edit_auth_pages, $auth_method_type;
		if (! $edit_auth) {
			return FALSE;
		}
		// Checked by:
		$target_str = '';
		if ($auth_method_type == 'pagename') {
			$target_str = $page; // Page name
		} else if ($auth_method_type == 'contents') {
			$target_str = join('', get_source($page)); // Its contents
		}

		foreach($edit_auth_pages as $regexp => $users) {
			if (preg_match($regexp, $target_str)) {
				if ($user == '' || in_array($user, explode(',', $users))) {
					return TRUE;
				}
			}
		}
		return FALSE;
	}

	// Append associative array elements��Time-stamp: <07/06/16(��) 10:19:44 kahata>
	function array_push_associative(&$arr) {
		$args = func_get_args();
		$ret = 0;
		foreach ($args as $arg) {
			if (is_array($arg)) {
				foreach ($arg as $key => $value) {
				$arr[$key] = $value;
				$ret++;
			}
			}else{
				$arr[$arg] = "";
			}
		}
		return $ret;
	}

}
?>