<?php 
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: ls2_1.inc.php 137 2006-08-03 15:46:02Z sonots $

/*
*���� [#m17ed6bb]
[[ls2>PukiWiki/1.4/�ޥ˥奢��/�ץ饰����/l#d2ce34ea]] ��ĥ((ls2 v1.23 �γ�ĥ�Ǥ���))��[[����ץ饰����/ls3.inc.php]] �Ȥϰ㤤��
�ڡ���̾�ˤ�볬�ع�¤�����ǥꥹ�Ȥ������ ls2 �γ�ĥ�Ǥ���
MenuBar �� #ls2_1(hogehoge/,depth=1,relative) �Τ褦�ˤ����Ƥ����������Ǥ���
**ɸ��ץ饰���� ls2 ������ѹ���(����) [#k9cb86b6]
-���ػ����ǽ��
-����Ū�ꥹ��ɽ����ǽ��
-���Хѥ�Ūɽ����ǽ��
-pukiwiki.ini.php �����ꤹ�� $non_list �����ѡ�
-include ��̵�¥롼�פ�����
-link ���˾�� include, title ���ץ�����ղä���Ƥ�������ñ���Ʊ�����ꤷ�����ץ��������Ѥ���褦���ѹ���
�����ȼ�����̾�Ȥ������Ѥ���������֥��ץ�����Ƚ�ꤵ��ʤ������ʹߤΤ��٤Ƥΰ����פ��� link=���̾ �Ȼ��ꤹ��褦���ѹ�
((ls2_1.inc.php v1.18 ����Ǥ�����������ϡ֥��ץ�����Ƚ�ꤵ��ʤ��ǽ�ΰ����פǤ���))��
**���θ���ɲõ�ǽ [#u2116eb9]
-ɽ��������굡ǽ
-�����ڡ������굡ǽ
-��������ɽ����ǽ
-New ɽ����ǽ
-���������ˤ�륽���ȵ�ǽ
-����ɽ���ˤ��ڡ����Υե��륿��ǽ
-����饤��ɽ����ǽ��

*�� [#jcb5f796]
 #ls2_1(�ѥ�����[,���ץ����])

 &ls2_1(�ѥ�����[,���ץ����]);
����饤��ץ饰������϶���Ū�� display=inline �Ȥʤ�ޤ���link ���ץ������ǽ�Ǥ���

//index.php?plugin=ls2_1
//true ����� 1����:relative=1, false ����� '' ��:relative= (����Ū�ˤ� false �ǤϤʤ� '' �ˤʤäƤ�����ȴ���褦)���ǥե����ư��ϥ��ץ�����ά�ǡ�

**�ѥ�᡼�� [#r8a06bfd]
-�ѥ�����(�ǽ�˻���)
~�ꥹ�Ȥ���ڡ���̾�Υѥ����󡣾�ά����Ȥ��⥫��ޤ�ɬ�ס�
��ά���ϥ����ȥڡ���+"/"�����ꤵ�줿���Ȥˤʤ롣
�ޤ� / ����ꤷ�����Ϥ��٤ƤΥڡ����˥ޥå����롣
�ޤ� // ����ꤷ������"�����ȥڡ���"�����ꤵ�줿���Ȥˤʤ�(�����դ�)��
-title=true|false
~�ڡ�����θ��Ф���ꥹ�Ȥ��롣
title ������ title=true �ΰ�̣�ˤʤ롣
-include=true|false
~���󥯥롼�ɤ��Ƥ���ڡ�����ꥹ�Ȥ��롣include �����Ǥ� include=true �ΰ�̣�ˤʤ롣
-link=���̾
~action�ץ饰�����ƤӽФ���󥯤�ɽ����
link �����ξ��ϡ֥ѥ�����פ���ʬ����Ѥ������̾������롣
-reverse=true|false
~�ڡ������¤ӽ��ȿž�����߽�ˤ��롣reverse �����Ǥ� reverse=true �ΰ�̣�ˤ롣~
Note: hierarchy,relative ����ӤȤ�ʻ�ѤϤ��ä�Ǽ���Τ����ʤ�ɽ���ˤʤ�ޤ�(�����Ѥ��߷פ��줿���ץ����ʤΤ�)��
-compact=true|false
~�ꥹ�ȤΥ�٥��Ĵ�����롣compact �����Ǥ� compact=true �ΰ�̣�ˤʤ롣~
�ե�������� PLUGIN_LS2_1_LIST_COMPACT �ǽ���ͤ�����Ǥ��ޤ����ǥե���ȤǤ� TRUE �Ǥ���
-title_compact=true|false
~title ���ץ�����Ѥ� compact ��ǽ��title_compact �����Ǥ� title_compact=true �ΰ�̣�ˤʤ롣~
�ե�������� PLUGIN_LS2_1_LIST_TITLE_COMPACT �ǽ���ͤ�����Ǥ��ޤ����ǥե���ȤǤ� TRUE �Ǥ���
-depth=\d*[-+]?\d*((\d*[-+]?\d* ������ɽ���ˤ��ɽ���Ǥ���\d �Ͽ����Τ��ȤǤ���))
~���ػ��ꡣ1 �ʤ� 1 ���ز��Υڡ����Τߤ�ɽ�����롣
2-4 �Τ褦�ʻ�����ǽ (2,3,4 �ΰ�)��2- �Τ褦�˻��ꤹ��� 2 ���ز��ʲ��Υڡ�����
2+1 �Τ褦�ʻ�����ǽ (2 �Ȥ������� 1 ���ز����Ĥޤ� 2,3 �ΰ�)��
//0-2 = false-2 = -2. 1-0 = 1-false = 1-. 2+ = 2+false = 2+0. +2 = false+2 = 0+2.
//0 �ޤ��� - �ޤ��� + �ϻ��ꤷ�ʤ��Ȥ���Ʊ����
//0 becomes false. - = false-false. + = false+false.
-relative=true|false
~���Хѥ�Ūɽ����relative �����Ǥ� relative=true �ΰ�̣�ˤʤ롣~
�ե�������� PLUGIN_LS2_1_RELATIVE �ǽ���ͤ�����Ǥ��ޤ����ǥե���ȤǤ� FALSE �Ǥ���
-display=hierarchy|flat|inline
~�ꥹ��ɽ�������λ��ꡣhierarchy �Ǥϸ��Ф��Υ�٥�˱���������Ū�ꥹ��ɽ����
flat �Ǥϸ��Ф��Υ�٥�ˤ�餺ʿ���ɽ����inline �Ǥϲ������ɽ����~
�ե�������� PLUGIN_CONTENTS2_1_DISPLAY �ǽ���ͤ�����Ǥ��ޤ����ǥե���Ȥ� flat �Ǥ���~
Note1: ���̸ߴ����Τ��� hierarchy, hierarchy=true �Ǥ� display=hierarchy  �ˤʤ�褦�ˤ��Ƥ���ޤ���~
Note2: ����饤�󷿥ץ饰����Ȥ��ƻ��Ѥ�����϶���Ū�� display=inline �ˤʤ�ޤ���~
Note3: ������ư��Ȥ��碌�뤿��ˡ������Ƹ��Ф��ˤ� display=flat �������ʤ��褦�ˤ��Ƥ���ޤ���
-inline_before=ʸ����
~display=inline �������ˤĤ���ʸ�������ꡣ~
�ե�������� PLUGIN_LS2_1_DISPLAY_INLINE_BEFORE �ǽ���ͤ�����Ǥ��ޤ����ǥե���ȤǤ� '[ ' �Ǥ���
-inline_delimiter=ʸ����
~display=inline ���ζ��ڤ�ʸ�������ꡣ~
�ե�������� PLUGIN_LS2_1_DISPLAY_INLINE_DELIMITER �ǽ���ͤ�����Ǥ��ޤ����ǥե���ȤǤ� ' | ' �Ǥ���
-inline_after=ʸ����
~display=inline ���θ��ˤĤ���ʸ�������ꡣ~
�ե�������� PLUGIN_LS2_1_DISPLAY_INLINE_AFTER �ǽ���ͤ�����Ǥ��ޤ����ǥե���ȤǤ� ' ]' �Ǥ���
-non_list=true|false
~pukiwiki.ini.php ���������� $non_list �ˤ��ꥹ���ӽ���non_list �����Ǥ� non_list=true �ΰ�̣�ˤʤ롣~
�ե�������� PLUGIN_LS2_1_NON_LIST �ǽ���ͤ�����Ǥ��ޤ����ǥե���ȤǤ� TRUE �Ǥ���
-number=-?\d+ ((-?\d+ ������ɽ���ˤ��ɽ���Ǥ���))
~���ɽ��������ꡣBlog2�ץ饰�������Ѥ���Ȥ��������餷���Ǥ���
number=10 ��Ƭ����10��ɽ�����ޤ���number=-10 �Τ褦�� - ��Ĥ���ȸ���10��ˤʤ�ޤ���
����Ǥ�ս�ˤϤʤ�ʤ��Τ� reverse ����Ѥ��Ƥ���������
-title_number=\d+
~title��ɽ��������ꡣtitle_number=10��Ƭ����10��ɽ�����ޤ���
���� - ��ǽ�Ϥ���ޤ���title_number=1 �ǥڡ�������Ƭ���Ф���ɽ�����뤳�Ȥˤʤ�ޤ���
��Ƭ���Ф���ɬ���񤯿ͤ�¿�������ʤΤǤ����ɽ������Τ��������⤷��ޤ���
-except=����ɽ��
~�ꥹ�Ȥ��ʤ��ڡ���������ɽ���ˤƻ��ꡣ$non_list �����Ǥ�­��ʤ��Ȥ��˻��ѡ�
relative �ξ��Ǥ�ڡ���̾���Τ�Ƚ�ꡣ~
�ҥ�ȡ� �ޥå��󥰤ˤ� [[ereg>http://php.s3.to/man/function.ereg.html]] ����Ѥ��ޤ���
except=Test|sample �� Test �ޤ��� sample ��ޤ�ڡ����������
-datesort=true|false
~����������ʿ������ۤɾ�)��ɽ����datesort �����Ǥ� datesort=true �ΰ�̣�ˤʤ롣~
Note: hierarchy,relative ����ӤȤ�ʻ�ѤϤ��ä�Ǽ���Τ����ʤ�ɽ���ˤʤ�ޤ�
(hierarchy,relative �ϥڡ���̾�ξ��祽���Ȼ��ѤΥ��ץ����ʤΤ�)��~
Note2: include �����ڡ������Ф��Ƥ�̵��ʤΤ� include ���ץ�����ʻ�Ѥ��Ƥ�̵�̤Ǥ���~
Note3: �� new ���ץ����Ǥ�����դ��Ƥ���������
-date=true|false
~�ڡ����ι���������ɽ����date �����Ǥ� date=true �ΰ�̣�ˤʤ롣
-new=true|false
~&color(#ff0000){New!};��ɽ����new �����Ǥ� new=true �ΰ�̣�ˤʤ롣~
New! ��ɽ����������ɸ��ץ饰���� new �ξ�����Ѥ��Ƥ��ޤ���
new �ץ饰����¸�ߤ��ʤ������ȼ�����ʤȤ��äƤ� new ���饳�ԡ�������Ρˤ���Ѥ��ޤ���
-filter=����ɽ��
~�ڡ����ѥ�����򤵤������ɽ���Ǹ��ꤹ�롣
�ѥ������ / (���Ƥΰ�̣) �ˤ��Ƥ����������Ȥ��Τ⤢�ꡣ~
�ҥ��: �ޥå��󥰤ˤ� [[ereg>http://php.s3.to/man/function.ereg.html]] ����Ѥ��ޤ���
*/
// ���Ф����󥫡��ν�
define('PLUGIN_LS2_1_ANCHOR_PREFIX', '#content_1_');
// ���Ф����󥫡��γ����ֹ�
define('PLUGIN_LS2_1_ANCHOR_ORIGIN', 0);
// ���Ф���٥��Ĵ������(�ǥե���� TRUE)
define('PLUGIN_LS2_1_LIST_COMPACT', true);
define('PLUGIN_LS2_1_LIST_TITLE_COMPACT', true);
// $non_list �ˤ��ڡ����ӽ�����Ѥ���(�ǥե���� TRUE)
define('PLUGIN_LS2_1_NON_LIST', true);
// ���Хѥ�Ūɽ��(�ǥե���� FALSE)
define('PLUGIN_LS2_1_RELATIVE', false);
// ����Ū�ꥹ��ɽ��(�ǥե���� FALSE)
define('PLUGIN_LS2_1_HIERARCHY', false);
//�ꥹ��ɽ������(�ǥե���� 'flat')
define('PLUGIN_LS2_1_DISPLAY','flat');
//display=inline ���������֡����ˤĤ���ʸ��
define('PLUGIN_LS2_1_DISPLAY_INLINE_BEFORE', '[ ');
define('PLUGIN_LS2_1_DISPLAY_INLINE_DELIMITER', ' | ');
define('PLUGIN_LS2_1_DISPLAY_INLINE_AFTER',  ' ]');
//CSS���饹����
define('PLUGIN_LS2_1_CSS_CLASS','ls2_1');

