#!/usr/local/bin/php
<?php
header("Content-type: text/plain");

$dirs = array("./attach","./backup","./counter","./cache","./diff","./trackback","./wiki");

foreach ($dirs as $dir)
{
	print "process $dir\n";
	if (!$dp = opendir($dir))
	{
		print "cannot opendir $dir.";
		return;
	}
	if (!chmod($dir,0777))
	{
		print "cannot chmod $dir to 0777.\n";
	}	
	while ($file = readdir($dp))
	{
		if ($file{0} == '.')
		{
			continue;
		}

		$dir_file = "$dir/$file";

		if (!chmod($dir_file,0666))
		{
			print "cannot chmod $dir_file to 0666.\n";
		}
	}
}

print "done.\n";

exit;
?>
