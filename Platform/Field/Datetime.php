<?php
namespace Platform;

// Todo: Better handling of I/O 

class FieldDatetime extends Field {
    
    public function __construct($label, $name, $options = array()) {
        Errorhandler::checkParams($label, 'string', $name, 'string', $options, 'array');
        parent::__construct($label, $name, $options);
    }
    
    public function setValue($value) {
        Errorhandler::checkParams($value, '\\Platform\\Time');
        $this->value = $value;
    }
    
    public function parse($value) {
        $this->value = new \Platform\Time($value);
    }

    public function renderInput() {
        $value = $this->value->getReadable();
        echo '<input class="'.$this->getClassString().'" type="datetime-local" name="'.$this->name.'" id="'.$this->getFieldIdForHTML().'" value="'.$value.'"'.$this->additional_attributes.'>';
    }
}