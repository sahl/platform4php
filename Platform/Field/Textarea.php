<?php
namespace Platform;

class FieldTextarea extends FieldText {

    public function __construct($label, $name, $options = array()) {
        Errorhandler::checkParams($label, 'string', $name, 'string', $options, 'array');
        if ($options['no_autosize']) {
            unset($options['no_autosize']);
        } else {
            $this->classes[] = 'autosize';
        }
        parent::__construct($label, $name, $options);
    }
    
    public function renderInput() {
        echo '<textarea class="'.$this->getClassString().'" name="'.$this->name.'" id="'.$this->getFieldIdForHTML().'"'.$this->additional_attributes.'>';
        echo $this->value;
        echo '</textarea>';
    }
}