<?php
namespace Platform;

class FieldHidden extends Field {
    
    public function __construct($label, $name, $options = array()) {
        parent::__construct('', $name, $options);
    }
    
    public function render() {
        $this->renderInput();
    }
    
    public function renderInput() {
        echo '<input class="'.$this->getClassString().'" type="hidden" name="'.$this->name.'" id="'.$this->getFieldIdForHTML().'" value="'.$this->value.'"'.$this->additional_attributes.'>';
    }
}