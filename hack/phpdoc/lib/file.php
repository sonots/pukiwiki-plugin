<?php
/** 
 * File related functions
 *
 * PukiWiki - Yet another WikiWikiWeb clone.
 *
 * Plus!NOTE:(policy)not merge official cvs(1.77->1.78) See Question/181
 *
 * @version $Id: file.php,v 1.78.31 2007/06/17 16:56:00 upk Exp $
 * @copyright Copyright (C)
 *  - 2005-2007 PukiWiki Plus! Team
 *  - 2002-2007 PukiWiki Developers Team
 *  - 2001-2002 Originally written by yu-ji
 * @license http://www.gnu.org/licenses/gpl.html 
 *          License: GPL v2 or (at your option) any later version
 * @package pukiwiki
 */

/**
 * Maximum number in RecentChanges
 */
define('PKWK_MAXSHOW_ALLOWANCE', 10);
/**
 * RecentChanges cache file
 */
define('PKWK_MAXSHOW_CACHE', 'recent.dat');

/**
 * XHTML entities
 */
define('PKWK_ENTITIES_REGEX_CACHE', 'entities.dat');

/**
 * AutoLink cache file
 */
define('PKWK_AUTOLINK_REGEX_CACHE',  'autolink.dat');
/**
 * AutoAlias cache file
 */
define('PKWK_AUTOALIAS_REGEX_CACHE', 'autoalias.dat');
/**
 * Glossary cache file
 */
define('PKWK_GLOSSARY_REGEX_CACHE',  'glossary.dat');
/**
 * AutoBaseAlias cache file
 */
define('PKWK_AUTOBASEALIAS_CACHE', 'autobasealias.dat');

/**
 * Get source(wiki text) data of the page
 * 
 * @param string $page pagename
 * @param boolean $lock lock file on reading
 * @param boolean $join join lines into a string
 * @return array|string lines of source (each line have "\n" at the end) and a joined source. 
 */
function get_source($page = NULL, $lock = TRUE, $join = FALSE)
{
	$result = $join ? '' : array();

	if (is_page($page)) {
		$path  = get_filename($page);

		if ($lock) {
			$fp = @fopen($path, 'r');
			if ($fp == FALSE) return $result;
			@flock($fp, LOCK_SH);
		}

		if ($join) {
			// Returns a value
			if (filesize($path) > 0) {
				$result = str_replace("\r", '', fread($fp, filesize($path)));
			}
		} else {
			// Returns an array
			// Removing line-feeds: Because file() doesn't remove them.
			$result = str_replace("\r", '', file($path));
		}

		if ($lock) {
			@flock($fp, LOCK_UN);
			@fclose($fp);
		}
	}

	return $result;
}

/**
 * Get last-modified filetime of the page
 *
 * @param string $page pagename
 * @return int filemtime or 0 if page does not exist
 */
function get_filetime($page)
{
	return is_page($page) ? filemtime(get_filename($page)) : 0;
}

/**
 * Get physical file name of the page
 *
 * @param string $page pagename
 * @return string encoded local path file name
 */
function get_filename($page)
{
	return DATA_DIR . encode($page) . '.txt';
}

/**
 * Put a data(wiki text) into a physical file(diff, backup, text)
 *
 * @param string $page
 * @param string $postdata contents to be written
 * @param boolean $notimestamp do not update timestamp
 * @return void
 * @uses is_page
 * @uses make_str_rules
 * @uses do_diff
 * @uses get_this_time_links
 * @uses postdata_write
 * @uses file_write
 * @uses make_backup
 * @uses tb_send
 * @uses links_update
 * @uses autolink_pattern_write
 * @uses get_autolink_pattern
 * @uses get_autoglossaries
 * @uses get_glossary_pattern
 * @uses log_write
 * @global boolean trackback. TrackBack is enabled or not
 * @global boolean autoalias. AutoAlias is enabled or not
 * @global string aliaspage. AutoAlias config page (usually AutoAliasname)
 * @global boolean autoglossary. AutoGlossary is enabled or not
 * @global string glossarypage. AugoGlossary config page (usually Glossary)
 * @global array use_spam_check. Spam check config (enabled or not)
 */
