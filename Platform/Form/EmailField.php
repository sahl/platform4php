<?php
namespace Platform\Form;
/**
 * Field for inputting email addresses
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=field_class
 */

class EmailField extends Field {
    
    public function parse($value) : bool {
        if (! parent::parse($value)) return false;
        if ($value && ! preg_match('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/i', $value)) {
            $this->triggerError('Invalid email');
            return false;
        }
        return true;
    }
    
    public function renderInput() {
        $placeholder = trim($this->placeholder) ? ' placeholder="'.$this->placeholder.'"' : '';
        echo '<input data-fieldclass="'.$this->getFieldClass().'" class="'.$this->getFieldClasses().'" style="max-width: '.$this->field_width.';"'.$placeholder.' type="email" name="'.$this->name.'" id="'.$this->getFieldIdForHTML().'" value="'.htmlentities($this->value, ENT_QUOTES).'"'.$this->additional_attributes.'>';
    }
}