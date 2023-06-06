<?php
namespace Platform\Form;

class PasswordField extends Field {
    
    private $inputwasparsed = false;
    
    protected static $component_class = 'platform_component_password_field';
    
    public function __construct() {
        parent::__construct();
        static::JSFile(\Platform\Utilities\Utilities::directoryToURL(__DIR__).'/js/Field.js'); 
        static::JSFile(\Platform\Utilities\Utilities::directoryToURL(__DIR__).'/js/PasswordField.js'); 
    }
    
    public function getValue() {
        if ($this->inputwasparsed) return $this->value;
        return null;
    }
    
    public function parse($value) : bool {
        // Check if there was legit input
        if ($_POST[$this->name.'__ischanged'] || $value != 'XXXXXX') {
            $this->inputwasparsed = true;
        }
        return parent::parse($value);
    }
    
    public function renderInput() {
        echo '<input type="hidden" name="'.$this->name.'__ischanged" value=0>';
        if ($this->inputwasparsed) $value = $this->value;
        else $value = $this->value ? 'XXXXXX' : '';
        $placeholder = trim($this->placeholder) ? ' placeholder="'.$this->placeholder.'"' : '';
        echo '<input data-fieldclass="'.$this->getFieldClass().'" class="'.$this->getFieldClasses().'" style="max-width: '.$this->field_width.';"'.$placeholder.' type="password" name="'.$this->name.'" id="'.$this->getFieldIdForHTML().'" value="'.htmlentities($this->value, ENT_QUOTES).'"'.$this->additional_attributes.'>';
    }
}