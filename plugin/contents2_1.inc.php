<?php 
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: contents2_1.inc.php 137 2006-08-03 15:46:02Z sonots $
/*
*���� [#m17ed6bb]
[[contents>PukiWiki/1.4/�ޥ˥奢��/�ץ饰����/c#vd4dabcd]] �ץ饰����ϼºݤΤȤ���ץ饰����ǤϤʤ��������Ȥ߹��ߤε�ǽ�Ǥ���
��ĥ�����ˤ����Τǥץ饰���󲽤��ޤ�����
�����ǥ���¿����[[����ץ饰����/ls2_1.inc.php]]���餭�Ƥ��ޤ����Ȥ������Ȥ�̾����contents2_1 �Ǥ���

~#contents �򤪤������֤���θ��Ф���ɽ������Ƥ�̵�̤��ʤȻפäƤ����ͤ��Ȥ����⤷��ޤ����㤨�Ф��Υڡ������Ⱦ�� contents2_1.inc.php �Υ�󥯤�����ʤ����ȡ�

MenuBar ��
 #contents2_1(fromhere=false,hierarchy=false)
�Ȥ��Ƥ����Τ��������⤷��ޤ���hierarchy �Ϥ����ߤǡ�

��Ϣ:([[org:�ߤ����ץ饰����/170]])([[org:�ߤ����ץ饰����/91]])([[org:³������Ȣ/221]])([[org:³������Ȣ/220]])([[org:����Ȣ/287]])([[org:�ߤ����ץ饰����/125]])
([[org:����Ȣ/59]])([[org:³������Ȣ/447]])([[org:³������Ȣ/306]])

**���Τ������� [#l67274d6]

���꣱��include ���ץ��������ˤϤǤ��ʤ����Ȥ�ȯ�Ф��ޤ��������󥫡��ֹ�� convert_html ���ƤФ줿�����������ΤǤ�����#contents2_1
�������Ƥ���ΤϷ�ɤΤȤ��� #include �ο��ˤ��������ޤ���
�Ĥޤꡢconvert_html ��Ƥ� include �ʳ��Υץ饰���󤬸ƤФ��Ȥ��äȤ���ޤ���

���ꣲ��#include �ǤȤꤳ�ޤ줿�ڡ�����ɽ������� #contents2_1
�Υ��󥫡���󥯤��ֹ椬�ޤ�������Ϥ��ޤꤦ�ޤ������ޤ��󡣤�����ˤ��Ǥ���

�ͻ�����convert_html ��� static $contents_id �� global �ˤ�����ɤ����ȹͤ��Ƥߤޤ�����������Ǥ���

�ͻ�����plugin_contents2_1_convert �� convert_html ���Ƥ���Ȥ��˰����� $contents_id ���Ϥ�
���Ȥ��Ǥ�����ɤ����ȹͤ��Ƥߤޤ����������Τ褦�� convert_html ���¤�Ǥ����Ȥ��Ƥ⡢���ꣲ��ľ���ޤ������꣱���Ѥ��ޤ���~
���ʤߤ������Ȥ߹��� #contents �� convert_html �� $contents_id ���Ϥ��Ƥ��뤳�Τ褦�ʷ��Ǥ���include ���ץ���󤬤ʤ��ΤǤ���ʾ��Ǻ��ɬ�פ��ʤ��ΤǤ���~
���ꣲ�Τ���ˤ�ꤿ���Ȥ���Ǥ������ϤƤ��ơ�������

�ͻ������Ȥꤢ�������ꥢ�󥫡���ƨ���롣���ߤ� pukiwiki �ϥǥե���Ȥ� $fixed_heading_anchor = 1; �Τ褦�ʤΤ���������ߤ����Ǥ��͡�

�����򡥸��ꥢ�󥫡���ƨ���Ƥ��ޤ����Ȥꤢ������衣

*�� [#jcb5f796]
 #contents2_1([���ץ����])

 &contents2_1([���ץ����]);
����饤�󷿥ץ饰������϶���Ū�� display=inline �Ȥʤ�ޤ���

**���ץ���� [#r8a06bfd]
-page=�ڡ���̾
~���Ф��ꥹ�Ȥ�Ԥ��ڡ�������ꡣ�ǥե���Ȥϥ����ȥڡ�����
-fromhere=true|false
~#contents2_1 ���Τ��뼡�ι԰ʹߤθ��Ф��Τߤ�ꥹ�Ȥ��롣fromhere �����Ǥ� true �ˤʤ�ޤ���~
�ե�������� PLUGIN_CONTENTS2_1_FROMHEARE �ǽ���ͤ�����Ǥ��ޤ����ǥե���Ȥ� TRUE �Ǥ���~
Note: �����Ǥ� #contents2_1 �����Ĥ���Ȱ��־�Τ�Τ�����ȿ�����Ƥ��ޤ��ޤ���
page ���ץ�����ɽ���ڡ����Ȱۤʤ�ڡ�������ꤷ������ȯư���ޤ���
-display=hierarchy|flat|inline
~�ꥹ��ɽ�������λ��ꡣhierarchy �Ǥϸ��Ф��Υ�٥�˱���������Ū�ꥹ��ɽ����
flat �Ǥϸ��Ф��Υ�٥�ˤ�餺ʿ���ɽ����~
�ե�������� PLUGIN_CONTENTS2_1_DISPLAY �ǽ���ͤ�����Ǥ��ޤ����ǥե���Ȥ� hierarchy �Ǥ���~
Note: inline �Ǥϲ������ɽ��������饤�󷿥ץ饰����Ȥ��ƻ��Ѥ�����϶���Ū�� display=inline �Ȥʤ롣~
-inline_before=ʸ����
~display=inline �������ˤĤ���ʸ�������ꡣ~
�ե�������� PLUGIN_LS2_1_DISPLAY_INLINE_BEFORE �ǽ���ͤ�����Ǥ��ޤ����ǥե���ȤǤ� '[ ' �Ǥ���
-inline_delimiter=ʸ����
~display=inline ���ζ��ڤ�ʸ�������ꡣ~
�ե�������� PLUGIN_LS2_1_DISPLAY_INLINE_DELIMITER �ǽ���ͤ�����Ǥ��ޤ����ǥե���ȤǤ� ' | ' �Ǥ���
-inline_after=ʸ����
~display=inline ���θ��ˤĤ���ʸ�������ꡣ~
�ե�������� PLUGIN_LS2_1_DISPLAY_INLINE_AFTER �ǽ���ͤ�����Ǥ��ޤ����ǥե���ȤǤ� ' ]' �Ǥ���
-compact=true|false
~�ꥹ�ȤΥ�٥��Ĵ�᤹�롣display=hierarchy �ѤΥ��ץ����Ǥ���compact �����Ǥ� true �ˤʤ�ޤ���~
�ե�������� PLUGIN_CONTENTS2_1_COMPACT �ǽ���ͤ�����Ǥ��ޤ����ǥե���Ȥ� TRUE �Ǥ���
-number=\d+ ((\d+ ������ɽ���ˤ��ɽ���Ǥ����㤨�� \d �Ͽ����Τ��ȤǤ���))
~ɽ������λ��ꡣ~
Note: include �ڡ���̾��ɽ���⣱�Ĥȥ�����Ȥ��ޤ���
-depth=\d*[-+]?\d*((\d*[-+]?\d* ������ɽ���ˤ��ɽ���Ǥ���\d �Ͽ����Τ��ȤǤ���))
~���Ф���٥���ꡣ1 �ʤ鸫�Ф���٥� 1 �Τߤ�ɽ�����롣
2-3 �Τ褦�ʻ�����ǽ (2,3 �ΰ�)��2- �Τ褦�˻��ꤹ��ȥ�٥� 2 �ʾ�θ��Ф�����
2+1 �Τ褦�ʻ�����ǽ (2 �Ȥ������� +1 ���Ĥޤ� 2,3 �ΰ�)��~
Note: include �ڡ���̾����٥룰�����Ф��ϥ�٥룱�ʹߤǤ���
������ compact ����Ѥ��Ƥ��Ƥ�(�ǥե���Ȥ� TRUE)��depth ���ץ����ǻ��ꤹ���٥���Ѥ��ޤ���
��äƸ����ܤȻ��ꤹ�٤���٥뤬�㤦���⤷��ޤ��󡣰�ö compact=false �Ȥ���Ĵ�٤�гμ¤Ǥ���
-except=����ɽ��
~�ꥹ�Ȥ��ʤ����Ф�������ɽ���ˤƻ��ꡣ~
�ҥ�ȡ� [[ereg>http://php.s3.to/man/function.ereg.html]] ��Ƚ���Ԥ��ޤ���
except=Test|sample �� Test �ޤ��� sample ��ޤห�Ф��������
-include=true|false
~#include �ץ饰����Ǽ�����Ǥ���ڡ����Ȥ��θ��Ф��ⰷ����include �����Ǥ� true �ˤʤ�ޤ���~
�ե�������� PLUGIN_CONTENTS2_1_INCLUDE �ǽ���ͤ�����Ǥ��ޤ����ǥե���Ȥ� TRUE �Ǥ���~
Note: �ڡ��������ȥ�ؤΥ����פ� #include �����󥫡���ĥ�äƤ���ʤ���̵���Ǥ���
����[[��ä������� - include �ץ饰����β�¤>#f0771d8a]] ��������������
//���ꥢ�󥫡��ν񼰤ˤ��褦��Ǻ���档�ɤ���ˤ���Ʊ��������@see file.php//Generate ID
-fixed_anchor=true|false
~���ꥢ�󥫡�������Ф�������Ѥ��롣fixed_anchor �����Ǥ� true �ˤʤ�ޤ����侩�Ǥ���~
�ե�������� PLUGIN_CONTENTS2_1_FIXEDANCHOR �ǽ���ͤ�����Ǥ��ޤ����ǥե���Ȥ� TRUE �Ǥ���~
Note: ���ꥢ�󥫡���Ĥ��뤿��ˤ� pukiwiki.ini.php  ��� $fixed_heading_anchor = 1 �����ꤷ�ʤ���Ф����ޤ��󡣸��ߤ� pukiwiki �Ǥϥǥե���Ȥ� 1 �Ǥ���
*/

