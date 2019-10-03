<?php
namespace Platform;

class FieldSelect extends Field {
    
    public function __construct($label, $name, $options = array()) {
        parent::__construct($label, $name, $options);
        $this->classes[] = 'w3-input';
    }
    
    public function renderInput() {
        echo '<select class="'.$this->getClassString().'" name="'.$this->name.'" id="'.$this->getFieldIdForHTML().'"'.$this->additional_attributes.'>';
        echo '<option value="">'.$this->heading;
        foreach ($this->options as $key => $option) {
            $selected = $key == $this->value ? ' selected' : '';
            echo '<option value="'.$key.'"'.$selected.'>'.$option;
        }
        echo '</select>';
    }
}