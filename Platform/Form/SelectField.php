<?php
namespace Platform\Form;

class SelectField extends Field {
    
    public function __construct(string $label, string $name, array $options = array()) {
        parent::__construct($label, $name, $options);
    }
    
    public function renderInput() {
        echo '<select class="'.$this->getClassString().'" style="max-width: '.$this->field_width.';" name="'.$this->name.'" id="'.$this->getFieldIdForHTML().'"'.$this->additional_attributes.'>';
        echo '<option value="">'.$this->heading;
        foreach ($this->options as $key => $option) {
            $selected = $key == $this->value ? ' selected' : '';
            echo '<option value="'.htmlentities($key, ENT_QUOTES).'"'.$selected.'>'.$option;
        }
        echo '</select>';
    }
}