<?php

class Preprocessor {
    private $INPUT;
    private $LINE_NUMBER;
    private $outfd;
    private $stack = array();

    public function __construct($INPUT) {
        $this->INPUT = trim($INPUT, ".\\/");
    }

    /**
     * Emits a preprocessing error in the output file.
     *
     * @param integer $n_tabs
     * @param string $text
     * @return void
     */
    public function error($n_tabs, $text) {
        $n_tabs = (int) $n_tabs;
        //
        // C++ specific error.
        //
        fwrite($this->outfd, tab($n_tabs) . "#line {$this->LINE_NUMBER} \"{$this->INPUT}\" \n");
        fwrite($this->outfd, tab($n_tabs) . '#error '. $text . "\n");
    }
    
    public function execute() {
        $fp = fopen($this->INPUT, 'r');
        if ($fp) {
            $this->outfd = fopen($OUTPUT, 'w');

            fclose($this->outfd);
            fclose($fp);
        }
    }
}
