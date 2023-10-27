<?php
namespace Platform\Form;
/**
 * Field for hidden input
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=field_class
 */

class HiddenField extends Field {
    
    public function renderInput() {
        echo '<input data-fieldclass="'.$this->getFieldClass().'" class="'.$this->getFieldClasses().'" type="hidden" name="'.$this->name.'" id="'.$this->getFieldIdForHTML().'" value="'.htmlentities($this->value, ENT_QUOTES).'"'.$this->additional_attributes.'>';
    }
}