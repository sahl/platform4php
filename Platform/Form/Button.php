<?php
namespace Platform\Form;
/**
 * Field for inputting addresses
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=field_class
 */

class Button extends Field {
    
    public function __construct() {
        parent::__construct();
        // This never holds an external label
        $this->setLabelAlignment(self::LABEL_ALIGN_NONE);
    }
    
    public function renderInput() {
        echo '<input data-fieldclass="'.$this->getFieldClass().'" class="'.$this->getFieldClasses().'" type="button" value="'.$this->label.'" id="'.$this->getFieldIdForHTML().'"'.$this->additional_attributes.'>';
    }
}