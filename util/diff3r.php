<?php
/**
 * diff3r: Apply diff3 command recursively
 *
 * Usage:
 *  php diff3r.php MYDIR OLDDIR YOURDIR OUTDIR [find options]
 *  
 * Requirement: find, diff3, php (on cygwin or Linux, etc)
 *
 * @author     sonots
 * @link       http://lsx.sourceforge.jp/?Util%2Fdiff3r.php
 * @license    http://www.gnu.org/licenses/gpl.html GPL v2
 * @version    $Id: diff3r.php,v 1.2 2007-07-14 11:14:46 sonots $
 * @package    diff3r
 */

// parse args
set_time_limit(0);
if ($argc < 4) {
	echo 'php diff3r.php MYDIR OLDDIR YOURDIR OUTDIR [find options]' . "\n";
	echo 'Please see $ man diff3 $ man find' . "\n";
	exit;
}
array_shift($argv);
$mydir   = array_shift($argv);
$olddir  = array_shift($argv);
$yourdir = array_shift($argv);
$outdir  = array_shift($argv);
$findopts = '-type f ' . implode(' ', $argv);
//$diff3opts = implode(" ", $argv);
$diff3opts = '-m --strip-trailing-cr';

// find YOURDIR
exec("find $yourdir $findopts", $yourfiles);
// remove $yourdir/ from $yourfiles
if ($yourdir[strlen($yourdir)-1] !== '/') { // Add '/'
    $yourdir .= '/';
}
foreach ($yourfiles as $i => $file) {
	$yourfiles[$i] = str_replace($yourdir, '', $file);
}

// diff3 recursively
exec("cp -r $mydir $outdir");
exec("touch /tmp/empty");
foreach ($yourfiles as $i => $file) {
	$myfile = $mydir . '/' . $file;
	$oldfile = $olddir . '/' . $file;
	$yourfile = $yourdir . '/' . $file;
	$outfile = $outdir . '/' . $file;
	// ToDo: deleted
	if (is_file($oldfile)) {
		if (is_file($myfile)) {
		} else {
			// $myfile = '/tmp/empty';
			continue;
		}
	} else {
		if (is_file($myfile)) {
			$oldfile = '/tmp/empty';
		} else {
			$myfile = $oldfile = '/tmp/empty';
		}
	}
    #echo "dirname $outfile | xargs mkdir -p" . "\n";
    exec("dirname $outfile | xargs mkdir -p");
	echo "diff3 $diff3opts $myfile $oldfile $yourfile > $outfile" . "\n";
	exec("diff3 $diff3opts $myfile $oldfile $yourfile > $outfile");
}
exec("rm /tmp/empty");
echo "Conflicted\n";
exec("find $outdir | xargs grep '<<<<<<'");
?>
