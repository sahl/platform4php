<?php
namespace Platform\Form;

class SubmitButton extends Field {
    
    public function __construct(string $label, string $name, array $options = array()) {
        parent::__construct($label, $name, $options);
        $this->classes[] = 'platform_submit';
        // This never holds a label
        $this->setLabelAlignment(self::LABEL_ALIGN_NONE);
    }
    
    public function renderInput() {
        echo '<input data-fieldclass="'.$this->getFieldClass().'" class="'.$this->getClassString().'" type="submit" value="'.$this->label.'" id="'.$this->getFieldIdForHTML().'"'.$this->additional_attributes.'>';
    }
}