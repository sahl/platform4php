<?php
namespace Platform;

class FieldText extends Field {
    
    public function __construct($label, $name, $options = array()) {
        parent::__construct($label, $name, $options);
        $this->classes[] = 'w3-input';
    }
    
    public function renderInput() {
        echo '<input class="'.$this->getClassString().'" type="text" name="'.$this->name.'" id="'.$this->getFieldIdForHTML().'" value="'.htmlentities($this->value, ENT_QUOTES).'"'.$this->additional_attributes.'>';
    }
}