// ���Ф����󥫡��ν�
define('PLUGIN_CONTENTS2_1_ANCHOR_PREFIX', '#content_');
// ���Ф����󥫡��γ����ֹ�
define('PLUGIN_CONTENTS2_1_ANCHOR_ORIGIN', 0);
define('PLUGIN_CONTENTS2_1_PAGE_ANCHOR_ORIGIN', 1);
// #contents2_1 ���񤤤Ƥ��뼡�ι԰ʹߤθ��Ф��Τߤ�ꥹ�Ȥ���(�ǥե���� TRUE)
define('PLUGIN_CONTENTS2_1_FROMHERE', true);
// �ꥹ�ȤΥ�٥��Ĵ������(�ǥե���� TRUE)
define('PLUGIN_CONTENTS2_1_COMPACT', true);
// �ꥹ��ɽ������(�ǥե���� 'hierarchy')
define('PLUGIN_CONTENTS2_1_DISPLAY', 'hierarhcy');
// #include �ץ饰����Ǽ�����Ǥ���ڡ����θ��Ф��ⰷ��(�ǥե���� TRUE)
define('PLUGIN_CONTENTS2_1_INCLUDE', true);
// display=inline ���������֡����ˤĤ���ʸ��
define('PLUGIN_CONTENTS2_1_DISPLAY_INLINE_BEFORE', '[ ');
define('PLUGIN_CONTENTS2_1_DISPLAY_INLINE_MIDDLE', ' | ');
define('PLUGIN_CONTENTS2_1_DISPLAY_INLINE_AFTER', ' ]');
// fixed anchor �����ѡʥǥե���� TRUE)
define('PLUGIN_CONTENTS2_1_FIXEDANCHOR', true);
// CSS���饹����
define('PLUGIN_CONTENTS2_1_CSS_CLASS', 'contents2_1');

