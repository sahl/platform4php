<?php
namespace Platform\Form;

use Platform\Utilities\Time;
// Todo: Better handling of I/O 

class DatetimeField extends Field {
    
    public function __construct(string $label, string $name, array $options = array()) {
        parent::__construct($label, $name, $options);
    }
    
    public function setValue(Time $value) {
        $this->value = $value;
    }
    
    public function parse($value) : bool {
        if (! parent::parse($value)) return false;
        $this->value = new Time($value);
        return true;
    }

    public function renderInput() {
        $value = $this->value->getReadable();
        echo '<input class="'.$this->getClassString().'" type="datetime-local" name="'.$this->name.'" id="'.$this->getFieldIdForHTML().'" value="'.$value.'"'.$this->additional_attributes.'>';
    }
}