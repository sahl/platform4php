<?php
namespace Platform\Form;

use Platform\Utilities\Time;

// Todo: Better handling of I/O 

class DateField extends Field {
    
    public $field_width = self::FIELD_SIZE_SMALL;
    
    public function __construct(string $label, string $name, array $options = array()) {
        $this->value = new Time();
        parent::__construct($label, $name, $options);
    }
    
    public function setValue($value) {
        if (! $value instanceof Time) $value = new Time($value);
        $this->value = $value;
    }
    
    public function parse($value) : bool {
        if (! parent::parse($value)) return false;
        $this->value = new Time($value);
        return true;
    }
    
    public function renderInput() {
        $placeholder = trim($this->placeholder) ? ' placeholder="'.$this->placeholder.'"' : '';
        echo '<input data-fieldclass="'.$this->getFieldClass().'" class="'.$this->getClassString().'" style="max-width: '.$this->field_width.';"'.$placeholder.' type="date" name="'.$this->name.'" id="'.$this->getFieldIdForHTML().'" value="'.$this->value->get('Y-m-d').'"'.$this->additional_attributes.'>';
    }
}