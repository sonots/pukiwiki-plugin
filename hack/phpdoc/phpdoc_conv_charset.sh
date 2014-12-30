#!/bin/sh
eval `find $1 -type f -name '*.html' -exec echo "sed 's/iso-8859-1/UTF-8/g' {} > /tmp/tmp; \mv /tmp/tmp {};" \;`
# To convert template files themselves
# cd [phpdoc template file dir]
# eval `find . -type f -name '*.html' -exec echo "sed 's/iso-8859-1/UTF-8/g' {} > /tmp/tmp; \mv /tmp/tmp {};" \;`

