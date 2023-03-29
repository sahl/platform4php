<?php
namespace Platform\Form;

use Platform\Utilities\Time;
// Todo: Better handling of I/O 

class DatetimeField extends Field {
    
    public function __construct(string $label, string $name, array $options = array()) {
        parent::__construct($label, $name, $options);
        $this->value = new Time();
    }
    
    public function setValue($value) {
        $this->value = $value;
    }
    
    public function parse($value) : bool {
        if (! parent::parse($value)) return false;
        $this->value = new Time($value);
        return true;
    }

    public function renderInput() {
        $placeholder = trim($this->placeholder) ? ' placeholder="'.$this->placeholder.'"' : '';
        echo '<input data-fieldclass="'.$this->getFieldClass().'" class="'.$this->getClassString().'" style="max-width: '.$this->field_width.';"'.$placeholder.' type="datetime-local" name="'.$this->name.'" id="'.$this->getFieldIdForHTML().'" value="'.$this->value->get().'"'.$this->additional_attributes.'>';
    }
}