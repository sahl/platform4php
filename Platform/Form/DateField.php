<?php
namespace Platform\Form;

use Platform\Utilities\Time;

// Todo: Better handling of I/O 

class DateField extends Field {
    
    public function __construct(string $label, string $name, array $options = array()) {
        parent::__construct($label, $name, $options);
        $this->value = new Time();
    }
    
    public function setValue(string $value) {
        $this->value = $value;
    }
    
    public function parse($value) : bool {
        if (! parent::parse($value)) return false;
        $this->value = new Time($value);
        return true;
    }
    
    public function renderInput() {
        echo '<input class="'.$this->getClassString().'" type="date" name="'.$this->name.'" id="'.$this->getFieldIdForHTML().'" value="'.$this->value.'"'.$this->additional_attributes.'>';
    }
}