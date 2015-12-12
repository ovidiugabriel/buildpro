<?php

function error_handler($code, $message, $file, $line) {
    global $INPUT, $LINE_NUMBER;

    ob_end_clean();

    // This is for good use only with C/C++ compiler
    // that's why is won't be a standard behavior to write this to the output file
    // but to the error console
    //
    echo "|\n";
    echo "| *** Compiler error: \n";
    echo "|\n";

    echo "#line {$LINE_NUMBER} \"{$INPUT}\" \n";
    echo "#error \"{$message}\"\n";
    die;
}

set_error_handler('error_handler');