function page_write($page, $postdata, $notimestamp = FALSE)
{
	global $trackback, $autoalias, $aliaspage,
	       $autoglossary, $glossarypage,
	       $use_spam_check;

	// if (PKWK_READONLY) return; // Do nothing
	if (auth::check_role('readonly')) return; // Do nothing

	if (is_page($page)) {
		$oldpostdata = get_source($page, TRUE, TRUE);
	} else {
		if (auth::is_check_role(PKWK_CREATE_PAGE))
			die_message(_('PKWK_CREATE_PAGE prohibits editing'));
		$oldpostdata = '';
	}

	$postdata = make_str_rules($postdata);

	// Create and write diff
	$diffdata    = do_diff($oldpostdata, $postdata);

	$role_adm_contents = auth::check_role('role_adm_contents');
	$links = array();
	if ( ($trackback > 1) || ( $role_adm_contents && $use_spam_check['page_contents']) ) {
		$links = get_this_time_links($postdata, $diffdata);
	}

	// Blocking SPAM
	if ($role_adm_contents) {
		if ($use_spam_check['page_remote_addr'] && SpamCheck($_SERVER['REMOTE_ADDR'],'ip')) {
			die_message('Writing was limited by IPBL (Blocking SPAM).');
		}
		if ($use_spam_check['page_contents'] && SpamCheck($links)) {
			die_message('Writing was limited by DNSBL (Blocking SPAM).');
		}
	}

	// Logging postdata
	postdata_write();

	// Create diff text
	file_write(DIFF_DIR, $page, $diffdata);

	// Create backup
	make_backup($page, $postdata == ''); // Is $postdata null?

	// Create wiki text
	file_write(DATA_DIR, $page, $postdata, $notimestamp);

	if ($trackback > 1) {
		// TrackBack Ping
		tb_send($page, $links);
	}

	unset($oldpostdata,$diffdata,$links);
	links_update($page);

	// Update autoalias.dat (AutoAliasName)
	if ($autoalias && $page == $aliaspage) {
		$aliases = get_autoaliases();
		if (empty($aliases)) {
			// Remove
			@unlink(CACHE_DIR . PKWK_AUTOALIAS_REGEX_CACHE);
		} else {
			// Create or Update
			autolink_pattern_write(CACHE_DIR . PKWK_AUTOALIAS_REGEX_CACHE,
				get_autolink_pattern(array_keys($aliases), $autoalias));
		}
	}

	// Update glossary.dat (AutoGlossary)
	if ($autoglossary && $page == $glossarypage) {
		$words = get_autoglossaries();
		if (empty($words)) {
			// Remove
			@unlink(CACHE_DIR . PKWK_GLOSSARY_REGEX_CACHE);
		} else {
			// Create or Update
			autolink_pattern_write(CACHE_DIR . PKWK_GLOSSARY_REGEX_CACHE,
				get_glossary_pattern(array_keys($words), $autoglossary));
		}
	}

	log_write('update',$page);
}

/**
 * Get newly added links from diff format text
 *
 * @param string $diffdata diff format text such as
 * <pre>
 * - minus
 * + plus
 * </pre>
 * @return array|null array $links or null if no link
 * @uses get_diff_lines
 * @global string script. PukiWiki script URI
 */
function get_link_list($diffdata)
{
	global $script;

	$links = array();

	list($plus, $minus) = get_diff_lines($diffdata);

	// Get URLs from <a>(anchor) tag from convert_html()
	$plus  = convert_html($plus); // WARNING: heavy and may cause side-effect
	preg_match_all('#href="(https?://[^"]+)"#', $plus, $links, PREG_PATTERN_ORDER);
	$links = array_unique($links[1]);

	// Reject from minus list
	if ($minus != '') {
		$links_m = array();
		$minus = convert_html($minus); // WARNING: heavy and may cause side-effect
		preg_match_all('#href="(https?://[^"]+)"#', $minus, $links_m, PREG_PATTERN_ORDER);
		$links_m = array_unique($links_m[1]);

		$links = array_diff($links, $links_m);
	}

	unset($plus,$minus);

	// Reject own URL (Pattern _NOT_ started with '$script' and '?')
	$links = preg_grep('/^(?!' . preg_quote($script, '/') . '\?)./', $links);

	// No link, END
	if (! is_array($links) || empty($links)) return;

	return $links;
}

/**
 * Get diff lines from diff format text
 *
 * @param string $diffdata diff format text
 * <pre>
 * - minus
 * + plus
 * </pre>
 * @return array array($plus, $minus)
 *  - string $plus +lines
 *  - string $minus -lines
 */
function get_diff_lines($diffdata)
{
	$_diff = explode("\n", $diffdata);
	$plus  = join("\n", preg_replace('/^\+/', '', preg_grep('/^\+/', $_diff)));
	$minus = join("\n", preg_replace('/^-/',  '', preg_grep('/^-/',  $_diff)));
	unset($_diff);
	return array($plus, $minus);
}

/**
 * Get links in a wiki source text after replacing some plugins into null plugin
 *
 * Used when TrackBack Ping and SPAM Check are processed
 *
 * @param string $data a wiki source text
 * @return array array $links
 * @global array exclude_link_plugin. plugins to be replaced into null plugin
 */
function replace_plugin_link2null($data)
{
	global $exclude_link_plugin;

	$pattern = $replacement = array();
	foreach($exclude_link_plugin as $plugin) {
		$pattern[] = '/^#'.$plugin.'\(/i';
		$replacement[] = '#null(';
	}

	$exclude = preg_replace($pattern,$replacement, explode("\n", $data));
	$html = convert_html($exclude);
	preg_match_all('#href="(https?://[^"]+)"#', $html, $links, PREG_PATTERN_ORDER);
	$links = array_unique($links[1]);
	unset($except, $html);
	return $links;
}

/**
 * Get newly added links this time
 * 
 * @param string $post a posted new wiki source text
 * @param string $diff a diff format text (diff between old and new source)
 * @return array array $links
 * @uses replace_plugin_link2null
 * @uses get_link_list
 * @see do_diff
 */
function get_this_time_links($post,$diff)
{
	$links = array();
	$post_links = (array)replace_plugin_link2null($post);
	$diff_links = (array)get_link_list($diff);

	foreach($diff_links as $d) {
		foreach($post_links as $p) {
			if ($p == $d) {
				$links[] = $p;
				break;
			}
		}
	}
	unset($post_links, $diff_links);
	return $links;
}

