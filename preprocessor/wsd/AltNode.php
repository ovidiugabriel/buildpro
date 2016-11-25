<?php 

class AltNode extends Node {
    public function __construct($type, $message) {
        parent::__construct($type, null, null, $message);
    }
}