function plugin_contents2_1_init()
{
    $messages['_contents2_1_msg_err'] = '<div>\'%s\' does not exist.</div>';
    set_plugin_messages($messages);
} 

function plugin_contents2_1_inline()
{
    $args = func_get_args();
    array_pop($args);
    return plugin_contents2_1($args, 'inline');
} 

function plugin_contents2_1_convert()
{
    return plugin_contents2_1(func_get_args(), 'convert');
} 

function plugin_contents2_1($args, $calledby = 'convert')
{
    global $vars;
    global $script;
    global $_contents2_1_msg_err; 
    // true or false �Υ��ץ����
    $params = array('fromhere' => PLUGIN_CONTENTS2_1_FROMHERE,
        'compact' => PLUGIN_CONTENTS2_1_COMPACT,
        'include' => PLUGIN_CONTENTS2_1_INCLUDE,
        'fixed_anchor' => PLUGIN_CONTENTS2_1_FIXEDANCHOR,
        ); 
    // ����¾�ΰ�������ĥ��ץ����
    $argparams = array('page' => '',
        'depth' => '',
        'number' => '',
        'except' => '',
        'display' => PLUGIN_CONTENTS2_1_DISPLAY,
        ); 
    // ����¾�ΰ�����������ͤ� HTML �˽��Ϥ���륪�ץ����( �� htmlspecialchars )
    $arghtmlparams = array('inline_before' => PLUGIN_CONTENT22_1_DISPLAY_INLINE_BEFORE,
        'inline_delimiter' => PLUGIN_CONTENTS2_1_DISPLAY_INLINE_DELIMITER,
        'inline_after' => PLUGIN_CONTENTS2_1_DISPLAY_INLINE_AFTER,
        );
    array_walk($args, 'plugin_contents2_1_check_params', &$params);
    array_walk($args, 'plugin_contents2_1_check_argparams', &$argparams);
    array_walk($args, 'plugin_contents2_1_check_arghtmlparams', &$arghtmlparams);
    $params = array_merge($params, $argparams, $arghtmlparams); 
    // inline �ץ饰������϶��� display=inline��
    if ($calledby == 'inline') {
        $params['display'] = 'inline';
    } 
    // �ڡ���̾����
    if ($params['page'] == '') {
        $page = $vars['page'];
    } else {
        $page = $params['page'];
    } 
    if (! is_page($page) || ! check_readable($page, false, false)) {
        return sprintf($_contents2_1_msg_err, htmlspecialchars($page));
    } 
    // page ���ץ��������Ѥ�������ɽ���ڡ����Ȱ㤦�ڡ����θ��Ф���󥯤����祢�󥫡������Ǥ�­��ʤ���
    if ($page != $vars['page']) {
        $r_page = rawurlencode($page);
        $href = $script . '?cmd=read&amp;page=' . $r_page;
        $params['href'] = $href; 
        // ɽ���ڡ����Ȱ㤦�ڡ��������ꤵ��Ƥ���ж��� FALSE
        $params['fromhere'] = false;
    } else {
        $params['href'] = '';
    } 
    // depth ���ץ�������
    if ($params['depth'] != '') {
        list($params['lowdepth'], $params['highdepth']) = plugin_contents2_1_depth_option_analysis($params['depth']);
    } 
    // number ���ץ�������
    if ($params['number'] != '') {
        if (! preg_match('/^\d+$/', $params['number'])) {
            $params['number'] = '';
        } 
    } 

    $params['result'] = $params['saved'] = array();
    $params['page_anchor_counter'] = PLUGIN_CONTENTS2_1_PAGE_ANCHOR_ORIGIN;
    $params['number_counter'] = 0;
    $params['fromhere_detected'] = false;
    plugin_contents2_1_get_headings($page, $params);

    if ($params['display'] == 'inline') {
        if ($calledby == 'inline') {
            $tag = 'span';
        } else {
            $tag = 'div';
        } 
        return "<$tag class=\"" . PLUGIN_CONTENTS2_1_CSS_CLASS . "\">"
         . join("", $params['result']) . join("", $params['saved']) . "</$tag>";
    } else {
        return join("\n", $params['result']) . join("\n", $params['saved']);
    } 
} 

