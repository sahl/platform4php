<?php
namespace Platform\Form;
/**
 * Field for inputting dates and times
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=field_class
 */

use Platform\Utilities\Time;
// Todo: Better handling of I/O 

class DatetimeField extends Field {
    
    protected static $component_class = 'platform_component_date_time_field';
    
    public function __construct() {
        parent::__construct();
        $this->JSFile(\Platform\Utilities\Utilities::directoryToURL(__DIR__).'js/DateTimeField.js');
        $this->value = new Time();
    }
    
    public static function Field(string $label, string $name, array $options = array()) {
        $field = parent::Field($label, $name, $options);
        return $field;
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
        echo '<input data-fieldclass="'.$this->getFieldClass().'" class="'.$this->getFieldClasses().'" style="'.$this->getFieldStyleString().'"'.$placeholder.' type="datetime-local" name="'.$this->name.'" id="'.$this->getFieldIdForHTML().'" value="'.$date_value.'"'.$this->additional_attributes.'>';
    }
}