function plugin_ls2_1_init()
{
    // �Ǥ������ new �ץ饰�����������Ѥ��� (new ���ץ������)
    if (is_file(PLUGIN_DIR . 'new.inc.php')) {
        require_once(PLUGIN_DIR . 'new.inc.php');
        plugin_new_init();
    } else {
        $messages['_plugin_new_elapses'] = array(
        60 * 60 * 24 * 1 => ' <span class="new1" title="%s">New!</span>',  // 1day
        60 * 60 * 24 * 5 => ' <span class="new5" title="%s">New</span>');  // 5days
        set_plugin_messages($messages);
    }
}

function plugin_ls2_1_action()
{
    return plugin_ls2_1('', 'action');
}

function plugin_ls2_1_inline()
{
    // {} ���ϻ��Ѥ��ʤ��Ϥ��ʤΤǼ�������
    $args = func_get_args();
    array_pop($args);
    return plugin_ls2_1($args, 'inline');
}

function plugin_ls2_1_convert()
{
    return plugin_ls2_1(func_get_args(), 'convert');
}

function plugin_ls2_1($args, $calledby = 'convert')
{
    global $script;
    global $vars;
    global $_ls2_msg_title;

    // true or false �Υ��ץ����
    $params = array('title' => false,
    'include' => false,
    'reverse' => false,
    'compact' => PLUGIN_LS2_1_LIST_COMPACT,
    'title_compact' => PLUGIN_LS2_1_LIST_TITLE_COMPACT,
    'relative' => PLUGIN_LS2_1_RELATIVE,
    'hierarchy' => PLUGIN_LS2_1_HIERARCHY,
    'non_list' => PLUGIN_LS2_1_NON_LIST,
    'datesort' => false,
    'date' => false,
    'new' => false,
    );
    // ����¾�ΰ�������ĥ��ץ����
    $argparams = array('depth' => false,
    'number' => false,
    'title_number' => false,
    'except' => false,
    'filter' => false,
    'display' => PLUGIN_LS2_1_DISPLAY,
    );
    // ����¾�ΰ�����������ͤ� HTML �˽��Ϥ���륪�ץ����( �� htmlspecialchars )
    $arghtmlparams = array('link' => false,
    'inline_before' => PLUGIN_LS2_1_DISPLAY_INLINE_BEFORE,
    'inline_delimiter' => PLUGIN_LS2_1_DISPLAY_INLINE_DELIMITER,
    'inline_after' => PLUGIN_LS2_1_DISPLAY_INLINE_AFTER,
    );

    // prefix ����
    $prefix = '';
    if ($calledby == 'action') {
        $prefix = isset($vars['prefix']) ? $vars['prefix'] : '';
        if ($prefix === '/') {
            $prefix = '';
        }
    } else {
        if (sizeof($args)) {
            $prefix = array_shift($args);
        }
        if ($prefix == '') {
            $prefix = strip_bracket($vars['page']) . '/';
        } else if ($prefix === '/') {
            $prefix = '';
        } else if ($prefix === '//') {
            $prefix = strip_bracket($vars['page']);
        }
    }


    // ���ץ�������
    if ($calledby == 'action') {
        foreach ($params as $key => $default) {
            $params[$key] = isset($vars[$key]) ? $vars[$key] : $default;

        }
        foreach ($argparams as $key => $default) {
            $argparams[$key] = isset($vars[$key]) ? $vars[$key] : $default;
        }
        foreach ($arghtmlparams as $key => $default) {
            $arghtmlparams[$key] = isset($vars[$key]) ? htmlspecialchars($vars[$key]) : $default;
        }
        $params = array_merge($params, $argparams, $arghtmlparams);
    } else {
        $params = plugin_ls2_1_check_params($args, $params);
        $argparams = plugin_ls2_1_check_argparams($args, $argparams);
        $arghtmlparams = plugin_ls2_1_check_arghtmlparams($args, $arghtmlparams);
        $params = array_merge($params, $argparams, $arghtmlparams);
    }

    // ����
    if ($calledby == 'action') {
        if ($params['link'] == '') {
            $title = str_replace('$1', htmlspecialchars($prefix), $_ls2_msg_title);
        } else {
            $title = $params['link'];
        }
        $body = plugin_ls2_1_show_lists($prefix, $params, $calledby);

        return array('body' => $body, 'msg' => $title);
    } else {
        if ($params['link'] !== false) {
            // link ���ץ�����

            // ���Ϥ�Ϥॿ�����������̾��ul, li �����Ǥ���
            if ($calledby == 'inline') {
                $plugintag = 'span';
            } else {
                $plugintag = 'p';
            }
            $open_plugintag = "<$plugintag class=\"" . PLUGIN_LS2_1_CSS_CLASS . "\">";
            $close_plugintag = "</$plugintag>";

            if ($params['link'] === '') {
                $title = str_replace('$1', htmlspecialchars($prefix), $_ls2_msg_title);
            } else {
                $title = $params['link'];
            }

            $tmp = array();
            $tmp[] = 'plugin=ls2_1&amp;prefix=' . rawurlencode($prefix);
            foreach ($params as $key => $val) {
                if (strpos($key, '_') === 0) {
                    continue;
                }
                // if ($val !== $default[$key]) �Ȥ�ä��ۤ������ޡ��Ȥʵ������뤬 $default ���ʤ��ΤǤ��礦���ʤ���
                $tmp[] = "$key=$val";
            }
            $ret = '<a href="' . $script . '?' . join('&amp;', $tmp) . '">' . $title . '</a>';
            return $open_plugintag . $ret . $close_plugintag;
        } else {
            // �̾��
            return plugin_ls2_1_show_lists($prefix, $params, $calledby);
        }
    }
}