/**
 * Modify original text with user-defined / system-defined rules
 *
 * @param string $source wiki source text
 * @return string replaced text
 * @global string str_rules. Replacing rules. 
 * @global boolean fixed_heading_anchor. Fixed heading anchor is enabled or not. 
 * @see rules.ini.php for $str_rules
 * @see pukiwiki.ini.php for $fixed_heading_anchor
 */
function make_str_rules($source)
{
	global $str_rules, $fixed_heading_anchor;

	$lines = explode("\n", $source);
	$count = count($lines);

	$modify    = TRUE;
	$multiline = 0;
	$matches   = array();
	for ($i = 0; $i < $count; $i++) {
		$line = & $lines[$i]; // Modify directly

		// Ignore null string and preformatted texts
		if ($line == '' || $line{0} == ' ' || $line{0} == "\t") continue;

		// Modify this line?
		if ($modify) {
			if (! PKWKEXP_DISABLE_MULTILINE_PLUGIN_HACK &&
			    $multiline == 0 &&
			    preg_match('/^#[^{]*(\{\{+)\s*$/', $line, $matches)) {
			    	// Multiline convert plugin start
				$modify    = FALSE;
				$multiline = strlen($matches[1]); // Set specific number
			}
		} else {
			if (! PKWKEXP_DISABLE_MULTILINE_PLUGIN_HACK &&
			    $multiline != 0 &&
			    preg_match('/^\}{' . $multiline . '}\s*$/', $line)) {
			    	// Multiline convert plugin end
				$modify    = TRUE;
				$multiline = 0;
			}
		}
		if ($modify === FALSE) continue;

		// Replace with $str_rules
		foreach ($str_rules as $pattern => $replacement)
			$line = preg_replace('/' . $pattern . '/', $replacement, $line);
		
		// Adding fixed anchor into headings
		if ($fixed_heading_anchor &&
		    preg_match('/^(\*{1,3}.*?)(?:\[#([A-Za-z][\w-]*)\]\s*)?$/', $line, $matches) &&
		    (! isset($matches[2]) || $matches[2] == '')) {
			// Generate unique id
			$anchor = generate_fixed_heading_anchor_id($matches[1]);
			$line = rtrim($matches[1]) . ' [#' . $anchor . ']';
		}
	}

	// Multiline part has no stopper
	if (! PKWKEXP_DISABLE_MULTILINE_PLUGIN_HACK &&
	    $modify === FALSE && $multiline != 0)
		$lines[] = str_repeat('}', $multiline);

	return implode("\n", $lines);
}

/**
 * Generate fixed heading anchor id randomly
 *
 * @param string $seed usually heading strings
 * @return string A random alphabetic letter + 7 letters of random strings
 */
function generate_fixed_heading_anchor_id($seed)
{
	// A random alphabetic letter + 7 letters of random strings from md()
	return chr(mt_rand(ord('a'), ord('z'))) .
		substr(md5(uniqid(substr($seed, 0, 100), TRUE)),
		mt_rand(0, 24), 7);
}

/**
 * Read top N lines as an array
 *
 * @param string  $file filename
 * @param int     $count number of executed fgets, generally number of lines
 * @param boolean $lock use lock or not 
 * @param int     $buffer number of bytes to be read in one fgets
 * @see file() Use PHP file() function if you want to get ALL lines
 * @return array lines of contents
 */
function file_head($file, $count = 1, $lock = TRUE, $buffer = 8192)
{
	$array = array();

	$fp = @fopen($file, 'r');
	if ($fp === FALSE) return FALSE;
	set_file_buffer($fp, 0);
	if ($lock) @flock($fp, LOCK_SH);
	rewind($fp);
	$index = 0;
	while (! feof($fp)) {
		$line = fgets($fp, $buffer);
		if ($line != FALSE) $array[] = $line;
		if (++$index >= $count) break;
	}
	if ($lock) @flock($fp, LOCK_UN);
	if (! fclose($fp)) return FALSE;

	return $array;
}

/**
 * Output to a file
 *
 * @param string $dir directory such as DATA_DIR, DIFF_DIR
 * @param string $page pagename
 * @param string $str contents to be written
 * @param boolean $notimestamp do not update timestamp
 * @return void
 * @uses auth::check_role
 * @uses string_bracket
 * @uses encode
 * @uses file_exists
 * @uses add_recent
 * @uses lastmodified_add
 * @uses is_page
 * @uses pkwk_touch_file
 * @uses pkwk_mail_notify
 */
