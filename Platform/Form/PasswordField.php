<?php
namespace Platform\Form;

class PasswordField extends Field {
    
    private $inputwasparsed = false;
    
    public function __construct(string $label, string $name, array $options = array()) {
        parent::__construct($label, $name, $options);
    }
    
    public function getValue() {
        if ($this->inputwasparsed) return $this->value;
        return null;
    }
    
    public function parse($value) : bool {
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
        echo '<input class="'.$this->getClassString().'" type="password" name="'.$this->name.'" id="'.$this->getFieldIdForHTML().'" value="'.htmlentities($this->value, ENT_QUOTES).'"'.$this->additional_attributes.'>';
    }
}