// �ꥹ�Ⱥ���
function plugin_ls2_1_show_lists($prefix, &$params, $calledby = 'convert') {
    global $_ls2_err_nopages;
    global $non_list;

    // inline �ץ饰������϶��� display=inline��
    if ($calledby == 'inline') {
        $params['display'] = 'inline';
    }
    // hierarchy ���̸ߴ���
    if ($params['display'] == PLUGIN_LS2_1_DISPLAY && $params['hierarchy']) {
        $params['display'] = 'hierarchy';
    }
    // depth ���ץ�������
    if ($params['depth']) {
        $params['prefixdepth'] = substr_count($prefix, "/") -1 ; //�ѥ�����ʸ����γ��ؿ���$depthflag Ƚ�Ǥ˻��ѡ�
        list($params['lowdepth'], $params['highdepth']) = plugin_ls2_1_depth_option_analysis($params['depth']);
    }
    // number ���ץ�������
    if ($params['number']) {
        list($params['headnumber'], $params['tailnumber']) = plugin_ls2_1_number_option_analysis($params['number']);
    }
    // title_number ���ץ�������
    if ($params['title_number']) {
        list($params['title_number']) = plugin_ls2_1_number_option_analysis($params['title_number']);
    }
    // display ���ץ������ϡ����������ͤ���ꤢ�Ƥ��Ƥ������ǥե���Ȥ��᤹����
    if($params['display'] != 'hierarchy' && $params['display'] != 'flat' && $params['display'] != 'inline') {
        $params['display'] = PLUGIN_LS2_1_DISPLAY;
    }
    // ���Ϥ�Ϥॿ����display=inine �˻��ѡ��������̾��ul, li �����Ǥ���
    if ($calledby == 'inline') {
        $plugintag = 'span';
    } else {
        $plugintag = 'p';
    }
    $open_plugintag = "<$plugintag class=\"" . PLUGIN_LS2_1_CSS_CLASS . "\">";
    $close_plugintag = "</$plugintag>";

    $pages = array();
    $pagestmp = array();
    foreach (get_existpages() as $_page) {
        // depth ����
        if ($params['depth']) {
            $flag = true;
            $_pagedepth = substr_count($_page, "/");
            if ($params['lowdepth']) {
                $flag &= $_pagedepth >= $params['prefixdepth'] + $params['lowdepth'];
            }
            if ($params['highdepth']) {
                $flag &= $_pagedepth <= $params['prefixdepth'] + $params['highdepth'];
            }
            if (! $flag) continue;
        }
        // non_list ����
        if ($params['non_list']) {
            $flag = !preg_match("/$non_list/", $_page);
            if (! $flag) continue;
        }
        // �ѥ���������
        if ($prefix !== '') {
            $flag = (strpos($_page, $prefix) === 0);
            if (! $flag) continue;
        }
        // except ����
        if ($params['except']) {
            $except = $params['except'];
            $flag = ! ereg($except, $_page);
            if (! $flag) continue;
        }
        // filter ����
        if ($params['filter']) {
            $filter = $params['filter'];
            $flag = ereg($filter, $_page);
            if (! $flag) continue;
        }
        // ���٤� TRUE �ʤ��ɲá�
        $pagestmp[] = $_page;
    }
    if ($params['datesort']) {
        // datesort ���ץ���󡣹���������ˤ�륽���ȡ�plugin_ls2_1_timecmp �� function
        usort($pagestmp, "plugin_ls2_1_timecmp");
    } else {
        // �̾�ϥڡ���̾�ǥ����ȡ�
        natcasesort($pagestmp);
    }
    // ɽ�� number ����
    if ($params['number']) {
        $asize = sizeof($pagestmp);
        if ($params['headnumber'] > 0) {
            $start = 0;
            $end = ($params['headnumber'] < $asize) ? $params['headnumber'] : $asize;
        } elseif ($params['tailnumber'] > 0) {
            $end = $asize;
            $start = ($end - $params['tailnumber'] > 0) ? $end - $params['tailnumber'] : 0;
        }
        for($i = $start; $i < $end ; $i++) {
            $pages[] = $pagestmp[$i];
        }
    } else {
        $pages = $pagestmp;
    }

    if ($params['reverse']) $pages = array_reverse($pages);

    foreach ($pages as $page) $params["page_$page"] = 0;

    if (empty($pages)) {
        return str_replace('$1', htmlspecialchars($prefix), $_ls2_err_nopages);
    }

    $params['result'] = array();
    $params['saved'] = array();
    // hierarchy ���ץ���󡣥ꥹ�ȥ�٥�����
    // ���ػ���򤷤Ƥ�����ϡ��ꥹ��ɽ����ǤΥȥåפ�����롣
    $top_level = 1;
    if ($params['depth']) {
        $top_level = $params['lowdepth'];
    }
    if ($top_level < 1) {
        $top_level = 1;
    }
    // �ײ���: number=- ��ʻ�Ѥ��줿���ϡ�$pages �򤹤٤� parse ���ư��ֳ��ؤ����ʤ��ڡ����򸫤Ĥ����������ˤ���
    if ($params['display'] == 'hierarchy') {
        if ($params['compact']) {
            // �ѥ�����κǸ�� / �ʲ������������) sample/test/d -> sample/test
            // $prefix_dir = preg_replace('/[^\/]+$/','',$prefix);
            if (($pos = strrpos($prefix, '/')) !== false) {
                $prefix_dir = substr($prefix, 0, $pos + 1);
            }
        }

        foreach ($pages as $page) {
            // level �׻�
            if ($params['compact']) {
                // �ѥ������Ȥ�Τ���
                $tmp = substr($page, strlen($prefix_dir));
                $level = 1;
                // depth ���ץ���󤬻��ꤵ��Ƥ������ $top_level ���Ѥ��ޤ���
                while (substr_count($tmp, "/") > $top_level -1) {
                    // �쳬�ؤ��ĤȤ�Τ���
                    // $tmp = preg_replace('/\/[^\/]*$/','',$tmp);
                    if (($pos = strrpos($tmp, '/')) !== false) {
                        $tmp = substr($tmp, 0, $pos);
                    }
                    // compact �ʤΤǾ�̤Υڡ�����¸�ߤ��Ƥ���Хꥹ�ȳ��إ�٥뤬������
                    if (in_array($prefix_dir . $tmp, $pages)) {
                        $level++;
                    }
                }
            }
            // compact �Ǥʤ����Ͼ�̤Υڡ�����̵ͭ�����ʤ��Τ� / �ο��ǽ�ʬ��
            else {
                $level = substr_count($page, "/") - $params['prefixdepth'];
                // depth ���ץ���󤬻��ꤵ��Ƥ������ $top_level ���Ѥ��ޤ���
                if ($params['depth']) {
                    $level = $level - ($top_level -1);
                }
            }
            // ����
            plugin_ls2_1_get_headings($page, $params, $level, false, $prefix, $top_level, $pages);
        }
    } else {
        foreach ($pages as $page) {
            plugin_ls2_1_get_headings($page, $params, 1, false, $prefix, 1, $pages);
        }
    }

    if ($params['display'] == 'inline') {
        $ret = join("", $params['result']) . join("", $params['saved']);
        return $open_plugintag . $ret . $close_plugintag;
    } else {
        return join("\n", $params['result']) . join("\n", $params['saved']);
    }

}