function file_write($dir, $page, $str, $notimestamp = FALSE)
{
	global $update_exec;
	global $notify, $notify_diff_only, $notify_subject;
	global $notify_exclude;
	global $whatsdeleted, $maxshow_deleted;
	global $_string;

	// if (PKWK_READONLY) return; // Do nothing
	if (auth::check_role('readonly')) return; // Do nothing

	if ($dir != DATA_DIR && $dir != DIFF_DIR) die('file_write(): Invalid directory');

	$page      = strip_bracket($page);
	$file      = $dir . encode($page) . '.txt';
	$file_exists = file_exists($file);

	// ----
	// Delete?

	if ($dir == DATA_DIR && $str === '') {
		// Page deletion
		if (! $file_exists) return; // Ignore null posting for DATA_DIR

		// Update RecentDeleted (Add the $page)
		add_recent($page, $whatsdeleted, '', $maxshow_deleted);

		// Remove the page
		unlink($file);

		// Update RecentDeleted, and remove the page from RecentChanges
		lastmodified_add($whatsdeleted, $page);

		// Clear is_page() cache
		is_page($page, TRUE);
		return;
	} else if ($dir == DIFF_DIR && $str === " \n") {
		return; // Ignore null posting for DIFF_DIR
	}

	// ----
	// File replacement (Edit)

	if (! is_pagename($page))
		die_message(str_replace('$1', htmlspecialchars($page),
			str_replace('$2', 'WikiName', $_msg_invalidiwn)));

	$str = rtrim(preg_replace('/' . "\r" . '/', '', $str)) . "\n";
	$timestamp = ($file_exists && $notimestamp) ? filemtime($file) : FALSE;

	$fp = fopen($file, 'a') or die('fopen() failed: ' .
		htmlspecialchars(basename($dir) . '/' . encode($page) . '.txt') .	
		'<br />' . "\n" .
		'Maybe permission is not writable or filename is too long');
	set_file_buffer($fp, 0);
	@flock($fp, LOCK_EX);
	$last = ignore_user_abort(1);
	ftruncate($fp, 0);
	rewind($fp);
	fputs($fp, $str);
	ignore_user_abort($last);
	@flock($fp, LOCK_UN);
	fclose($fp);

	if ($timestamp) pkwk_touch_file($file, $timestamp);

	// Optional actions
	if ($dir == DATA_DIR) {
		if ($timestamp === FALSE) lastmodified_add($page);

		// Command execution per update
		if (defined('PKWK_UPDATE_EXEC') && PKWK_UPDATE_EXEC)
			system(PKWK_UPDATE_EXEC . ' > /dev/null &');
		elseif ($update_exec)
			system($update_exec . ' > /dev/null &');

	} else if ($dir == DIFF_DIR && $notify) {
		$notify_exec = TRUE;
		foreach ($notify_exclude as $exclude) {
			$exclude = preg_quote($exclude);
			if (substr($exclude, -1) == '.')
				$exclude = $exclude . '*';
			if (preg_match('/^' . $exclude . '/', $_SERVER["REMOTE_ADDR"])) {
				$notify_exec = FALSE;
				break;
			}
		}
		if ($notify_exec !== FALSE) {
			if ($notify_diff_only) $str = preg_replace('/^[^-+].*\n/m', '', $str);
			$summary['ACTION'] = 'Page update';
			$summary['PAGE']   = & $page;
			$summary['URI']    = get_script_uri() . '?' . rawurlencode($page);
			$summary['USER_AGENT']  = TRUE;
			$summary['REMOTE_ADDR'] = TRUE;
			pkwk_mail_notify($notify_subject, $str, $summary);
//			pkwk_mail_notify($notify_subject, $str, $summary) or
//				die('pkwk_mail_notify(): Failed');
		}
	}

	is_page($page, TRUE); // Clear is_page() cache
}

/**
 * Add a page to a recent page such as RecentChanges, RecentDeleted
 *
 * @param string $page page to be added to log
 * @param string $recentpage log page
 * @param string $subject additional comment
 * @param int $limit number of limits to be logged
 * @return void
 */
function add_recent($page, $recentpage, $subject = '', $limit = 0)
{
	// if (PKWK_READONLY || $limit == 0 || $page == '' || $recentpage == '' ||
	if (auth::check_role('readonly') || $limit == 0 || $page == '' || $recentpage == '' ||
		check_non_list($page)) return;

	// Load
	$lines = $matches = array();
	foreach (get_source($recentpage) as $line)
		if (preg_match('/^-(.+) - (\[\[.+\]\])$/', $line, $matches))
			$lines[$matches[2]] = $line;

	$_page = '[[' . $page . ']]';

	// Remove a report about the same page
	if (isset($lines[$_page])) unset($lines[$_page]);

	// Add
	array_unshift($lines, '-' . format_date(UTIME) . ' - ' . $_page .
		htmlspecialchars($subject) . "\n");

	// Get latest $limit reports
	$lines = array_splice($lines, 0, $limit);

	// Update
	$fp = fopen(get_filename($recentpage), 'w') or
		die_message('Cannot write page file ' .
		htmlspecialchars($recentpage) .
		'<br />Maybe permission is not writable or filename is too long');
	set_file_buffer($fp, 0);
	@flock($fp, LOCK_EX);
	rewind($fp);
	fputs($fp, '#freeze'    . "\n");
	fputs($fp, '#norelated' . "\n"); // :)
	fputs($fp, join('', $lines));
	@flock($fp, LOCK_UN);
	@fclose($fp);
}

/**
 * Update PKWK_MAXSHOW_CACHE(recent.dat) and $whatsnew(RecentChanges) (Light)
 * Use without $autolink
 *
 * @param string $update updated page, added to log file
 * @param string $remove deleted page, removed from log file
 * @return void
 * @global int maxshow. Number of logs in $whatsnew
 * @global string whatsnew. Whatsnew pagename (usually RecentChanges)
 * @global boolean autolink. AutoLink is enabled or not
 * @global boolean autobasealias. AutoBaseAlias is enabled or not
 * @uses put_lastmodified
 * @uses file_head
 * @uses pkwk_touch_file
 * @since PukiWiki 1.4.7
 */
