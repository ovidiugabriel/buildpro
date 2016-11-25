<?php

#
# https://www.websequencediagrams.com/
# To understand terminology: http://www.uml-diagrams.org/sequence-diagrams.html#lifeline
#
# Use PlantUML (http://plantuml.com/starting) to convert WSD files to PNG
# Run it like that: java -jar plantuml.jar sequenceDiagram.txt
# 

define ('TAB', '    ');

//
// Main code
//

$opts = getopt('', array('main:'));

$INPUT = $argv[$argc-1];
$fp = fopen($INPUT, 'r');
if ($fp) {

$participants = array();
$sections = array(
    'header' => array(),
    'code'   => array(),
);

while ($line = fgets($fp)) {
    $line = trim($line);
    if (!$line) continue;
    echo "// $line \n";
    $matches = array();

    $node = null;

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
        $participant = new Node($matches[1], null, null, $matches[2]);
        $sections['header'][] = $participant;
        $participants[$matches[2]] = array();
    }

    else {
        // The line is not recognized by the language
        continue;
    }

    if ($node) {
        $participants[$node->from][] = $node;
    }
} // end-while

$backend = CppBackend::getInstance();

// Generate header code
foreach ($sections['header'] as $hdr_node) {
    echo $backend->generate($hdr_node);
}
echo "\n";

if (isset($opts['main'])) {
    
    echo $backend->generateMainMethod($opts['main']);
    echo $backend->createEntryPoint($opts['main']);
}

} // end-if
