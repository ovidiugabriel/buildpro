<?php

class DestroyNode extends Node {
    public function __construct($to) {
        parent::__construct('destroy', null, $to, 'destroy');
    }
}