function lastmodified_add($update = '', $remove = '')
{
	global $maxshow, $whatsnew, $autolink, $autobasealias;

	// AutoLink implimentation needs everything, for now
	if ($autolink || $autobasealias) {
		put_lastmodified(); // Try to (re)create ALL
		return;
	}

	if (($update == '' || check_non_list($update)) && $remove == '')
		return; // No need

	$file = CACHE_DIR . PKWK_MAXSHOW_CACHE;
	if (! file_exists($file)) {
		put_lastmodified(); // Try to (re)create ALL
		return;
	}

	// Read (keep the order of the lines)
	$recent_pages = $matches = array();
	foreach(file_head($file, $maxshow + PKWK_MAXSHOW_ALLOWANCE, FALSE) as $line)
		if (preg_match('/^([0-9]+)\t(.+)/', $line, $matches))
			$recent_pages[$matches[2]] = $matches[1];

	// Remove if it exists inside
	if (isset($recent_pages[$update])) unset($recent_pages[$update]);
	if (isset($recent_pages[$remove])) unset($recent_pages[$remove]);

	// Add to the top: like array_unshift()
	if ($update != '' && $update != $whatsnew && ! check_non_list($update))
		$recent_pages = array($update => get_filetime($update)) + $recent_pages;

	// Check
	if (count($recent_pages) < $maxshow) {
		put_lastmodified(); // Try to (re)create ALL
		return;
	}

	// Sort decending order of last-modification date(Pointed Question/119)
	arsort($recent_pages, SORT_NUMERIC);

	// Re-create PKWK_MAXSHOW_CACHE
	pkwk_touch_file($file);
	$fp = fopen($file, 'r+') or
		die_message('Cannot open ' . 'CACHE_DIR/' . PKWK_MAXSHOW_CACHE);
	set_file_buffer($fp, 0);
	@flock($fp, LOCK_EX);
	$last = ignore_user_abort(1);
	ftruncate($fp, 0);
	rewind($fp);
	foreach ($recent_pages as $page=>$time)
		fputs($fp, $time . "\t" . $page . "\n");
	ignore_user_abort($last);
	@flock($fp, LOCK_UN);
	@fclose($fp);

	// Update 'RecentChanges'
	$recent_pages = array_splice($recent_pages, 0, $maxshow);
	$file = get_filename($whatsnew);
	pkwk_touch_file($file);
	$fp = fopen($file, 'r+') or
		die_message('Cannot open ' . htmlspecialchars($whatsnew));
	set_file_buffer($fp, 0);
	@flock($fp, LOCK_EX);
	$last = ignore_user_abort(1);
	ftruncate($fp, 0);
	rewind($fp);
	foreach ($recent_pages as $page=>$time)
		fputs($fp, '-' . htmlspecialchars(format_date($time)) .
			' - ' . '[[' . htmlspecialchars($page) . ']]' . "\n");
	fputs($fp, '#norelated' . "\n"); // :)
	ignore_user_abort($last);
	@flock($fp, LOCK_UN);
	@fclose($fp);
}

/**
 * Update RecentChanges
 *
 * Update PKWK_AUTOLINK_REGEX_CACHE(autolink.dat) and PKWK_AUTOBASEALIS_CACHE(autobasealias.dat), too
 *
 * @global int maxshow. Number of logs in $whatsnew
 * @global string whatsnew. Whatsnew pagename (usually RecentChanges)
 * @global boolean autolink. AutoLink is enabled or not
 * @global boolean atuobasealias. AutoBaseAlias is enabled or not
 * @uses auth::check_role
 * @uses get_existpages
 */
