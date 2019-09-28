<?php
namespace Platform;

class FieldCheckbox extends Field {
    
    public function __construct($label, $name, $options = array()) {
        parent::__construct($label, $name, $options);
        $this->classes[] = 'w3-input';
    }
    
    public function parse($value) {
        if (! parent::parse($value)) return false;
        $this->value = (int)$this->value;
        return true;
    }    
    
    public function renderInput() {
        $checked = $this->value ? ' checked' : '';
        echo '<input class="'.$this->getClassString().'" type="checkbox" name="'.$this->name.'" id="'.$this->getFieldIdForHTML().'" value="1"'.$this->additional_attributes.$checked.'>';
    }
}