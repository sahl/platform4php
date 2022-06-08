<?php
namespace Platform\Form;

class TextField extends Field {
    
    public function __construct(string $label, string $name, array $options = array()) {
        parent::__construct($label, $name, $options);
    }
    
    public function renderInput() {
        echo '<input class="'.$this->getClassString().'" style="max-width: '.$this->field_width.';" type="text" name="'.$this->name.'" id="'.$this->getFieldIdForHTML().'" value="'.htmlentities($this->value, ENT_QUOTES).'"'.$this->additional_attributes.'>';
    }
}