function plugin_contents2_1_get_headings($page, &$params)
{
    static $_contents2_1_anchor = 0; 
    // ���Ǥˤ��Υڡ����θ��Ф���ɽ���������ɤ����Υե饰
    $is_done = (isset($params["page_$page"]) && $params["page_$page"] > 0);
    if (! $is_done) $params["page_$page"] = ++$_contents2_1_anchor; 
    // include �ڡ����ξ��
    if ($params['page_anchor_counter'] > 1) {
        // ɽ���Ѥ�
        if ($is_done) {
            $params['page_anchor_counter']--;
            return;
        } 
        // ɸ�� #include �ץ饰����ˤϥ��󥫡��ϤĤ��ʤ��ΤǤ��Υ��󥫡���󥯤ϵ�ǽ���ʤ���
        // ����ץ饰����/include2.inc.php �Ϥ�����б����Ƥ��ޤ���
        $id = '#' . plugin_contents2_1_pageanchor($page); 
        // include �ڡ���̾�Υ�٥�ϣ�
        $level = 0;

        $link_string = htmlspecialchars($page);
        $title = $link_string . ' ' . get_pg_passage($page, false);

        plugin_contents2_1_push2result($page, &$params, $level, $page, $id, $title, $link_string);
    } 

    $anchor_counter = PLUGIN_CONTENTS2_1_ANCHOR_ORIGIN;
    $matches = array();

    foreach (get_source($page) as $line) {
        // include �ڡ����ˤ��� #contents2_1 �ϸƤӽФ��� #contents2_1 �Ȥ����餫�˰㤦��ĤʤΤ�
        // include �ڡ������Ф��Ƥ� fromhere �������餷�ʤ���̵�����Ф���ˡ�
        // ���� $params['page_anchor_counter'] �Τ���� #include ��é�ä� #include �ο��Ͽ����ʤ��Ȥ����ʤ���
        // fixed_anchor=false ���Ȥ��Ƥ� fixed_anchor ���ޤ�����Ƥ��ʤ��ڡ����Ǥ����ǽ��������ΤǤ�Ϥ�����ʤ��Ȥ����ʤ���
        if ($params['fromhere'] && ! $params['fromhere_detected'] && $params['page_anchor_counter'] > 1) {
            if ($params['include'] &&
                preg_match('/^#include.*\((.+)\)/', $line, $matches) &&
                    is_page($matches[1])) {
                $params['page_anchor_counter']++;
                plugin_contents2_1_get_headings($matches[1], $params);
            } 
            continue;
        } 
        // fromhere Ƚ�ꡣ�ޤ����Ĥ��äƤ��ʤ����ϲ��⤷�ʤ�
        if ($params['fromhere'] && ! $params['fromhere_detected'] &&
            $params['fromhere_detected'] = preg_match('/^#contents2\_1/', $line, $matches)) {
            // do nothing
        } 
        // ���Ф�����
        elseif (preg_match('/^(\*{1,3})/', $line, $matches)) {
            // ���󥫡�ʸ�����Ĥ��롣$anchor_counter++ �����ס�
            $id = PLUGIN_CONTENTS2_1_ANCHOR_PREFIX . $params['page_anchor_counter'] . '_' . $anchor_counter++; 
            // fromhere ���ޤ����Ĥ��äƤ��ʤ���� $anchor_counter++ �������� continue��
            if ($params['fromhere'] && ! $params['fromhere_detected']) continue; 
            // ���Ф���٥�ϣ��ʹ�
            $level = strlen($matches[1]); 
            // $line �� 'remove footnotes and HTML tags' ����롣���Ф��Ԥθ��ꥢ�󥫡����֤���롣
            $fixed_id = make_heading($line);
            if ($params['fixed_anchor'] && $fixed_id !== '') {
                $id = '#' . $fixed_id;
            } 
            // ��ư���󥫡����Ĥ�����ξ��� [#438239] �����˾���������������� make_heading �ǤϤޤ��Ĥ�褦�ʤΤǡ�
            $title = $link_string = trim($line);

            plugin_contents2_1_push2result($line, &$params, $level, $page, $id, $title, $link_string); 
            // number Ƚ�ꡣ���¤�ۤ��Ƥ����ȴ���ƽ�λ��
            if ($params['number'] != '' && $params['number_counter'] >= $params['number']) {
                break;
            } 
        } 
        // include ����
        elseif ($params['include'] &&
            preg_match('/^#include.*\((.+)\)/', $line, $matches) &&
                is_page($matches[1])) {
            $params['page_anchor_counter']++;
            plugin_contents2_1_get_headings($matches[1], $params);
        } 
    } 
} 
// ���ץ����Ƚ���Ԥ�������ʤ���Х�󥯤��������Ǽ���Ƥ�����
function plugin_contents2_1_push2result($line, &$params, $level, $page, $link_id, $link_title, $link_string)
{ 
    // number Ƚ�ꡣinclude �ڡ���̾��ɽ���⣱�Ĥȿ����롣
    if ($params['number'] != '' && $params['number_counter'] >= $params['number']) {
        // do nothing
    } 
    // except Ƚ��
    elseif ($params['except'] != '' && ereg($params['except'], $line)) {
        // do nothing
    } 
    // depth Ƚ�ꡣ
    elseif ($params['lowdepth'] != '' && $level < $params['lowdepth']) {
        // do nothing
    } elseif ($params['highdepth'] != '' && $level > $params['highdepth']) {
        // do nothing
    } else {
        // display  ���ץ����
        if ($params['display'] == 'inline') {
            $litag = '';
        } else {
            $litag = '<li>';
        } 
        // include ���ץ������� include �ڡ���̾��ɽ�����ʤ���Ф����ʤ��Τǡ���٥�0����+1���餹��¾��
        if ($params['include']) {
            $level++;
        } 
        // �ꥹ�Ⱥ���
        plugin_contents2_1_list_push($params, $level);

        array_push($params['result'], $litag);
        $ret .= '<a id="list_' . $params["page_$page"] . '" href="' . $params['href'] . $link_id . '" title="' . $link_title . '">' . $link_string . '</a>';
        array_push($params['result'], $ret);

        $params['number_counter']++;
    } 
} 
// <ul> �� </li></ul> ��Ŭ���������롣
function plugin_contents2_1_list_push(&$params, $level)
{
    global $_ul_left_margin, $_ul_margin, $_list_pad_str;

    $result = &$params['result']; // �Хåե����������Ϥ��뤳�Ȥˤʤ롣
    $saved = &$params['saved']; // �Ĥ��ʤ���Ф����ʤ�ʸ���� </ul> �򤿤��廊�Ƥ�����
    if ($params['display'] == 'inline') {
        $ulopen = PLUGIN_CONTENTS2_1_DISPLAY_INLINE_BEFORE;
        $ulclose = PLUGIN_CONTENTS2_1_DISPLAY_INLINE_AFTER;
        $liclose = PLUGIN_CONTENTS2_1_DISPLAY_INLINE_MIDDLE;
    } else {
        $ulopen = '<ul class="' . PLUGIN_CONTENTS2_1_CSS_CLASS . '"%s>';
        $ulclose = "</li>\n</ul>";
        $liclose = '</li>';
    } 

    if ($params['display'] == 'flat' || $params['display'] == 'inline') {
        // ������������ˤ���ΤϤ��줷���ʤ������ޤȤ�Ƥ��������ä���
        if (count($saved) < 1) {
            if ($params['display'] == 'flat') {
                $left = $_ul_margin;
            } else if ($params['display'] == 'inline') {
                $left = 0;
            } 
            $level = 1;
            $str = sprintf($_list_pad_str, $level, $left, $left);
            array_push($result, sprintf($ulopen, $str));
            array_unshift($saved, $ulclose);
        } else {
            array_push($result, $liclose);
        } 
    } else {
        while (count($saved) > $level || (! empty($saved) && $saved[0] != $ulclose))
        array_push($result, array_shift($saved));

        $margin = $level - count($saved); 
        // count($saved)�����䤹
        while (count($saved) < ($level - 1)) array_unshift($saved, '');

        if (count($saved) < $level) {
            array_unshift($saved, $ulclose);

            $left = ($level == $margin) ? $_ul_left_margin : 0;

            if ($params['compact']) {
                $left += $_ul_margin; // �ޡ���������
                $level -= ($margin - 1); // ��٥����
            } else {
                $left += $margin * $_ul_margin;
            } 

            $str = sprintf($_list_pad_str, $level, $left, $left);
            array_push($result, sprintf($ulopen, $str));
        } else {
            array_push($result, $liclose);
        } 
    } 
} 
// ���ץ���� \d?[+-]?\d? ����ϡ�
function plugin_contents2_1_depth_option_analysis($arg)
{
    $low = 0;
    $high = 0;
    if (!preg_match('/^\d*\-?\d*$/', $arg) or $arg == '') {
        return array('', '');
    } 

    if (substr_count($arg, "-")) { // \d-\d �ξ��
            list($low, $high) = split("-", $arg, 2);
    } elseif (substr_count($arg, "+")) { // \d+\d �ξ��
            list($low, $high) = split("+", $arg, 2);
        $high += $low;
    } else { // \d �����ξ��
            $low = $high = $arg;
    } 
    return array($low, $high);
} 
// true or false ���ͤ���ĥ��ץ�������Ϥ���
function plugin_contents2_1_check_params($value, $key, &$params)
{ 
    // $value �� depth=2-3 �� hierarchy �Τ褦���ͤ����롣�¼� $key �ϰ�̣�ʤ���
    // trim �Ϥ����Ƥ��Ƥ��ʤ���
    if ($value == '') return;

    list($key, $val) = split("=", $value);
    if (isset($params[$key])) {
        if ($val == '' || $val == "true") {
            $params[$key] = true;
        } elseif ($val == "false") {
            $params[$key] = false;
        } 
    } 
} 
// ����¾���ͤ���ĥ��ץ�������Ϥ���
function plugin_contents2_1_check_argparams($value, $key, &$params)
{
    if ($value == '') return;

    list($key, $val) = split("=", $value);
    if (isset($params[$key])) {
        $params[$key] = $val;
    } 
} 
// ����¾�ΰ�����������ͤ� HTML �˽��Ϥ���륪�ץ�������Ϥ��� (�� htmlspecialchars)
function plugin_contents2_1_check_arghtmlparams($value, $key, &$params)
{
    if ($value == '') return;

    list($key, $val) = split("=", $value);
    if (isset($params[$key])) {
        $params[$key] = htmlspecialchars($val);
    } 
} 

// �ڡ������󥫡�������
function plugin_contents2_1_pageanchor($page)
{
    // �ڡ���̾���100ʸ���򥭡��� md5 �ϥå�����ꡢ����� 7 ʸ���˺�롣
    // ���󥫡�����Ƭ�� [A-Za-z] �Ǥʤ���Фʤ�ʤ��Τ� 'A' ��Ĥ��롣
    $start = (($len = strlen($page) - 100) > 0) ? $len : 0;
    $pageanchor = 'A' . substr(md5(substr($page, $start)), 0, 7);
    return $pageanchor;
}

?>
