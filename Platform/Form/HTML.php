<?php
namespace Platform\Form;

class HTML extends Field {
    
    public static $fieldcounter = 0;
    
    public function __construct(string $html) {
        parent::__construct('', 'htmlfield'.(self::$fieldcounter++), array());
        $this->value = $html;
    }
    
    public function parse($value) : bool {
        return true;
    }
    
    public function render() {
        echo $this->value;
    }
}