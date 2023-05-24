<?php
namespace Platform\Form;

class CheckboxField extends Field {
    
    public static function Field(string $label, string $name, array $options = array()) {
        $field = parent::Field($label, $name, $options);
        $field->addClass('platform_checkbox');
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