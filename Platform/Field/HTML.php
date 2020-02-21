<?php
namespace Platform;

class FieldHTML extends Field {
    
    public static $fieldcounter = 0;
    
    public function __construct($html) {
        Errorhandler::checkParams($html, 'string');
        parent::__construct('', 'htmlfield'.(self::$fieldcounter++), array());
        $this->value = $html;
    }
    
    public function parse($value) {
        return true;
    }
    
    public function render() {
        echo $this->value;
    }
}