function put_lastmodified()
{
	global $maxshow, $whatsnew, $autolink, $autobasealias;

	// if (PKWK_READONLY) return; // Do nothing
	if (auth::check_role('readonly')) return; // Do nothing

	// Get WHOLE page list
	$pages = get_existpages();

	// Check ALL filetime
	$recent_pages = array();
	foreach($pages as $page)
		if ($page != $whatsnew && ! check_non_list($page))
			$recent_pages[$page] = get_filetime($page);

	// Sort decending order of last-modification date
	arsort($recent_pages, SORT_NUMERIC);

	// Cut unused lines
	// BugTrack2/179: array_splice() will break integer keys in hashtable
	$count   = $maxshow + PKWK_MAXSHOW_ALLOWANCE;
	$_recent = array();
	foreach($recent_pages as $key=>$value) {
		unset($recent_pages[$key]);
		$_recent[$key] = $value;
		if (--$count < 1) break;
	}
	$recent_pages = & $_recent;

	// Re-create PKWK_MAXSHOW_CACHE
	$file = CACHE_DIR . PKWK_MAXSHOW_CACHE;
	pkwk_touch_file($file);
	$fp = fopen($file, 'r+') or
		die_message('Cannot open' . 'CACHE_DIR/' . PKWK_MAXSHOW_CACHE);
	set_file_buffer($fp, 0);
	@flock($fp, LOCK_EX);
	$last = ignore_user_abort(1);
	ftruncate($fp, 0);
	rewind($fp);
	foreach ($recent_pages as $page=>$time)
		fputs($fp, $time . "\t" . $page . "\n");
	ignore_user_abort($last);
	@flock($fp, LOCK_UN);
	@fclose($fp);

	// Create RecentChanges
	$file = get_filename($whatsnew);
	pkwk_touch_file($file);
	$fp = fopen($file, 'r+') or
		die_message('Cannot open ' . htmlspecialchars($whatsnew));
	set_file_buffer($fp, 0);
	@flock($fp, LOCK_EX);
	$last = ignore_user_abort(1);
	ftruncate($fp, 0);
	rewind($fp);
	foreach (array_keys($recent_pages) as $page) {
		$time      = $recent_pages[$page];
		$s_lastmod = htmlspecialchars(format_date($time));
		$s_page    = htmlspecialchars($page);
		fputs($fp, '-' . $s_lastmod . ' - [[' . $s_page . ']]' . "\n");
	}
	fputs($fp, '#norelated' . "\n"); // :)
	ignore_user_abort($last);
	@flock($fp, LOCK_UN);
	@fclose($fp);

	// For AutoLink
	if ($autolink) {
		autolink_pattern_write(CACHE_DIR . PKWK_AUTOLINK_REGEX_CACHE,
			get_autolink_pattern($pages, $autolink));
	}

	// AutoBaseAlias
	if ($autobasealias) {
		autobasealias_write(CACHE_DIR . PKWK_AUTOBASEALIAS_CACHE, $pages);
	}
}

/**
 * Update AutoBaseAlias data
 * 
 * @param string $filename log file, usually CACHE_DIR.PKWK_AUTOBASEALIAS_CACHE(autobasealias.dat)
 * @param array &$pages existpages
 * @return void
 * @global string autobasealias_nonlist. non_list (ignore pages) for AutoBaseAlias
 * @uses get_short_pagename
 * @see get_existpages
 * @see pukiwiki.ini.php for $autobasealias_nonlist
 */
function autobasealias_write($filename, &$pages)
{
	global $autobasealias_nonlist;
	$pairs = array();
	foreach ($pages as $page) {
		if (preg_match('/' . $autobasealias_nonlist . '/', $page)) continue;
		$base = get_short_pagename($page);
		if ($base !== $page) {
			if (! isset($pairs[$base])) $pairs[$base] = array();
			$pairs[$base][] = $page;
		}
	}
	$data = serialize($pairs);

	$fp = fopen($filename, 'w') or
			die_message('Cannot open ' . $filename . '<br />Maybe permission is not writable');
	set_file_buffer($fp, 0);
	@flock($fp, LOCK_EX);
	rewind($fp);
	fputs($fp, $data);
	@flock($fp, LOCK_UN);
	@fclose($fp);
}

/**
 * Update autolink data
 *
 * @param string $filename log file, usually CACHE_DIR.PKWK_AUTOLINK_REGEX_CACHE(autolink.dat)
 * @param array $autolink_pattern autolink patten to be saved
 * @see get_autolink_pattern
 * @see get_autoglossary_pattern
 * @return void
 */
function autolink_pattern_write($filename, $autolink_pattern)
{
	list($pattern, $pattern_a, $forceignorelist) = $autolink_pattern;

	$fp = fopen($filename, 'w') or
			die_message('Cannot open ' . $filename . '<br />Maybe permission is not writable');
	set_file_buffer($fp, 0);
	@flock($fp, LOCK_EX);
	rewind($fp);
	fputs($fp, $pattern   . "\n");
	fputs($fp, $pattern_a . "\n");
	fputs($fp, join("\t", $forceignorelist) . "\n");
	@flock($fp, LOCK_UN);
	@fclose($fp);
}

/**
 * Get elapsed date of the page
 *
 * @param string $page pagename
 * @param boolean $sw add <small> tag or not
 * @global boolean show_passage. get passage or not
 * @return string 
 * @uses get_filetime
 * @uses get_passage
 */
function get_pg_passage($page, $sw = TRUE)
{
	global $show_passage;
	if (! $show_passage) return '';

	$time = get_filetime($page);
	$pg_passage = ($time != 0) ? get_passage($time) : '';

	return $sw ? '<small>' . $pg_passage . '</small>' : ' ' . $pg_passage;
}

/**
 * Send Last-Modified HTTP header
 *
 * @param string $page pagename
 * @global boolean lastmod. Run this function or not
 * @return void
 */
function header_lastmod($page = NULL)
{
	global $lastmod;

	if ($lastmod && is_page($page)) {
		pkwk_headers_sent();
		header('Last-Modified: ' .
			date('D, d M Y H:i:s', get_filetime($page)) . ' GMT');
	}
}

/**
 * Get a page list of this wiki
 * 
 * @param string $dir directory name
 * @param string $ext common file extension
 * @return array pagenames
 */
function get_existpages($dir = DATA_DIR, $ext = '.txt')
{
	$aryret = array();

	$pattern = '((?:[0-9A-F]{2})+)';
	if ($ext != '') $ext = preg_quote($ext, '/');
	$pattern = '/^' . $pattern . $ext . '$/';

	$dp = @opendir($dir) or
		die_message($dir . ' is not found or not readable.');
	$matches = array();
	while ($file = readdir($dp))
		if (preg_match($pattern, $file, $matches))
			$aryret[$file] = decode($matches[1]);
	closedir($dp);

	return $aryret;
}

