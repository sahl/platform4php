<?php
namespace Platform\Form;
/**
 * Field for inputting multi-line text
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=field_class
 */

class TextareaField extends TextField {
    
    protected static $component_class = 'platform_component_textarea_field';
    
    public function __construct() {
        parent::__construct();
        static::JSFile(\Platform\Utilities\Utilities::directoryToURL(__DIR__).'/js/Field.js'); 
        static::JSFile(\Platform\Utilities\Utilities::directoryToURL(__DIR__).'/js/TextareaField.js'); 
    }

    public static function Field(string $label, string $name, array $options = array()) {
        $field = parent::Field($label, $name, $options);
        if ($options['no_autosize']) {
            unset($options['no_autosize']);
        } else {
            $field->addClass('autosize');
        }
        return $field;
    }
    
    public function renderInput() {
        $placeholder = trim($this->placeholder) ? ' placeholder="'.$this->placeholder.'"' : '';
        echo '<textarea data-fieldclass="'.$this->getFieldClass().'" class="'.$this->getFieldClasses().'" style="max-width: '.$this->field_width.';"'.$placeholder.' name="'.$this->name.'" id="'.$this->getFieldIdForHTML().'"'.$this->additional_attributes.'>';
        echo $this->value;
        echo '</textarea>';
    }
}