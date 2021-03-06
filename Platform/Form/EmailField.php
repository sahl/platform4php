<?php
namespace Platform\Form;

class EmailField extends Field {
    
    public function __construct(string $label, string $name, array $options = array()) {
        parent::__construct($label, $name, $options);
    }
    
    public function parse($value) : bool {
        if (! parent::parse($value)) return false;
        if ($value && ! preg_match('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/i', $value)) {
            $this->triggerError('Invalid email');
            return false;
        }
        return true;
    }
    
    public function renderInput() {
        echo '<input class="'.$this->getClassString().'" type="email" name="'.$this->name.'" id="'.$this->getFieldIdForHTML().'" value="'.htmlentities($this->value, ENT_QUOTES).'"'.$this->additional_attributes.'>';
    }
}