/**
 * Get PageReading(pronounce-annotated) data in an array()
 *
 * @return array associative array whose keys are pagenames and values are readings
 */
function get_readings()
{
	global $pagereading_enable, $pagereading_kanji2kana_converter;
	global $pagereading_kanji2kana_encoding, $pagereading_chasen_path;
	global $pagereading_kakasi_path, $pagereading_config_page;
	global $pagereading_config_dict;

	$pages = get_existpages();

	$readings = array();
	foreach ($pages as $page) 
		$readings[$page] = '';

	$deletedPage = FALSE;
	$matches = array();
	foreach (get_source($pagereading_config_page) as $line) {
		$line = chop($line);
		if(preg_match('/^-\[\[([^]]+)\]\]\s+(.+)$/', $line, $matches)) {
			if(isset($readings[$matches[1]])) {
				// This page is not clear how to be pronounced
				$readings[$matches[1]] = $matches[2];
			} else {
				// This page seems deleted
				$deletedPage = TRUE;
			}
		}
	}

	// If enabled ChaSen/KAKASI execution
	if($pagereading_enable) {

		// Check there's non-clear-pronouncing page
		$unknownPage = FALSE;
		foreach ($readings as $page => $reading) {
			if($reading == '') {
				$unknownPage = TRUE;
				break;
			}
		}

		// Execute ChaSen/KAKASI, and get annotation
		if($unknownPage) {
			switch(strtolower($pagereading_kanji2kana_converter)) {
			case 'chasen':
				if(! file_exists($pagereading_chasen_path))
					die_message('ChaSen not found: ' . $pagereading_chasen_path);

				$tmpfname = tempnam(realpath(CACHE_DIR), 'PageReading');
				$fp = fopen($tmpfname, 'w') or
					die_message('Cannot write temporary file "' . $tmpfname . '".' . "\n");
				foreach ($readings as $page => $reading) {
					if($reading != '') continue;
					fputs($fp, mb_convert_encoding($page . "\n",
						$pagereading_kanji2kana_encoding, SOURCE_ENCODING));
				}
				fclose($fp);

				$chasen = "$pagereading_chasen_path -F %y $tmpfname";
				$fp     = popen($chasen, 'r');
				if($fp === FALSE) {
					unlink($tmpfname);
					die_message('ChaSen execution failed: ' . $chasen);
				}
				foreach ($readings as $page => $reading) {
					if($reading != '') continue;

					$line = fgets($fp);
					$line = mb_convert_encoding($line, SOURCE_ENCODING,
						$pagereading_kanji2kana_encoding);
					$line = chop($line);
					$readings[$page] = $line;
				}
				pclose($fp);

				unlink($tmpfname) or
					die_message('Temporary file can not be removed: ' . $tmpfname);
				break;

			case 'kakasi':	/*FALLTHROUGH*/
			case 'kakashi':
				if(! file_exists($pagereading_kakasi_path))
					die_message('KAKASI not found: ' . $pagereading_kakasi_path);

				$tmpfname = tempnam(realpath(CACHE_DIR), 'PageReading');
				$fp       = fopen($tmpfname, 'w') or
					die_message('Cannot write temporary file "' . $tmpfname . '".' . "\n");
				foreach ($readings as $page => $reading) {
					if($reading != '') continue;
					fputs($fp, mb_convert_encoding($page . "\n",
						$pagereading_kanji2kana_encoding, SOURCE_ENCODING));
				}
				fclose($fp);

				$kakasi = "$pagereading_kakasi_path -kK -HK -JK < $tmpfname";
				$fp     = popen($kakasi, 'r');
				if($fp === FALSE) {
					unlink($tmpfname);
					die_message('KAKASI execution failed: ' . $kakasi);
				}

				foreach ($readings as $page => $reading) {
					if($reading != '') continue;

					$line = fgets($fp);
					$line = mb_convert_encoding($line, SOURCE_ENCODING,
						$pagereading_kanji2kana_encoding);
					$line = chop($line);
					$readings[$page] = $line;
				}
				pclose($fp);

				unlink($tmpfname) or
					die_message('Temporary file can not be removed: ' . $tmpfname);
				break;

			case 'none':
				$patterns = $replacements = $matches = array();
				foreach (get_source($pagereading_config_dict) as $line) {
					$line = chop($line);
					if(preg_match('|^ /([^/]+)/,\s*(.+)$|', $line, $matches)) {
						$patterns[]     = $matches[1];
						$replacements[] = $matches[2];
					}
				}
				foreach ($readings as $page => $reading) {
					if($reading != '') continue;

					$readings[$page] = $page;
					foreach ($patterns as $no => $pattern)
						$readings[$page] = mb_convert_kana(mb_ereg_replace($pattern,
							$replacements[$no], $readings[$page]), 'aKCV');
				}
				break;

			default:
				die_message('Unknown kanji-kana converter: ' . $pagereading_kanji2kana_converter . '.');
				break;
			}
		}

		if($unknownPage || $deletedPage) {

			asort($readings); // Sort by pronouncing(alphabetical/reading) order
			$body = '';
			foreach ($readings as $page => $reading)
				$body .= '-[[' . $page . ']] ' . $reading . "\n";

			page_write($pagereading_config_page, $body);
		}
	}

	// Pages that are not prounouncing-clear, return pagenames of themselves
	foreach ($pages as $page) {
		if($readings[$page] == '')
			$readings[$page] = $page;
	}

	return $readings;
}

