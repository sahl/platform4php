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
    
    public function renderInput() {
        $placeholder = trim($this->placeholder) ? ' placeholder="'.$this->placeholder.'"' : '';
        echo '<input data-fieldclass="'.$this->getFieldClass().'" class="'.$this->getFieldClasses().'" style="max-width: '.$this->field_width.';"'.$placeholder.' type="datetime-local" name="'.$this->name.'" id="'.$this->getFieldIdForHTML().'" value="'.$this->value.'"'.$this->additional_attributes.'>';
    }
}
