<?php

//
// Compare with:
//
//      racket xexpr.rkt in.s | xmllint --format -

const DEBUG = 0;

interface IParser {
    public function endElement($tag);
    public function startElement($tag);
    public function characterData($string);
    public function __toString();
}

class Parser extends stdClass implements IParser {
    /** @var array */
    private $stack = array();

    /** @var string */
    private $output = '';

    /** 
     * @param string $tag 
     * @return void
     */
    public function endElement($tag) {
        if (DEBUG) echo __FUNCTION__ . "('$tag')\n";
        if (count((array) $this->stack) > 0) {
            $this->output .= "</$tag>";
            array_pop($this->stack);
        }
    }

    /** 
     * @param string $tag
     * @return void
     */
    public function startElement($tag) {
        if (DEBUG) echo __FUNCTION__ . "('$tag')\n";
        $this->stack[] = $tag;
        $this->output .= "<$tag>";
    }

    /** 
     * @param string $string
     * @return void
     */
    public function characterData($string) {
        if (DEBUG) echo __FUNCTION__ . "('$string')\n";
        $this->output .= $string;
    }

    /** 
     * @return string
     */
    public function __toString() {
        return $this->output;
    }
}

class State {
    const STOP      = 0;    // The initial state, machine stopped
    const CONS      = 1;    // Reading the function name
    const START     = 2;    // Start reading arguments
    const IN_STRING = 3;    // Reading a string 
}

/** 
 * @param string $input
 * @param IParser $parser
 * @return IParser
 */
function xexpr_to_xml($input, IParser $parser) {
    $state  = State::STOP;
    $cons   = '';
    $stack = array();
    $string = '';

    $str = str_split($input);
    foreach ($str as $ch) {
        switch ($ch) {
            case '(':
                $state = State::CONS;
                break;

            case ')':
                $state = State::STOP;
                if (count($stack) > 0) {
                    $parser->endElement(array_pop($stack));
                }
                break;

            case ' ':
            case "\n":
                if (State::CONS == $state) {
                    $state = State::START;
                    $parser->startElement($cons);
                    if (DEBUG) echo ":array_push(stack, '$cons')\n";
                    $stack[] = $cons;
                    $cons = '';
                }
                break;

            case '"':
                if (State::START == $state) {
                    $state = State::IN_STRING;
                } elseif (State::IN_STRING == $state) {
                    $parser->characterData($string);
                    $string = '';
                    $state = State::START;
                }
                break;

            case "\n":
                break;

            default:
                if (State::CONS == $state) {
                    $cons .= $ch;
                } elseif (State::IN_STRING == $state) {
                    $string .= $ch;
                }
                break;
        }
    }
    return $parser;
}

if (isset($argv[1])) {
    echo xexpr_to_xml(file_get_contents($argv[1]), new Parser());
}
