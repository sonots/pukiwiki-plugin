<?php
$lines = file($argv[1]);
// adjust all indents into the first met indent way
foreach ($lines as $i => $line) {
    if (strpos($line, "\t") === 0) {
        $replace = "\t";
        break;
    } elseif (strpos($line, '    ') === 0) {
        $replace = '    ';
        break;
    }
}
$search = ($replace === "\t") ? '    ' : "\t";

foreach ($lines as $i => $line) {
    preg_match('/^('.$search.')+/', $line, $matches);
    $count = substr_count($matches[0], $search);
    $lines[$i] = preg_replace('/^'.$matches[0].'/', str_repeat($replace, $count), $line); 
}
if (isset($argv[2])) {
    file_put_contents($argv[2], implode('', $lines));
} else {
    print implode('', $lines);
}
?>
