<?php
namespace Platform\Form;

class Button extends Field {
    
    public function __construct(string $label, string $name, array $options = array()) {
        parent::__construct($label, $name, $options);
        // This never holds an external label
        $this->setLabelAlignment(self::LABEL_ALIGN_NONE);
    }
    
    public function renderInput() {
        echo '<input data-fieldclass="'.$this->getFieldClass().'" class="'.$this->getFieldClasses().'" type="button" value="'.$this->label.'" id="'.$this->getFieldIdForHTML().'"'.$this->additional_attributes.'>';
    }
}