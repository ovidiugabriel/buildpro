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
    
    /** 
     * @throws Exception
     */
    public function execute() {
        $fp = fopen($this->INPUT, 'r');
        if (!$fp) {
            throw new Exception("Could not open file: {$this->INPUT}");
        }

        $OUTPUT = 'output/output.php';
        $this->outfd = fopen($OUTPUT, 'w');
        if (!$this->outfd) {
            throw new Exception("Could not open file: {$OUTPUT}");
        }

        // We have file handlers here ... continue execution

        fclose($this->outfd);
        fclose($fp);
    }
}
