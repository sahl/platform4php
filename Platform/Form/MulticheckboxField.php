<?php
namespace Platform\Form;

class MulticheckboxField extends Field {
    
    public function __construct(string $label, string $name, array $options = array()) {
        parent::__construct($label, $name, $options);
        $this->classes[] = '';
    }
    
    public function parse($value) : bool {
        if (! is_array($value)) $value = array();
        $this->value = $value;
        return true;
    }    
    
    public function renderInput() {
        if (! $this->value) $this->value = array();
        echo '<div class="multi_checkbox_container" id="'.$this->getFieldIdForHTML().'">';
        foreach ($this->options as $key => $option) {
            $checked = in_array($key, $this->value) ? ' checked' : '';
            echo '<input class="'.$this->getClassString().'" style="vertical-align: -1px;" type="checkbox" name="'.$this->name.'[]" value="'.$key.'"'.$this->additional_attributes.$checked.'> '.$option.'<br>';
        }
        echo '</div>';
    }
}