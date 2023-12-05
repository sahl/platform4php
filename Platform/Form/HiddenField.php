<?php
namespace Platform\Form;
/**
 * Field for hidden input
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=field_class
 */

class HiddenField extends Field {
    
    protected static $component_class = 'platform_component_hidden_field';
    
    public function __construct() {
        parent::__construct();
        $this->JSFile(\Platform\Utilities\Utilities::directoryToURL(__DIR__).'js/HiddenField.js');
    }
    
    public function renderInput() {
        echo '<input data-fieldclass="'.$this->getFieldClass().'" class="'.$this->getFieldClasses().'" type="hidden" name="'.$this->name.'" id="'.$this->getFieldIdForHTML().'" value="'.htmlentities($this->value, ENT_QUOTES).'"'.$this->additional_attributes.'>';
    }
}