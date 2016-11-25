<?php

class Node {
    const TYPE_FINISH = 'finish';
    const TYPE_RETURN = 'return';
    const TYPE_START  = 'start';
    const TYPE_CREATE = 'create';
    const TYPE_CALL   = 'call';

    /** 
     * @param string $type
     * @param string $from
     * @param string $to
     * @param string $message
     */
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
}
