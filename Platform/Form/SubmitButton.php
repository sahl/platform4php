<?php
namespace Platform\Form;
/**
 * A form submit button
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=field_class
 */

class SubmitButton extends Field {
    
    public function __construct() {
        parent::__construct();
        $this->addFieldClass('platform_submit');
        // This never holds a label
        $this->setLabelAlignment(self::LABEL_ALIGN_NONE);
    }
    
    public function renderInput() {
        echo '<input data-fieldclass="'.$this->getFieldClass().'" style="'.$this->getFieldStyleString().'" class="'.$this->getFieldClasses().'" type="submit" value="'.$this->label.'" id="'.$this->getFieldIdForHTML().'"'.$this->additional_attributes.'>';
    }
}