function plugin_ls2_1_get_headings($page, &$params, $level = 1, $include = false, $prefix, $top_level = 1, &$pages)
{
    global $script;
    static $_ls2_anchor = 0;

    // ���Ǥˤ��Υڡ����θ��Ф���ɽ���������ɤ����Υե饰
    $is_done = (isset($params["page_$page"]) && $params["page_$page"] > 0);
    if (! $is_done) $params["page_$page"] = ++$_ls2_anchor;

    $s_page = htmlspecialchars($page);
    $title = $s_page . ' ' . get_pg_passage($page, false);
    $r_page = rawurlencode($page);
    $href = $script . '?' . $r_page;
    // relative ���ץ���󡣥��̾���档
    if ($params['relative']) {
        // �ѥ�����κǸ�� / �ʲ������������) sample/test/d -> sample/test
        // $prefix_dir = preg_replace('/[^\/]+$/','',$prefix);
        if (($pos = strrpos($prefix, '/')) !== false) {
            $prefix_dir = substr($prefix, 0, $pos + 1);
        }
        // �ڡ���̾���餽�Υѥ������Ȥ������
        // $s_page = ereg_replace("^$prefix_dir",'',$s_page);
        $s_page = substr($s_page, strlen($prefix_dir));
        // relative ���ץ����� hierarchy ���ץ����Ʊ���˻��ꤵ�줿����
        // �ѥ����������������Ǥʤ�����̤�¸�ߤ��Ƥ���ڡ���̾���������
        if ($params['display'] == 'hierarchy') {
            $tmp = $s_page;
            // depth ���ץ���󤬻��ꤵ��Ƥ������ $top_level ���Ѥ��ޤ���
            while (substr_count($tmp, "/") > $top_level -1) {
                // �쳬�ؤ��ĤȤ�Τ���
                if (($pos = strrpos($tmp, '/')) !== false) {
                    $tmp = substr($tmp, 0, $pos);
                }
                // ��̤Υڡ�����¸�ߤ��Ƥ���С�����ʸ����������������̾�ˤ��롣
                if (in_array($prefix_dir . $tmp, $pages)) {
                    // $s_page = ereg_replace("^$tmp/",'',$s_page);
                    $s_page = substr($s_page, strlen("$tmp/"));
                    break;
                }
            }
        }
    }
    // date ���ץ���󡣹����������ɲá�
    $date = '';
    if ($params['date']) {
        $date = format_date(get_filetime($page));
    }
    // new ���ץ����New! ɽ�����ɲá�
    $new = '';
    if ($params['new']) {
        global $_plugin_new_elapses;
        $timestamp = get_filetime($page) - LOCALZONE;
        $erapse = UTIME - $timestamp;
        foreach ($_plugin_new_elapses as $limit=>$tag) {
            if ($erapse <= $limit) {
                $new .= sprintf($tag, get_passage($timestamp));
                break;
            }
        }
    }

    plugin_ls2_1_list_push($params, $level);

    // LI TAG. display ���ץ����˰ͤ롣plugin_ls2_1_list_push �ˤ⡣
    if ($params['display'] == 'inline') {
        $litag = '';
    } else {
        $litag = '<li>';
    }
    array_push($params['result'],$litag);

    // include ���줿�ڡ����ξ��
    if ($include) {
        $ret = 'include ';
    } else {
        $ret = '';
    }
    // ���Ǥ�ɽ���Ѥߤʤ�ɬ���ե�������õ�������Ϥ�����ȴ����
    if ($is_done) {
        $ret .= '<a href="' . $href . '" title="' . $title . '">' . $s_page . '</a> ';
        $ret .= '<a href="#list_' . $params["page_$page"] . '"><sup>&uarr;</sup></a>';
        array_push($params['result'], $ret);
        return;
    }

    $ret .= '<a id="list_' . $params["page_$page"] . '" href="' . $href . '" title="' . $title . '">' . $s_page . '</a>';
    if ($date != '') {
        $ret .= " $date";
    }
    if ($new != '') {
        $ret .= " $new";
    }
    array_push($params['result'], $ret);

    // title ���ץ����include ���ץ������ϥե�������õ���⤹��
    if ($params['title'] || $params['include']) {
        $anchor = PLUGIN_LS2_1_ANCHOR_ORIGIN;
        $matches = array();
        // ���Τ� title_number �ĤǤϤʤ��ƥե�����ñ�̤� title_number ��
        $title_counter = 0;
        foreach (get_source($page) as $line) {
            if ($params['title'] && preg_match('/^(\*{1,3})/', $line, $matches)) {
                if ($params['title_number']) {
                    // �����η�����¤ʤΤ������ȴ���Ƥ� $anchor �ˤ�������ϤǤʤ��Ϥ�
                    if ($title_counter >= $params['title_number']) {
                        if (! $params['include']) {
                            break;
                        } else {
                            continue;
                        }
                    }
                    $title_counter++;
                }

                // $line �� 'remove footnotes and HTML tags' ����롣���Ф��ԤΥ��󥫡����֤���뤬��ľ����ʤ���
                $id = make_heading($line);
                $hlevel = strlen($matches[1]);
                $id = PLUGIN_LS2_1_ANCHOR_PREFIX . $anchor++;
                plugin_ls2_1_list_push($params, $level + $hlevel);
                array_push($params['result'], $litag);
                array_push($params['result'], '<a href="' . $href . $id . '">' . $line . '</a>');
            } else if ($params['include'] &&
            preg_match('/^#include\((.+)\)/', $line, $matches) &&
            is_page($matches[1])) {
                plugin_ls2_1_get_headings($matches[1], $params, $level + $hlevel + 1, true, $prefix, $top_level, $pages);
            }
        }
    }
}
//<ul> �� </li></ul> ��Ŭ���������롣
function plugin_ls2_1_list_push(&$params, $level)
{
    global $_ul_left_margin, $_ul_margin, $_list_pad_str;

    $result = & $params['result']; // �Хåե����������Ϥ��뤳�Ȥˤʤ롣
    $saved  = & $params['saved'];  // �Ĥ��ʤ���Ф����ʤ�ʸ���� </ul> �򤿤��廊�Ƥ�����
    if ($params['display'] == 'inline') {
        $ulopen = $params['inline_before'];
        $ulclose = $params['inline_after'];
        $liclose = $params['inline_delimiter'];
    } else {
        //$ulopen   = '<ul class="' . PLUGIN_LS2_1_CSS_CLASS . '"%s>';
        $ulopen   = '<ul%s>';
        $ulclose  = "</li>\n</ul>";
        $liclose  = '</li>';
    }

    // ���Ф��ˤ� flat ������
    // if ($params['display'] == 'inline' || $params['display'] == 'flat') {
    //�����Ф��ˤ� flat �������ʤ�
    if ($params['display'] == 'inline') {
        // ������������ˤ���ΤϤ��줷���ʤ������ޤȤ�Ƥ��������ä���
        if ( count($saved) < 1 ) {
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
        while (count($saved) > $level || (! empty($saved) && $saved[0] != $ulclose)) {
            array_push($result, array_shift($saved));
        }

        $margin = $level - count($saved);

        // count($saved)�����䤹
        while (count($saved) < ($level - 1)) array_unshift($saved, '');

        if (count($saved) < $level) {
            array_unshift($saved, $ulclose);

            $left = ($level == $margin) ? $_ul_left_margin : 0;

            if ($params['title_compact']) {
                $left  += $_ul_margin;   // �ޡ���������
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
function plugin_ls2_1_depth_option_analysis($arg)
{
    $low = 0;
    $high = 0;
    if (!preg_match('/^\d*\-?\d*$/', $arg) or $arg == '') {
        return false;
    }

    if (substr_count($arg, "-")) {
        // \d-\d �ξ��
        list($low, $high) = split("-", $arg, 2);
    } elseif (substr_count($arg, "+")) {
        // \d+\d �ξ��
        list($low, $high) = split("+", $arg, 2);
        $high += $low;
    } else {
        // \d �����ξ��
        $low = $high = $arg;
    }
    return array($low, $high);
}
// ���ץ���� -?\d+ ����ϡ�
function plugin_ls2_1_number_option_analysis($arg)
{
    $head = 0;
    $tail = 0;
    if (!preg_match('/^-?\d+$/', $arg) or $arg == '') {
        return false;
    }
    if (substr_count($arg, "-")) {
        // - ��������
        $tail = substr($arg, 1);
    } else {
        $head = $arg;
    }
    return array($head, $tail);
}
// ���ե����ȴؿ�
function plugin_ls2_1_timecmp($a, $b)
{
    $atime = filemtime(get_filename($a));
    $btime = filemtime(get_filename($b));

    if ($atime == $btime) {
        return 0;
    }
    return ($atime < $btime) ? 1 : -1;
}

// true or false ���ͤ���ĥ��ץ�������Ϥ���
function plugin_ls2_1_check_params($args, $params)
{
    foreach ($args as $value) {
        list($key, $val) = split("=", $value);
        if (isset($params[$key])) {
            if ($val == '' || $val == "true") {
                $params[$key] = true;
            } elseif ($val == "false") {
                $params[$key] = false;
            }
        }
    }
    return $params;
}

// ����¾���ͤ���ĥ��ץ�������Ϥ���
function plugin_ls2_1_check_argparams($args, $params)
{
    foreach ($args as $value) {
        list($key, $val) = split("=", $value);
        if (isset($params[$key])) {
            $params[$key] = $val;
        }
    }
    return $params;
}

// ����¾�ΰ�����������ͤ� HTML �˽��Ϥ���륪�ץ�������Ϥ��� (�� htmlspecialchars)
function plugin_ls2_1_check_arghtmlparams($args, $params)
{
    foreach ($args as $value) {
        list($key, $val) = split("=", $value);
        if (isset($params[$key])) {
            $params[$key] = htmlspecialchars($val);
        }
    }
    return $params;
}
?>
