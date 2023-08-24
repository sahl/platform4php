<?php
namespace Platform\Form;

class SubmitButton extends Field {
    
    public function __construct() {
        parent::__construct();
        $this->addFieldClass('platform_submit');
        // This never holds a label
        $this->setLabelAlignment(self::LABEL_ALIGN_NONE);
    }
    
    public function renderInput() {
        echo '<input data-fieldclass="'.$this->getFieldClass().'" class="'.$this->getFieldClasses().'" type="submit" value="'.$this->label.'" id="'.$this->getFieldIdForHTML().'"'.$this->additional_attributes.'>';
    }
}