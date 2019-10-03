<?php
namespace Platform;

class FieldTextarea extends FieldText {
    
    public function renderInput() {
        echo '<textarea class="'.$this->getClassString().'" name="'.$this->name.'" id="'.$this->getFieldIdForHTML().'"'.$this->additional_attributes.'>';
        echo $this->value;
        echo '</textarea>';
    }
}