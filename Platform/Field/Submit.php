<?php
namespace Platform;

class FieldSubmit extends Field {
    
    public function __construct($label, $name, $options = array()) {
        Errorhandler::checkParams($label, 'string', $name, 'string', $options, 'array');
        parent::__construct($label, $name, $options);
    }
    
    public function render() {
        echo '<input class="'.$this->getClassString().'" type="submit" value="'.$this->label.'" id="'.$this->getFieldIdForHTML().'"'.$this->additional_attributes.'>';
    }
    
    public function getClassString() {
        return 'w3-button w3-round w3-black';
    }
}