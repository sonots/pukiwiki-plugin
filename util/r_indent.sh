# exec as $ sh r_indent.sh . | xargs eval
find $* -name "*.inc.php" -exec echo "php indent.php {} {};" \;
