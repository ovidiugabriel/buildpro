<?php

/* ************************************************************************* */
/*                                                                           */
/*  Title:       default.lib.php                                             */
/*                                                                           */
/*  Created on:  06.03.2016 at 08:45:19                                      */
/*  Email:       ovidiugabriel@gmail.com                                     */
/*  Copyright:   (C) 2016 ICE Control srl. All Rights Reserved.              */
/*                                                                           */
/*  $Id$                                                                     */
/*                                                                           */
/* ************************************************************************* */

/* +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ */
/* History (Start).                                                          */
/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */
/*                                                                           */
/* Date         Name    Reason                                               */
/* ------------------------------------------------------------------------- */
/* 16.03.2016           Added include_path hint in error message             */
/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */
/* History (END).                                                            */
/* +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ */

/**
 *
 * @param  integer $code
 * @param  string $message
 * @param  string $file
 * @param  integer $line
 * @return void
 */
function error_handler($code, $message, $file, $line) {
    global $INPUT, $LINE_NUMBER;

    ob_end_clean();

    // This is for good use only with C/C++ compiler
    // that's why is won't be a standard behavior to write this to the output file
    // but to the error console
    //
    echo "/* Compiler error: */\n";
    echo "#line {$LINE_NUMBER} \"{$INPUT}\" \n";

    //
    // Show the include_path for efficient debug in case of require_* or include_* fails
    //
    if ( (2 == $code) && preg_match('/^(require|include)/', $message) ) {
        $message .= "; include_path=" . ini_get('include_path');
    }
    echo "#error \"{$message}\"\n";
    die;
}

set_error_handler('error_handler');

// EOF
