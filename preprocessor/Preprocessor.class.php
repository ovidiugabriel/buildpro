<?php

class Preprocessor {
    private $INPUT;
    private $LINE_NUMBER;
    private $outfd;

    public function __construct($INPUT) {
        $this->INPUT = $INPUT;
    }

    public function error($n_tabs, $text) {
    }
    
    public function execute() {
    }
}
