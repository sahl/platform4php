<?php
namespace Platform\Form;
/**
 * Field for inputting dates
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=field_class
 */

use Platform\Utilities\Time;

// Todo: Better handling of I/O 

class DateField extends Field {
    
    public $allow_past = true; // if it is allowed to select a date in the past
    
    public function __construct() {
        parent::__construct();
        $this->value = new Time();
    }
    
    public static function Field(string $label, string $name, array $options = array()) {
        $field = parent::Field($label, $name, $options);
        if (isset($options['allow_past'])) {
            $field->allow_past = (bool)$options['allow_past'];
            unset($options['allow_past']);
        }
        return $field;
    }
    
    public function setValue($value) {
        if (! $value instanceof Time) $value = new Time($value);
        $this->value = $value;
    }
    
    public function parse($value) : bool {
        if (! parent::parse($value)) return false;
        if ($value) {
            $value = new Time($value);
            $this->value = $value->startOfDay();
        } else {
            $this->value = new Time();
        }
        return true;
    }
    
    public function renderInput() {
        // Determine all attributes
        $attributes = ['data-fieldclass' => $this->getFieldClass(),
                       'class' => $this->getFieldClasses(),
                       'style'=> $this->getFieldStyleString(),
                       'type' => 'date',
                       'name' => $this->name,
                       'id' => $this->getFieldIdForHTML(),
                       'value' => $this->value->get('Y-m-d')
            ];
        if (trim($this->placeholder))
            $attributes['placeholder'] = $this->placeholder;
        if (!$this->allow_past)
            $attributes['min'] = \Platform\Utilities\Time::today()->get('Y-m-d');
        
        foreach ($attributes as $name => $value)
            $attributes[$name] = $name.'="'.$value.'"';
        echo '<input ' . implode(' ', $attributes) . $this->additional_attributes.'>';
    }
}
