<?php
namespace Platform\Form;

class TextareaField extends TextField {

    public function __construct(string $label, string $name, array $options = array()) {
        if ($options['no_autosize']) {
            unset($options['no_autosize']);
        } else {
            $this->classes[] = 'autosize';
        }
        parent::__construct($label, $name, $options);
    }
    
    public function renderInput() {
        $placeholder = trim($this->placeholder) ? ' placeholder="'.$this->placeholder.'"' : '';
        echo '<textarea data-fieldclass="'.$this->getFieldClass().'" class="'.$this->getClassString().'" style="max-width: '.$this->field_width.';"'.$placeholder.' name="'.$this->name.'" id="'.$this->getFieldIdForHTML().'"'.$this->additional_attributes.'>';
        echo $this->value;
        echo '</textarea>';
    }
}