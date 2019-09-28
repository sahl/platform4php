<?php
namespace Platform;

class FieldMulticheckbox extends Field {
    
    public function __construct($label, $name, $options = array()) {
        parent::__construct($label, $name, $options);
        $this->classes[] = '';
    }
    
    public function parse($value) {
        if (! is_array($value)) $value = array();
        $this->value = $value;
        return true;
    }    
    
    public function renderInput() {
        if (! $this->value) $this->value = array();
        echo '<div class="multi_checkbox_container" id="'.$this->getFieldIdForHTML().'">';
        foreach ($this->options as $key => $option) {
            $checked = in_array($key, $this->value);
            echo '<input class="'.$this->getClassString().'" style="vertical-align: -1px;" type="checkbox" name="'.$this->name.'[]" value="'.$key.'"'.$this->additional_attributes.$checked.'> '.$option.'<br>';
        }
        echo '</div>';
    }
}