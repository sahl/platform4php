<?php
namespace Platform;

class FieldNumber extends Field {
    
    public function __construct($label, $name, $options = array()) {
        parent::__construct($label, $name, $options);
        $this->classes[] = 'w3-input';
    }
    
    public function parse($value) {
        if (! parent::parse($value)) return false;
        if ($this->value === '') {
            $this->value = null;
            return true;
        }
        if (!is_numeric($value)) {
            $this->triggerError('Must be a number');
            return false;
        }
        return true;
    }    
    
    public function renderInput() {
        echo '<input class="'.$this->getClassString().'" type="number" name="'.$this->name.'" id="'.$this->getFieldIdForHTML().'" value="'.htmlentities($this->value, ENT_QUOTES).'"'.$this->additional_attributes.'>';
    }
}