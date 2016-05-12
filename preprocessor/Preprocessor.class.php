<?php

class Preprocessor {
    /** @var string */
    private $INPUT;
    
    /** @var integer */
    private $LINE_NUMBER;
    
    /** @var resource */
    private $outfd;
    
    /** @var array */
    private $stack = array();

    /** 
     * @param string $INPUT
     * @param resource $outfd
     */
    public function __construct($INPUT, $outfd = null) {
        $this->INPUT = trim($INPUT, ".\\/");
        $this->outfd = $outfd;
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
     * @return void
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
    
    /** 
     * @param integer $LINE_NUMBER
     */
    public function set_line_number($LINE_NUMBER) {
        $this->LINE_NUMBER = (int) $LINE_NUMBER;
    }
}
