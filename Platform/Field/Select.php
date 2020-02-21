<?php
namespace Platform;

class FieldSelect extends Field {
    
    public function __construct($label, $name, $options = array()) {
        Errorhandler::checkParams($label, 'string', $name, 'string', $options, 'array');
        parent::__construct($label, $name, $options);
        $this->classes[] = 'w3-input';
    }
    
    public function renderInput() {
        echo '<select class="'.$this->getClassString().'" name="'.$this->name.'" id="'.$this->getFieldIdForHTML().'"'.$this->additional_attributes.'>';
        echo '<option value="">'.$this->heading;
        foreach ($this->options as $key => $option) {
            $selected = $key == $this->value ? ' selected' : '';
            echo '<option value="'.htmlentities($key, ENT_QUOTES).'"'.$selected.'>'.$option;
        }
        echo '</select>';
    }
}