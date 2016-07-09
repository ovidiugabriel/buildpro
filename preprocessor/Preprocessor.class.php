<?hh

class Preprocessor {
    /** @var string */
    private string $INPUT;

    /** @var integer */
    private int $LINE_NUMBER = 0;

    /** @var resource */
    public resource $outfd;

    /** @var array */
    // private array $stack = array();

    /** 
     * @param string $INPUT
     * @param resource $outfd
     */
    public function __construct(string $INPUT, ?resource $outfd = null) {
        $this->INPUT    = trim($INPUT, ".\\/");

        if (!file_exists($this->INPUT)) {
            throw new Exception("$INPUT - No such file.", 1);
        }

        $OUTPUT = dirname(__FILE__) . '/output/'. $this->INPUT .'.php';
        $dir = dirname($OUTPUT);

        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

        if (!is_writable($dir)) {
            throw new Exception("{$dir} is not writable", 1);
        }

        if (null === $outfd) {
            $outfd = fopen($OUTPUT, 'w');

            //
            // In Hack language outfd is typed as resource
            // so there is not need to check if it is a resource or 
            // 'Identical' (triple equal) comparison with boolean false
            //
            if (!$outfd) {
                throw new Exception("Failed to open '$OUTPUT'", 1);
            }
        }
        $this->outfd = $outfd;
    }

    /**
     * Emits a preprocessing error in the output file.
     *
     * @param integer $n_tabs
     * @param string $text
     * @return void
     */
    public function error(int $n_tabs, string $text):void {
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
    public function execute():void {

        $fp = fopen($this->INPUT, 'r');
        if (!$fp) {
            throw new Exception("Could not open file: {$this->INPUT}");
        }

        // We have file handlers here ... continue execution

        while ($line = fgets($fp)) {
            $this->LINE_NUMBER++;

            direct_write($this->outfd, "");
            $this->out(0, '$LINE_NUMBER = ' . $this->LINE_NUMBER);
            direct_write($this->outfd, "#line {$this->LINE_NUMBER} \"{$this->INPUT}\"");

            $line       = rtrim($line);

            // Process the line
        }

        fclose($this->outfd);
        fclose($fp);
    }

    public function __destruct() {
        /*
        if ($this->outfd) {
            fclose($this->outfd);
        }
        */
    }
    
    /** 
     * @param integer $LINE_NUMBER
     */
    public function set_line_number(int $LINE_NUMBER):void {
        $this->LINE_NUMBER = (int) $LINE_NUMBER;
    }

    /**
     * Writes a new PHP line to the given file.
     *
     * @param integer $n_tabs
     * @param string $text
     * @param string $sep
     * @return void
     */
    public function out(int $n_tabs, string $text, ?string $sep = null):void {
        static $file_started = false;

        // {{{ do not remove 
        if (null === $sep) {
            $sep = SEP_SEMICOLON;
        }
        // }}}

        if (!$file_started) {
            //
            // Note that Hack-language allows only one open tag in the entire file
            // and no close tags
            //
            fwrite($this->outfd, tab($n_tabs) . '<?'.PHP_TAG_NAME." \n\n");
            $file_started = true;
        }

        // Note that Hack-language always requires semicolon at the end of the line
        fwrite($this->outfd, tab($n_tabs) . $text . $sep . "\n");
    }
}
