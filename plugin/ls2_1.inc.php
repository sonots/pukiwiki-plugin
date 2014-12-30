<?php 
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: ls2_1.inc.php 137 2006-08-03 15:46:02Z sonots $

/*
*説明 [#m17ed6bb]
[[ls2>PukiWiki/1.4/マニュアル/プラグイン/l#d2ce34ea]] 拡張((ls2 v1.23 の拡張です。))。[[自作プラグイン/ls3.inc.php]] とは違い、
ページ名による階層構造だけでリストする純粋な ls2 の拡張です。
MenuBar に #ls2_1(hogehoge/,depth=1,relative) のようにおいておくと便利です。
**標準プラグイン ls2 からの変更点(初版) [#k9cb86b6]
-階層指定可能。
-階層的リスト表示機能。
-相対パス的表示機能。
-pukiwiki.ini.php で設定する $non_list の利用。
-include の無限ループを修正。
-link 時に常に include, title オプションが付加されていたが、単純に同時指定したオプションを利用するように変更。
それに伴いリンク名として利用される引数を「オプションと判定されない引数以降のすべての引数」から link=リンク名 と指定するように変更
((ls2_1.inc.php v1.18 からです。それ以前は「オプションと判定されない最初の引数」でした))。
**その後の追加機能 [#u2116eb9]
-表示件数指定機能
-除外ページ指定機能
-更新日時表示機能
-New 表示機能
-更新日時によるソート機能
-正規表現によるページのフィルタ機能
-インライン表示機能。

*書式 [#jcb5f796]
 #ls2_1(パターン[,オプション])

 &ls2_1(パターン[,オプション]);
インラインプラグイン時は強制的に display=inline となります。link オプションも可能です。

//index.php?plugin=ls2_1
//true 指定は 1。例:relative=1, false 指定は '' 例:relative= (内部的にも false ではなく '' になっている手の抜きよう)。デフォルト動作はオプション省略で。

**パラメータ [#r8a06bfd]
-パターン(最初に指定)
~リストするページ名のパターン。省略するときもカンマが必要。
省略時はカレントページ+"/"が指定されたことになる。
また / を指定した場合はすべてのページにマッチする。
また // を指定した場合は"カレントページ"が指定されたことになる(もろ後付け)。
-title=true|false
~ページ中の見出しもリストする。
title だけで title=true の意味になる。
-include=true|false
~インクルードしているページもリストする。include だけでも include=true の意味になる。
-link=リンク名
~actionプラグインを呼び出すリンクを表示。
link だけの場合は「パターン」の部分を使用したリンク名が作られる。
-reverse=true|false
~ページの並び順を反転し、降順にする。reverse だけでも reverse=true の意味にる。~
Note: hierarchy,relative コンビとの併用はきっと納得のいかない表示になります(昇順用に設計されたオプションなので)。
-compact=true|false
~リストのレベルを調整する。compact だけでも compact=true の意味になる。~
ファイル中の PLUGIN_LS2_1_LIST_COMPACT で初期値を設定できます。デフォルトでは TRUE です。
-title_compact=true|false
~title オプション用の compact 機能。title_compact だけでも title_compact=true の意味になる。~
ファイル中の PLUGIN_LS2_1_LIST_TITLE_COMPACT で初期値を設定できます。デフォルトでは TRUE です。
-depth=\d*[-+]?\d*((\d*[-+]?\d* は正規表現による表記です。\d は数字のことです。))
~階層指定。1 なら 1 階層下のページのみを表示する。
2-4 のような指定も可能 (2,3,4 の意)。2- のように指定すると 2 階層下以下のページ。
2+1 のような指定も可能 (2 とそこから 1 階層下。つまり 2,3 の意)。
//0-2 = false-2 = -2. 1-0 = 1-false = 1-. 2+ = 2+false = 2+0. +2 = false+2 = 0+2.
//0 または - または + は指定しないときと同じ。
//0 becomes false. - = false-false. + = false+false.
-relative=true|false
~相対パス的表示。relative だけでも relative=true の意味になる。~
ファイル中の PLUGIN_LS2_1_RELATIVE で初期値を設定できます。デフォルトでは FALSE です。
-display=hierarchy|flat|inline
~リスト表示形式の指定。hierarchy では見出しのレベルに応じた階層的リスト表示。
flat では見出しのレベルによらず平らに表示。inline では横一列に表示。~
ファイル中の PLUGIN_CONTENTS2_1_DISPLAY で初期値を設定できます。デフォルトは flat です。~
Note1: 下位互換性のため hierarchy, hierarchy=true でも display=hierarchy  になるようにしてあります。~
Note2: インライン型プラグインとして使用する場合は強制的に display=inline になります。~
Note3: 以前の動作とあわせるために、あえて見出しには display=flat が利かないようにしてあります。
-inline_before=文字列
~display=inline 時の前につける文字を設定。~
ファイル中の PLUGIN_LS2_1_DISPLAY_INLINE_BEFORE で初期値を設定できます。デフォルトでは '[ ' です。
-inline_delimiter=文字列
~display=inline 時の区切り文字を設定。~
ファイル中の PLUGIN_LS2_1_DISPLAY_INLINE_DELIMITER で初期値を設定できます。デフォルトでは ' | ' です。
-inline_after=文字列
~display=inline 時の後ろにつける文字を設定。~
ファイル中の PLUGIN_LS2_1_DISPLAY_INLINE_AFTER で初期値を設定できます。デフォルトでは ' ]' です。
-non_list=true|false
~pukiwiki.ini.php で定義される $non_list によるリスト排除。non_list だけでも non_list=true の意味になる。~
ファイル中の PLUGIN_LS2_1_NON_LIST で初期値を設定できます。デフォルトでは TRUE です。
-number=-?\d+ ((-?\d+ は正規表現による表記です。))
~リンク表示件数指定。Blog2プラグインを使用するときに便利らしいです。
number=10 で頭から10件表示します。number=-10 のように - をつけると後ろの10件になります。
それでも逆順にはならないので reverse を使用してください。
-title_number=\d+
~titleの表示件数指定。title_number=10で頭から10件表示します。
現在 - 機能はありません。title_number=1 でページの先頭見出しを表示することになります。
先頭見出しを必ず書く人は多いそうなのでそれを表示するのに便利かもしれません。
-except=正規表現
~リストしないページを正規表現にて指定。$non_list だけでは足りないときに使用。
relative の場合でもページ名全体で判定。~
ヒント： マッチングには [[ereg>http://php.s3.to/man/function.ereg.html]] を使用します。
except=Test|sample → Test または sample を含むページを除く。
-datesort=true|false
~更新日時順（新しいほど上)に表示。datesort だけでも datesort=true の意味になる。~
Note: hierarchy,relative コンビとの併用はきっと納得のいかない表示になります
(hierarchy,relative はページ名の昇順ソート時用のオプションなので)。~
Note2: include されるページに対しては無視なので include オプションと併用しても無駄です。~
Note3: 旧 new オプションです。注意してください。
-date=true|false
~ページの更新日時も表示。date だけでも date=true の意味になる。
-new=true|false
~&color(#ff0000){New!};も表示。new だけでも new=true の意味になる。~
New! が表示される条件は標準プラグイン new の条件を使用しています。
new プラグインが存在しない場合は独自設定（といっても new からコピーしたもの）を使用します。
-filter=正規表現
~ページパターンをさらに正規表現で限定する。
パターンを / (全ての意味) にしてこちらだけを使うのもあり。~
ヒント: マッチングには [[ereg>http://php.s3.to/man/function.ereg.html]] を使用します。
*/
// 見出しアンカーの書式
define('PLUGIN_LS2_1_ANCHOR_PREFIX', '#content_1_');
// 見出しアンカーの開始番号
define('PLUGIN_LS2_1_ANCHOR_ORIGIN', 0);
// 見出しレベルを調整する(デフォルト TRUE)
define('PLUGIN_LS2_1_LIST_COMPACT', true);
define('PLUGIN_LS2_1_LIST_TITLE_COMPACT', true);
// $non_list によるページ排除を使用する(デフォルト TRUE)
define('PLUGIN_LS2_1_NON_LIST', true);
// 相対パス的表示(デフォルト FALSE)
define('PLUGIN_LS2_1_RELATIVE', false);
// 階層的リスト表示(デフォルト FALSE)
define('PLUGIN_LS2_1_HIERARCHY', false);
//リスト表示形式(デフォルト 'flat')
define('PLUGIN_LS2_1_DISPLAY','flat');
//display=inline 時に前、間、後ろにつける文字
define('PLUGIN_LS2_1_DISPLAY_INLINE_BEFORE', '[ ');
define('PLUGIN_LS2_1_DISPLAY_INLINE_DELIMITER', ' | ');
define('PLUGIN_LS2_1_DISPLAY_INLINE_AFTER',  ' ]');
//CSSクラス設定
define('PLUGIN_LS2_1_CSS_CLASS','ls2_1');

