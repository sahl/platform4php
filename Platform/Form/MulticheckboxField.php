<?php
namespace Platform\Form;

class MulticheckboxField extends Field {
    
    private $height;
    
    public function __construct(string $label, string $name, array $options = array()) {
        if ($options['height']) {
            $this->height = $options['height'];
            unset($options['height']);
        }
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
        $style = '';
        if ($this->height) $style = 'max-height: '.$this->height.'px; overflow: auto; padding: 3px;';
        echo '<div class="multi_checkbox_container" id="'.$this->getFieldIdForHTML().'" style="'.$style.'">';
        foreach ($this->options as $key => $option) {
            $checked = in_array($key, $this->value) ? ' checked' : '';
            echo '<input class="'.$this->getClassString().'" style="vertical-align: -1px;" type="checkbox" name="'.$this->name.'[]" value="'.$key.'"'.$this->additional_attributes.$checked.'> '.$option.'<br>';
        }
        echo '</div>';
    }
}