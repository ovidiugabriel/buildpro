<?php

#
# https://www.websequencediagrams.com/
# To understand terminology: http://www.uml-diagrams.org/sequence-diagrams.html#lifeline
#

function createInstance($className, $objectName = null) {
    if (!$objectName) {
        $objectName = strtolower($className);
    }
    return "{$className}* {$objectName} = new {$className}()";
}

class Node {
    const TYPE_FINISH = 'finish';
    const TYPE_RETURN = 'return';
    const TYPE_START  = 'start';
    const TYPE_CREATE = 'create';
    const TYPE_CALL   = 'call';

    public function __construct($type, $from = null, $to = null, $message = null) {
        if ($type) {
            $type_names = array(
                    '-->-'  =>  self::TYPE_FINISH,
                    '-->'   =>  self::TYPE_RETURN,
                    '->+'   =>  self::TYPE_START,
                    '->*'   =>  self::TYPE_CREATE,
                    '->'    =>  self::TYPE_CALL,
                );
            $this->type = isset($type_names[$type]) ? $type_names[$type] : $type;
        }
        if ($from) {$this->from = $from;}
        if ($to) {$this->to = $to;}
        if ($message) {$this->message = $message;}
    }

    public function generate() {
        switch ($this->type) {
            case 'call': {
                $msg = $this->message;
                $m = str_split($msg);
                if (')' != $m[count($m)-1] && false === strpos($msg, ')')) {
                    $msg .= '()';
                }
                return strtolower($this->to) . '->' . $msg . ";\n";
            }
            case 'create': return createInstance($this->to). ";\n";
            case 'participant': return '#include "'.$this->message.'.h"' . "\n";
        }
    }
}

class DestroyNode extends Node {
    public function __construct($to) {
        parent::__construct('destroy', null, $to, 'destroy');
    }
}

class AltNode extends Node {
    public function __construct($type, $message) {
        parent::__construct($type, null, null, $message);
    }
}

class EndNode extends Node {
    public function __construct() {
        parent::__construct('end');
    }
}

//
// Main code
//

$INPUT = $argv[1];
$fp = fopen($INPUT, 'r');
if ($fp) {

$participants = array();	// nume_actor => lista apeluri?...

echo "int main(void) { \n";
while ($line = fgets($fp)) {
    $line = trim($line);
    if (!$line) continue;
    echo "// $line \n";
    $matches = array();

    // Finish occurrence {finish}
    if (preg_match('/^\s*(.*)\s*(-->-)\s*(.*)\s*:\s*(.*)\s*$/', $line, $matches)) {
        $node = new Node($matches[2], $matches[1], $matches[3], $matches[4]);

    }

    // Return message {return}
    elseif (preg_match('/^\s*(.*)\s*(-->)\s*(.*)\s*:\s*(.*)\s*$/', $line, $matches)) {
        $node = new Node($matches[2], $matches[1], $matches[3], $matches[4]);

    }

    // Start occurrence {start}
    elseif (preg_match('/^\s*(.*)\s*(->\+)\s*(.*)\s*:\s*(.*)\s*$/', $line, $matches)) {
        $node = new Node($matches[2], $matches[1], $matches[3], $matches[4]);

    }

    // Object creation message {create}
    elseif (preg_match('/^\s*(.*)\s*(->\*)\s*(.*)\s*:\s*(.*)\s*$/', $line, $matches)) {
        $node = new Node($matches[2], $matches[1], $matches[3], $matches[4]);

    }

    // (Synchronous) message {call}
    elseif (preg_match('/^\s*(.*)\s*(->)\s*(.*)\s*:\s*(.*)\s*$/', $line, $matches)) {
        $node = new Node($matches[2], $matches[1], $matches[3], $matches[4]);

    }

    elseif (preg_match('/^(destroy)\s+(.*)$/', $line, $matches)) {
        $node = new DestroyNode($matches[2]);

    }

    elseif (preg_match('/^(alt)\s+(.*)$/', $line, $matches)) {
        $node = new AltNode($matches[1], $matches[2]);

    }

    elseif (preg_match('/^(else)\s+(.*)$/', $line, $matches)) {
        $node = new AltNode($matches[1], $matches[2]);

    }

    elseif (preg_match('/^(end)$/', $line, $matches)) {
        $node = new EndNode();

    }

    elseif (preg_match('/^(opt)\s+(.*)$/', $line, $matches)) {
        $node = new AltNode($matches[1], $matches[2]);

    }

    elseif (preg_match('/^(loop)\s+(.*)$/', $line, $matches)) {
        $node = new AltNode($matches[1], $matches[2]);

    }

    elseif (preg_match('/^(participant)\s+(.*)$/', $line, $matches)) {
        $node = new Node($matches[1], null, null, $matches[2]);
    }

    else { continue; }

    echo '// ' . json_encode($node), "\n";
    echo $node->generate() . "\n";
}

echo "return 0; } \n";
}