function plugin_ls2_1_init()
{
    // できるだけ new プラグインの設定を用いる (new オプション用)
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
    // {} 部は使用しないはずなので取り除く。
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

    // true or false のオプション
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
    // その他の引数を持つオプション
    $argparams = array('depth' => false,
    'number' => false,
    'title_number' => false,
    'except' => false,
    'filter' => false,
    'display' => PLUGIN_LS2_1_DISPLAY,
    );
    // その他の引数を持ち、値が HTML に出力されるオプション( 要 htmlspecialchars )
    $arghtmlparams = array('link' => false,
    'inline_before' => PLUGIN_LS2_1_DISPLAY_INLINE_BEFORE,
    'inline_delimiter' => PLUGIN_LS2_1_DISPLAY_INLINE_DELIMITER,
    'inline_after' => PLUGIN_LS2_1_DISPLAY_INLINE_AFTER,
    );

    // prefix 処理
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


    // オプション解析
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

    // 出力
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
            // link オプション時

            // 出力を囲むタグ。もちろん通常はul, li タグです。
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
                // if ($val !== $default[$key]) とやったほうがスマートな気がするが $default がないのでしょうがない。
                $tmp[] = "$key=$val";
            }
            $ret = '<a href="' . $script . '?' . join('&amp;', $tmp) . '">' . $title . '</a>';
            return $open_plugintag . $ret . $close_plugintag;
        } else {
            // 通常時
            return plugin_ls2_1_show_lists($prefix, $params, $calledby);
        }
    }
}

