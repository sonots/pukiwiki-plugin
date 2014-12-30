<?php

if ($geshi->line_numbers) {
    $geshi->set_overall_style('background: #e7e7ef;', true);
    $geshi->set_line_style('margin-left: 3.5em;', true);
    $geshi->set_code_style('background: #f0f0f0; border-left: 1px solid #bbb;', true);
} else {
    $geshi->set_overall_style('background: #f0f0f0;', true);
}

?>
