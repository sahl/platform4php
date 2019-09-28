<?php
namespace Platform;

class FieldDatetime extends Field {
    
    public function __construct($label, $name, $options = array()) {
        parent::__construct($label, $name, $options);
        $this->classes[] = 'w3-input';
    }
    
    public function renderInput() {
        echo '<input class="'.$this->getClassString().'" type="datetime-local" name="'.$this->name.'" id="'.$this->getFieldIdForHTML().'" value="'.$this->value.'"'.$this->additional_attributes.'>';
    }
}