<?php

class Parser extends stdClass {
    private $stack = array();
    private $output = '';

    public function endElement($tag) {
        $this->output .= tab(count($this->stack)) . "</$tag>\n";
        array_pop($this->stack);
    }

    public function startElement($tag) {
        $this->stack[] = $tag;
        $this->output .= tab(count($this->stack)) . "<$tag>\n";
    }

    public function characterData($string) {
        $this->output .= tab(count($this->stack)+1) . $string . "\n";
    }

    public function __toString() {
        return $this->output;
    }
}


if (!function_exists('tab')) {
function tab($size) {
    $result = '';
    for ($i = 0; $i < $size; $i++) {
        $result .= "    ";  // a tab is 4 spaces
    }
    return $result;
}} /* function=tab */

class State {
    const STOP      = 0;    // The initial state, machine stopped
    const CONS      = 1;    // Reading the function name
    const START     = 2;    // Start reading arguments
    const IN_STRING = 3;    // Reading a string 
}

function xexpr_to_xml($input, stdClass $parser) {
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
                call_user_func(array($parser, 'endElement'), array_pop($stack));
                break;

            case ' ':
                if (State::CONS == $state) {
                    $state = State::START;
                    call_user_func(array($parser, 'startElement'), $cons);
                    $stack[] = $cons;
                    $cons = '';
                }
                break;

            case '"':
                if (State::START == $state) {
                    $state = State::IN_STRING;
                } elseif (State::IN_STRING == $state) {
                    call_user_func(array($parser, 'characterData'), $string);
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

//
// Usage Example:
//
//      $input = '
//          (html
//              (head (title "Hello") )
//              (body "Hi!")
//          )';
//      echo xexpr_to_xml($input, new Parser());
//
