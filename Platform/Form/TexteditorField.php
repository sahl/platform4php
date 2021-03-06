<?php
namespace Platform\Form;

class TexteditorField extends TextField {
    
    public function __construct(string $label, string $name, array $options = array()) {
        $this->classes[] = 'texteditor';
        parent::__construct($label, $name, $options);
    }    
    
    public function renderInput() {
        echo '<textarea class="'.$this->getClassString().'" name="'.$this->name.'" id="'.$this->getFieldIdForHTML().'"'.$this->additional_attributes.'>';
        echo $this->value;
        echo '</textarea>';
    }
}