<?php
namespace Platform\Form;
/**
 * Field for showing a dropdown menu
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=field_class
 */

class SelectField extends Field {
    
    public static $component_class = 'platform_component_select_field';
    
    public function __construct() {
        parent::__construct();
        static::JSFile(\Platform\Utilities\Utilities::directoryToURL(__DIR__).'/js/Field.js'); 
        static::JSFile(\Platform\Utilities\Utilities::directoryToURL(__DIR__).'/js/SelectField.js'); 
    }
    
    public function renderInput() {
        echo '<select data-fieldclass="'.$this->getFieldClass().'" class="'.$this->getFieldClasses().'" style="max-width: '.$this->field_width.';" name="'.$this->name.'" id="'.$this->getFieldIdForHTML().'"'.$this->additional_attributes.'>';
        echo '<option value="">'.$this->heading;
        foreach ($this->options as $key => $option) {
            $selected = $key == $this->value ? ' selected' : '';
            echo '<option value="'.htmlentities($key, ENT_QUOTES).'"'.$selected.'>'.$option;
        }
        echo '</select>';
    }
}