<?php
namespace Platform\Form;

class NumberField extends Field {
    
    public function __construct(string $label, string $name, array $options = array()) {
        parent::__construct($label, $name, $options);
    }
    
    public function parse($value) : bool {
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
        echo '<input class="'.$this->getClassString().'" style="max-width: '.$this->field_width.';" type="number" name="'.$this->name.'" id="'.$this->getFieldIdForHTML().'" value="'.htmlentities($this->value, ENT_QUOTES).'"'.$this->additional_attributes.'>';
    }
}