// リスト作成
function plugin_ls2_1_show_lists($prefix, &$params, $calledby = 'convert') {
    global $_ls2_err_nopages;
    global $non_list;

    // inline プラグイン時は強制 display=inline。
    if ($calledby == 'inline') {
        $params['display'] = 'inline';
    }
    // hierarchy 下位互換用
    if ($params['display'] == PLUGIN_LS2_1_DISPLAY && $params['hierarchy']) {
        $params['display'] = 'hierarchy';
    }
    // depth オプション解析
    if ($params['depth']) {
        $params['prefixdepth'] = substr_count($prefix, "/") -1 ; //パターン文字列の階層数。$depthflag 判断に使用。
        list($params['lowdepth'], $params['highdepth']) = plugin_ls2_1_depth_option_analysis($params['depth']);
    }
    // number オプション解析
    if ($params['number']) {
        list($params['headnumber'], $params['tailnumber']) = plugin_ls2_1_number_option_analysis($params['number']);
    }
    // title_number オプション解析
    if ($params['title_number']) {
        list($params['title_number']) = plugin_ls2_1_number_option_analysis($params['title_number']);
    }
    // display オプション解析。おかしな値がわりあてられていた場合デフォルトに戻すだけ
    if($params['display'] != 'hierarchy' && $params['display'] != 'flat' && $params['display'] != 'inline') {
        $params['display'] = PLUGIN_LS2_1_DISPLAY;
    }
    // 出力を囲むタグ。display=inine に使用。もちろん通常はul, li タグです。
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
        // depth 制限
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
        // non_list 制限
        if ($params['non_list']) {
            $flag = !preg_match("/$non_list/", $_page);
            if (! $flag) continue;
        }
        // パターン制限
        if ($prefix !== '') {
            $flag = (strpos($_page, $prefix) === 0);
            if (! $flag) continue;
        }
        // except 制限
        if ($params['except']) {
            $except = $params['except'];
            $flag = ! ereg($except, $_page);
            if (! $flag) continue;
        }
        // filter 制限
        if ($params['filter']) {
            $filter = $params['filter'];
            $flag = ereg($filter, $_page);
            if (! $flag) continue;
        }
        // すべて TRUE なら追加。
        $pagestmp[] = $_page;
    }
    if ($params['datesort']) {
        // datesort オプション。更新日時順によるソート。plugin_ls2_1_timecmp は function
        usort($pagestmp, "plugin_ls2_1_timecmp");
    } else {
        // 通常はページ名でソート。
        natcasesort($pagestmp);
    }
    // 表示 number 制限
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
    // hierarchy オプション。リストレベル制御
    // 階層指定をしている場合は、リスト表示上でのトップがずれる。
    $top_level = 1;
    if ($params['depth']) {
        $top_level = $params['lowdepth'];
    }
    if ($top_level < 1) {
        $top_level = 1;
    }
    // 要改良: number=- と併用された場合は、$pages をすべて parse して一番階層が少ないページを見つけ、それを基準にする
    if ($params['display'] == 'hierarchy') {
        if ($params['compact']) {
            // パターンの最後の / 以下を取り除く。例) sample/test/d -> sample/test
            // $prefix_dir = preg_replace('/[^\/]+$/','',$prefix);
            if (($pos = strrpos($prefix, '/')) !== false) {
                $prefix_dir = substr($prefix, 0, $pos + 1);
            }
        }

        foreach ($pages as $page) {
            // level 計算
            if ($params['compact']) {
                // パターンをとりのぞく
                $tmp = substr($page, strlen($prefix_dir));
                $level = 1;
                // depth オプションが指定されていた場合 $top_level が変わります。
                while (substr_count($tmp, "/") > $top_level -1) {
                    // 一階層ずつとりのぞく
                    // $tmp = preg_replace('/\/[^\/]*$/','',$tmp);
                    if (($pos = strrpos($tmp, '/')) !== false) {
                        $tmp = substr($tmp, 0, $pos);
                    }
                    // compact なので上位のページが存在していればリスト階層レベルが増える
                    if (in_array($prefix_dir . $tmp, $pages)) {
                        $level++;
                    }
                }
            }
            // compact でない場合は上位のページの有無は問わないので / の数で十分。
            else {
                $level = substr_count($page, "/") - $params['prefixdepth'];
                // depth オプションが指定されていた場合 $top_level が変わります。
                if ($params['depth']) {
                    $level = $level - ($top_level -1);
                }
            }
            // 実態
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

    // すでにこのページの見出しを表示したかどうかのフラグ
    $is_done = (isset($params["page_$page"]) && $params["page_$page"] > 0);
    if (! $is_done) $params["page_$page"] = ++$_ls2_anchor;

    $s_page = htmlspecialchars($page);
    $title = $s_page . ' ' . get_pg_passage($page, false);
    $r_page = rawurlencode($page);
    $href = $script . '?' . $r_page;
    // relative オプション。リンク名制御。
    if ($params['relative']) {
        // パターンの最後の / 以下を取り除く。例) sample/test/d -> sample/test
        // $prefix_dir = preg_replace('/[^\/]+$/','',$prefix);
        if (($pos = strrpos($prefix, '/')) !== false) {
            $prefix_dir = substr($prefix, 0, $pos + 1);
        }
        // ページ名からそのパターンをとり除く。
        // $s_page = ereg_replace("^$prefix_dir",'',$s_page);
        $s_page = substr($s_page, strlen($prefix_dir));
        // relative オプションと hierarchy オプションが同時に指定された場合は
        // パターンを取り除くだけでなく、上位の存在しているページ名も取り除く。
        if ($params['display'] == 'hierarchy') {
            $tmp = $s_page;
            // depth オプションが指定されていた場合 $top_level が変わります。
            while (substr_count($tmp, "/") > $top_level -1) {
                // 一階層ずつとりのぞく
                if (($pos = strrpos($tmp, '/')) !== false) {
                    $tmp = substr($tmp, 0, $pos);
                }
                // 上位のページが存在していれば、その文字列を取り除き、相対名にする。
                if (in_array($prefix_dir . $tmp, $pages)) {
                    // $s_page = ereg_replace("^$tmp/",'',$s_page);
                    $s_page = substr($s_page, strlen("$tmp/"));
                    break;
                }
            }
        }
    }
    // date オプション。更新日時の追加。
    $date = '';
    if ($params['date']) {
        $date = format_date(get_filetime($page));
    }
    // new オプション。New! 表示の追加。
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

    // LI TAG. display オプションに依る。plugin_ls2_1_list_push にも。
    if ($params['display'] == 'inline') {
        $litag = '';
    } else {
        $litag = '<li>';
    }
    array_push($params['result'],$litag);

    // include されたページの場合
    if ($include) {
        $ret = 'include ';
    } else {
        $ret = '';
    }
    // すでに表示済みなら必ずファイル内探索処理はせずに抜ける
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

    // title オプション、include オプション時はファイル内探索もする
    if ($params['title'] || $params['include']) {
        $anchor = PLUGIN_LS2_1_ANCHOR_ORIGIN;
        $matches = array();
        // 全体で title_number 個ではなく各ファイル単位で title_number 個
        $title_counter = 0;
        foreach (get_source($page) as $line) {
            if ($params['title'] && preg_match('/^(\*{1,3})/', $line, $matches)) {
                if ($params['title_number']) {
                    // ただの件数制限なので途中で抜けても $anchor には不整合はでないはず
                    if ($title_counter >= $params['title_number']) {
                        if (! $params['include']) {
                            break;
                        } else {
                            continue;
                        }
                    }
                    $title_counter++;
                }

                // $line は 'remove footnotes and HTML tags' される。見出し行のアンカーが返されるが正直いらない。
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
//<ul> と </li></ul> を適宜挿入する。
function plugin_ls2_1_list_push(&$params, $level)
{
    global $_ul_left_margin, $_ul_margin, $_list_pad_str;

    $result = & $params['result']; // バッファ。これを出力することになる。
    $saved  = & $params['saved'];  // 閉じなければいけない文だけ </ul> をたくわえておく。
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

    // 見出しにも flat が利く
    // if ($params['display'] == 'inline' || $params['display'] == 'flat') {
    //　見出しには flat が利かない
    if ($params['display'] == 'inline') {
        // 初期化がここにあるのはうれしくないが、まとめておきたかった。
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

        // count($saved)を増やす
        while (count($saved) < ($level - 1)) array_unshift($saved, '');

        if (count($saved) < $level) {
            array_unshift($saved, $ulclose);

            $left = ($level == $margin) ? $_ul_left_margin : 0;

            if ($params['title_compact']) {
                $left  += $_ul_margin;   // マージンを固定
                $level -= ($margin - 1); // レベルを修正
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
// オプション \d?[+-]?\d? を解析。
function plugin_ls2_1_depth_option_analysis($arg)
{
    $low = 0;
    $high = 0;
    if (!preg_match('/^\d*\-?\d*$/', $arg) or $arg == '') {
        return false;
    }

    if (substr_count($arg, "-")) {
        // \d-\d の場合
        list($low, $high) = split("-", $arg, 2);
    } elseif (substr_count($arg, "+")) {
        // \d+\d の場合
        list($low, $high) = split("+", $arg, 2);
        $high += $low;
    } else {
        // \d だけの場合
        $low = $high = $arg;
    }
    return array($low, $high);
}
// オプション -?\d+ を解析。
function plugin_ls2_1_number_option_analysis($arg)
{
    $head = 0;
    $tail = 0;
    if (!preg_match('/^-?\d+$/', $arg) or $arg == '') {
        return false;
    }
    if (substr_count($arg, "-")) {
        // - がある場合
        $tail = substr($arg, 1);
    } else {
        $head = $arg;
    }
    return array($head, $tail);
}
// 日付ソート関数
function plugin_ls2_1_timecmp($a, $b)
{
    $atime = filemtime(get_filename($a));
    $btime = filemtime(get_filename($b));

    if ($atime == $btime) {
        return 0;
    }
    return ($atime < $btime) ? 1 : -1;
}

// true or false の値を持つオプションを解析する
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

// その他の値を持つオプションを解析する
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

// その他の引数を持ち、値が HTML に出力されるオプションを解析する (要 htmlspecialchars)
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
