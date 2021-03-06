<?php
namespace Platform;

// Todo: Better handling of I/O 

class FieldDate extends Field {
    
    public function __construct($label, $name, $options = array()) {
        Errorhandler::checkParams($label, 'string', $name, 'string', $options, 'array');
        parent::__construct($label, $name, $options);
        $this->value = new \Platform\Time();
    }
    
    public function setValue($value) {
        Errorhandler::checkParams($value, '\\Platform\\Time');
        $this->value = $value;
    }
    
    public function parse($value) {
        if (! parent::parse($value)) return false;
        $this->value = new \Platform\Time($value);
        return true;
    }
    
    public function renderInput() {
        $value = $this->value->getReadable('Y-m-d');
        echo '<input class="'.$this->getClassString().'" type="date" name="'.$this->name.'" id="'.$this->getFieldIdForHTML().'" value="'.$value.'"'.$this->additional_attributes.'>';
    }
}