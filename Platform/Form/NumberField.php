<?php
namespace Platform\Form;
/**
 * Field for inputting numbers
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=field_class
 */

class NumberField extends Field {
    public $allow_decimal = true; // if it is allowed to specify decimal numbers
    public $minimum_value = false; // min/max allowed value or false for no limit
    public $maximum_value = false;

    public static function Field(string $label, string $name, array $options = array()) {
        $options['reserved_options'] = ['allow_decimal', 'minimum_value', 'maximum_value'];
        $field = parent::Field($label, $name, $options);
        if (isset($options['allow_decimal'])) {
            $field->allow_decimal = (bool)$options['allow_decimal'];
            unset($options['allow_decimal']);
        }
        if (isset($options['minimum_value'])) {
            $field->minimum_value = (float)$options['minimum_value'];
            unset($options['minimum_value']);
        }
        if (isset($options['maximum_value'])) {
            $field->maximum_value = (float)$options['maximum_value'];
            unset($options['maximum_value']);
        }
        return $field;
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
    
    /**
     * Set the value of this field (with no validation)
     * @param object $value
     */
    public function setValue($value) {
        if (!$this->allow_decimal)
            $value = round($value);
        if ($this->minimum_value !== false && $value < $this->minimum_value)
            $value = $this->minimum_value;
        if ($this->maximum_value !== false && $value > $this->maximum_value)
            $value = $this->maximum_value;
        $this->value = $value;
    }
    
    public function renderInput() {
        // sanity checks
        if (!$this->allow_decimal) {
            $this->value = round($this->value);
            if ($this->minimum_value !== false)
                $this->minimum_value = round($this->minimum_value);
            if ($this->maximum_value !== false)
                $this->maximum_value = round($this->maximum_value);
        }
        if ($this->minimum_value !== false && $this->maximum_value !== false)
            if ($this->minimum_value > $this->maximum_value)
                $this->minimum_value = $this->maximum_value;
            
        // Determine all attributes
        $attributes = ['data-fieldclass' => $this->getFieldClass(),
                       'class' => $this->getFieldClasses(),
                       'style'=> $this->getFieldStyleString(),
                       'type' => 'number',
                       'name' => $this->name,
                       'id' => $this->getFieldIdForHTML(),
                       'value' => htmlentities($this->value, ENT_QUOTES)
            ];
        if (trim($this->placeholder))
            $attributes['placeholder'] = $this->placeholder;
        if ($this->minimum_value !== false)
            $attributes['min'] = htmlentities($this->minimum_value, ENT_QUOTES);
        if ($this->maximum_value !== false)
            $attributes['max'] = htmlentities($this->maximum_value, ENT_QUOTES);
        if ($this->allow_decimal)
            $attributes['step'] = 'any';
        
        foreach ($attributes as $name => $value)
            $attributes[$name] = $name.'="'.$value.'"';
        echo '<input ' . implode(' ', $attributes) . $this->additional_attributes.'>';
    }
}