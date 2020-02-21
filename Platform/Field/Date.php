<?php
namespace Platform;

// Todo: Better handling of I/O 

class FieldDate extends Field {
    
    public function __construct($label, $name, $options = array()) {
        Errorhandler::checkParams($label, 'string', $name, 'string', $options, 'array');
        parent::__construct($label, $name, $options);
        $this->classes[] = 'w3-input';
    }
    
    public function renderInput() {
        echo '<input class="'.$this->getClassString().'" type="date" name="'.$this->name.'" id="'.$this->getFieldIdForHTML().'" value="'.$this->value.'"'.$this->additional_attributes.'>';
    }
}