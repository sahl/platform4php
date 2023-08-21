<?php
namespace Platform\Form;

use Platform\Utilities\Time;
// Todo: Better handling of I/O 

class DatetimeField extends Field {
    
    public function __construct(string $label, string $name, array $options = array()) {
        parent::__construct($label, $name, $options);
        $this->value = new Time();
    }
    
    public function parse($value): bool {
        $result = parent::parse($value);
        if ($result) {
            $this->value = Time::parseFromDisplayTime($value);
        }
        return $result;
    }
    
    public function setValue($value) {
        $this->value = new Time($value);
    }
    
    public function renderInput() {
        $placeholder = trim($this->placeholder) ? ' placeholder="'.$this->placeholder.'"' : '';
        $date_value = str_replace(' ', 'T', $this->value->getReadable('Y-m-d H:i'));
        echo '<input class="'.$this->getClassString().'" style="max-width: '.$this->field_width.';"'.$placeholder.' type="datetime-local" name="'.$this->name.'" id="'.$this->getFieldIdForHTML().'" value="'.$date_value.'"'.$this->additional_attributes.'>';
    }
}