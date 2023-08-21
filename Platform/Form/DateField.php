<?php
namespace Platform\Form;

use Platform\Utilities\Time;

// Todo: Better handling of I/O 

class DateField extends Field {
    
    public $field_width = self::FIELD_SIZE_SMALL;
    
    private $allow_past = true; // if it is allowed to select a date in the past
    
    public function __construct(string $label, string $name, array $options = array()) {
        $this->value = new Time();
        if (isset($options['allow_past'])) {
            $this->allow_past = (bool)$options['allow_past'];
            unset($options['allow_past']);
        }
        parent::__construct($label, $name, $options);
    }
    
    public function setValue($value) {
        if (! $value instanceof Time) $value = new Time($value);
        $this->value = $value;
    }
    
    public function parse($value) : bool {
        if (! parent::parse($value)) return false;
        $this->value = Time::parseFromDisplayTime($value);
        return true;
    }
    
    public function renderInput() {
        // Determine all attributes
        $attributes = ['data-fieldclass' => $this->getFieldClass(),
                       'class' => $this->getClassString(),
                       'style'=> 'max-width: '.$this->field_width.';"',
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
