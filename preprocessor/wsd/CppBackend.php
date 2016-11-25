<?php

class CppBackend {
    private function __construct() {}

    public function getInstance() {
        static $instance = null;
        if (null == $instance) {
            $instance = new CppBackend();
        }
        return $instance;
    }

    /** 
     * Part of the C++ code generator
     *
     * @param string $className
     * @param string $objectName
     * @return string
     */
    public function generateCreateInstance($className, $objectName = null) {
        if (!$objectName) {
            $objectName = strtolower($className);
        }
        return "{$className}* {$objectName} = new {$className}()";
    }
    
    /** 
     * Part of the C++ code generator
     *
     * @param string $method
     * @return string
     */
    public function generateMainMethod($method) {
        global $participants;

        list ($main_participant, $main_func) = explode('::', $method);

        $out = '';
        $out .= "int {$method}() {\n";
        foreach ($participants[$main_participant] as $node) {
            $out .= TAB . $this->generate($node);
        }
        $out .= "}\n\n";
        return $out;
    }
    
    /**
     * This method is useful only for C/C++ where the entry point cannot be defined as a method
     * in a class
     *
     * @param string $method
     * @return string
     */
    public function createEntryPoint($method) {
        $out = '';
        $out .= "int main(int argc, char* argv[]) {\n";
        $out .= TAB . "return $method();\n";
        $out .= "}\n\n";
        return $out;
    }

    /**
     * @param Node $node
     */
    public function generate(Node $node) {
        switch ($node->type) {
            case 'call': {
                $msg = $noode->message;
                $m = str_split($msg);
                if (')' != $m[count($m)-1] && false === strpos($msg, ')')) {
                    $msg .= '()';
                }
                return strtolower($node->to) . '->' . $msg . ";\n";
            }
            case 'create': return $this->generateCreateInstance($node->to). ";\n";
            case 'participant': return '#include "'.$node->message.'.h"' . "\n";
        }
    }
}

