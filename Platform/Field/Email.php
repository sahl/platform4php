<?php
namespace Platform;

class FieldEmail extends Field {
    
    public function __construct($label, $name, $options = array()) {
        Errorhandler::checkParams($label, 'string', $name, 'string', $options, 'array');
        parent::__construct($label, $name, $options);
    }
    
    public function parse($value) {
        if (! parent::parse($value)) return false;
        if ($value && ! preg_match('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/i', $value)) {
            $this->triggerError('Invalid email');
            return false;
        }
        return true;
    }
    
    public function renderInput() {
        echo '<input class="'.$this->getClassString().'" type="email" name="'.$this->name.'" id="'.$this->getFieldIdForHTML().'" value="'.htmlentities($this->value, ENT_QUOTES).'"'.$this->additional_attributes.'>';
    }
}