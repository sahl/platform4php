<?php
namespace Platform\Form;
/**
 * Field for inputting text
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=field_class
 */

class TextField extends Field {
    
    public function renderInput() {
        $placeholder = trim($this->placeholder) ? ' placeholder="'.$this->placeholder.'"' : '';
        echo '<input data-fieldclass="'.$this->getFieldClass().'" class="'.$this->getFieldClasses().'" style="'.$this->getFieldStyleString().'"'.$placeholder.' type="text" name="'.$this->name.'" id="'.$this->getFieldIdForHTML().'" value="'.htmlentities($this->value, ENT_QUOTES).'"'.$this->additional_attributes.'>';
    }
}