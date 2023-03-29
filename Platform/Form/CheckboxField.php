<?php
namespace Platform\Form;

class CheckboxField extends Field {
    
    public function __construct(string $label, string $name, array $options = array()) {
        parent::__construct($label, $name, $options);
        $this->classes[] = 'platform_checkbox';
    }
    
    public function parse($value) : bool {
        if (! parent::parse($value)) return false;
        $this->value = (int)$this->value;
        return true;
    }    
    
    public function renderInput() {
        $checked = $this->value ? ' checked' : '';
        echo '<input data-fieldclass="'.$this->getFieldClass().'" class="'.$this->getClassString().'" type="checkbox" name="'.$this->name.'" id="'.$this->getFieldIdForHTML().'" value="1"'.$this->additional_attributes.$checked.'> ';
    }
}