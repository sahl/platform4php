<?php
namespace Platform\Form;
/**
 * Field for inputting numbers
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=field_class
 */

class NumberField extends Field {
    
    public function parse($value) : bool {
        if (! parent::parse($value)) return false;
        if ($this->value === '') {
            $this->value = null;
            return true;
        }
        if (!is_numeric($value)) {
            $this->triggerError('Must be a number');
            return false;
        }
        return true;
    }    
    
    public function renderInput() {
        $placeholder = trim($this->placeholder) ? ' placeholder="'.$this->placeholder.'"' : '';
        echo '<input data-fieldclass="'.$this->getFieldClass().'" class="'.$this->getFieldClasses().'" style="max-width: '.$this->field_width.';"'.$placeholder.' type="number" name="'.$this->name.'" id="'.$this->getFieldIdForHTML().'" value="'.htmlentities($this->value, ENT_QUOTES).'"'.$this->additional_attributes.'>';
    }
}