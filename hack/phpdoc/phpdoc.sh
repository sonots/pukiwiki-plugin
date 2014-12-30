#!/bin/sh
~/bin/phpdoc.sh -t phpdoc -o HTML:Smarty:HandS -pp on $*
#-o HTML:Smarty:PHP
#-d $dir
#-f $file
#./phpdoc_conv_charset.sh phpdoc
diff -u lib.orig lib > phpdoc.diff