/**
 * Get a list of encoded files (must specify a directory and a suffix)
 * 
 * @param string $dir directory name
 * @param string $ext common file extension
 * @return array file paths which each path is as "$dir . filename"
 */
function get_existfiles($dir, $ext)
{
	$pattern = '/^(?:[0-9A-F]{2})+' . preg_quote($ext, '/') . '$/';
	$aryret = array();
	$dp = @opendir($dir) or die_message($dir . ' is not found or not readable.');
	while ($file = readdir($dp))
		if (preg_match($pattern, $file))
			$aryret[] = $dir . $file;
	closedir($dp);
	return $aryret;
}

/**
 * Get a list of related pages of the page
 * 
 * @param string $page
 * @return array
 * @uses links_get_related_db
 * @global array vars. string $vars['page'] pagename
 * @global array related. If possible, get related pages generated by make_link(), too
 */
function links_get_related($page)
{
	global $vars, $related;
	static $links = array();

	if (isset($links[$page])) return $links[$page];

	// If possible, merge related pages generated by make_link()
	$links[$page] = ($page == $vars['page']) ? $related : array();

	// Get repated pages from DB
	$links[$page] += links_get_related_db($vars['page']);

	return $links[$page];
}

/**
 * _If needed_, re-create the file to change/correct ownership into PHP's
 * NOTE: Not works for Windows
 *
 * @param string $filename
 * @param boolean $preserve_time do not update timestamp
 * @return void
 * @since PukiWiki 1.4.6
 */
function pkwk_chown($filename, $preserve_time = TRUE)
{
	static $php_uid; // PHP's UID

	if (! isset($php_uid)) {
		if (extension_loaded('posix')) {
			$php_uid = posix_getuid(); // Unix
		} else {
			$php_uid = 0; // Windows
		}
	}

	// Lock for pkwk_chown()
	$lockfile = CACHE_DIR . 'pkwk_chown.lock';
	$flock = fopen($lockfile, 'a') or
		die('pkwk_chown(): fopen() failed for: CACHEDIR/' .
			basename(htmlspecialchars($lockfile)));
	// flock($flock, LOCK_EX) or die('pkwk_chown(): flock() failed for lock');
	@flock($flock, LOCK_EX);

	// Check owner
	$stat = stat($filename) or
		die('pkwk_chown(): stat() failed for: '  . basename(htmlspecialchars($filename)));
	if ($stat[4] === $php_uid) {
		// NOTE: Windows always here
		$result = TRUE; // Seems the same UID. Nothing to do
	} else {
		$tmp = $filename . '.' . getmypid() . '.tmp';

		// Lock source $filename to avoid file corruption
		// NOTE: Not 'r+'. Don't check write permission here
		$ffile = fopen($filename, 'r') or
			die('pkwk_chown(): fopen() failed for: ' .
				basename(htmlspecialchars($filename)));

		// Try to chown by re-creating files
		// NOTE:
		//   * touch() before copy() is for 'rw-r--r--' instead of 'rwxr-xr-x' (with umask 022).
		//   * (PHP 4 < PHP 4.2.0) touch() with the third argument is not implemented and retuns NULL and Warn.
		//   * @unlink() before rename() is for Windows but here's for Unix only
		// flock($ffile, LOCK_EX) or die('pkwk_chown(): flock() failed');
		@flock($ffile, LOCK_EX);
		$result = touch($tmp) && copy($filename, $tmp) &&
			($preserve_time ? (touch($tmp, $stat[9], $stat[8]) || touch($tmp, $stat[9])) : TRUE) &&
			rename($tmp, $filename);
		// flock($ffile, LOCK_UN) or die('pkwk_chown(): flock() failed');
		@flock($ffile, LOCK_UN);

		fclose($ffile) or die('pkwk_chown(): fclose() failed');

		if ($result === FALSE) @unlink($tmp);
	}

	// Unlock for pkwk_chown()
	// flock($flock, LOCK_UN) or die('pkwk_chown(): flock() failed for lock');
	@flock($flock, LOCK_UN);
	fclose($flock) or die('pkwk_chown(): fclose() failed for lock');

	return $result;
}

/**
 * touch() with trying pkwk_chown()
 *
 * @param string $filename
 * @param int $time mtime
 * @param int $atime atime
 * @return void
 * @uses pkwk_chown
 * @since PukiWiki 1.4.6
 */
function pkwk_touch_file($filename, $time = FALSE, $atime = FALSE)
{
	// Is the owner incorrected and unable to correct?
	if (! file_exists($filename) || pkwk_chown($filename)) {
		if ($time === FALSE) {
			$result = touch($filename);
		} else if ($atime === FALSE) {
			$result = touch($filename, $time);
		} else {
			$result = touch($filename, $time, $atime);
		}
		return $result;
	} else {
		die('pkwk_touch_file(): Invalid UID and (not writable for the directory or not a flie): ' .
			htmlspecialchars(basename($filename)));
	}
}
?>
