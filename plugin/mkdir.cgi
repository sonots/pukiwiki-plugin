#!/usr/local/bin/php
<?php
/**
 * mkdir cgi
 *
 * $err = file_get_contents('http://.../mkdir.cgi?dir=DIRECTORY&mode=MODE&password=md5(PASSWORD)');
 * $err 
 *  '1'  # Success
 *  ''   # Failure
 *  '-1' # Password Mismatch
 *
 * @author     sonots <http://lsx.sourceforge.jp>
 * @license    http://www.gnu.org/licenses/gpl.html GPL v2
 * @link       http://lsx.sourceforge.jp/?Plugin%2Fstatichtml.inc.php
 * @version    $Id: mkdir.cgi,v 1.2 2008-05-27 16:53:22Z sonots $
 */
$GLOBALS['password'] = md5('');
$GLOBALS['chmod']    = TRUE;

main();
function main()
{
    if (! isset($_GET['dir'])) {
        print '1';
        exit;
    }
    $mode = isset($_GET['mode']) ? (int)base_convert($_GET['mode'], 8, 10) : 0757;
    if (is_dir($_GET['dir'])) {
        if ($GLOBALS['chmod']) chmod($_GET['dir'], $mode);
        print '1';
        exit;
    }
    if ($GLOBALS['password'] !== $_GET['password']
       || $GLOBALS['password'] == 'd41d8cd98f00b204e9800998ecf8427e' // Disable default
       ) {
        print '-1';
        exit;
    }
    print r_mkdir($_GET['dir'], $mode);
}

/** 
 * mkdir recursively (mkdir of PHP5 has recursive flag)
 *
 * @param string $dir
 * @param int $mode
 * @return boolean success or failure
 */
function r_mkdir($dir, $mode = 0755)
{
    if (is_dir($dir) || @mkdir($dir,$mode)) {
        chmod($dir, $mode); // for safe
        return TRUE;
    }
    if (! r_mkdir(dirname($dir),$mode)) return FALSE;
    return @mkdir($dir,$mode);
}
?>
