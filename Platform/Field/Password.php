<?php
namespace Platform;

class FieldPassword extends Field {
    
    private $inputwasparsed = false;
    
    public function __construct($label, $name, $options = array()) {
        parent::__construct($label, $name, $options);
        $this->classes[] = 'w3-input';
        $this->classes[] = 'platform_password';
    }
    
    public function getValue() {
        if ($this->inputwasparsed) return $this->value;
        return null;
    }
    
    public function parse($value) {
        // Check if there was legit input
        if ($_POST[$this->name.'__ischanged'] || $value != 'XXXXXX') {
            $this->inputwasparsed = true;
        }
        return parent::parse($value);
    }
    
    public function renderInput() {
        echo '<input type="hidden" name="'.$this->name.'__ischanged" value=0>';
        if ($this->inputwasparsed) $value = $this->value;
        else $value = $this->value ? 'XXXXXX' : '';
        echo '<input class="'.$this->getClassString().'" type="password" name="'.$this->name.'" id="'.$this->getFieldIdForHTML().'" value="'.$value.'"'.$this->additional_attributes.'>';
    }
}