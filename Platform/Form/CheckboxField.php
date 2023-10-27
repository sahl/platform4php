<?php
namespace Platform\Form;
/**
 * Field for checkboxes
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=field_class
 */

class CheckboxField extends Field {
    
    protected static $component_class = 'platform_component_checkbox_field';
    
    public function __construct() {
        parent::__construct();
        static::JSFile(\Platform\Utilities\Utilities::directoryToURL(__DIR__).'/js/Field.js'); 
        static::JSFile(\Platform\Utilities\Utilities::directoryToURL(__DIR__).'/js/CheckboxField.js'); 
    }
    
    public static function Field(string $label, string $name, array $options = array()) {
        $field = parent::Field($label, $name, $options);
        return $field;
    }
    
    public function parse($value) : bool {
        if (! parent::parse($value)) return false;
        $this->value = (int)$this->value;
        return true;
    }    
    
    public function renderInput() {
        $checked = $this->value ? ' checked' : '';
        echo '<input data-fieldclass="'.$this->getFieldClass().'" class="'.$this->getFieldClasses().'" type="checkbox" name="'.$this->name.'" id="'.$this->getFieldIdForHTML().'" value="1"'.$this->additional_attributes.$checked.'> ';
    }
}