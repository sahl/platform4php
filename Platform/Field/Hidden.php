<?php
namespace Platform;

class FieldHidden extends Field {
    
    public function __construct($label, $name, $options = array()) {
        Errorhandler::checkParams($label, 'string', $name, 'string', $options, 'array');
        parent::__construct('', $name, $options);
    }
    
    public function render() {
        $this->renderInput();
    }
    
    public function renderInput() {
        echo '<input class="'.$this->getClassString().'" type="hidden" name="'.$this->name.'" id="'.$this->getFieldIdForHTML().'" value="'.htmlentities($this->value, ENT_QUOTES).'"'.$this->additional_attributes.'>';
    }
}