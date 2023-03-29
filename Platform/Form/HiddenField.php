<?php
namespace Platform\Form;

class HiddenField extends Field {
    
    public function __construct(string $label, string $name, array $options = array()) {
        parent::__construct('', $name, $options);
    }
    
    public function render() {
        $this->renderInput();
    }
    
    public function renderInput() {
        echo '<input data-fieldclass="'.$this->getFieldClass().'" class="'.$this->getClassString().'" type="hidden" name="'.$this->name.'" id="'.$this->getFieldIdForHTML().'" value="'.htmlentities($this->value, ENT_QUOTES).'"'.$this->additional_attributes.'>';
    }
}