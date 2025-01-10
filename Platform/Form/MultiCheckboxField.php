<?php
namespace Platform\Form;
/**
 * Field for selecting several of many checkboxes
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=field_class
 */

class MultiCheckboxField extends Field {
    
    public $height;
    
    public static $component_class = 'platform_component_multi_checkbox_field';
    
    public function __construct() {
        parent::__construct();
        static::JSFile(\Platform\Utilities\Utilities::directoryToURL(__DIR__).'/js/Field.js'); 
        static::JSFile(\Platform\Utilities\Utilities::directoryToURL(__DIR__).'/js/MultiCheckboxField.js'); 
        $this->addFieldClass('platform_multicheck_container');
    }
    
    public static function Field(string $label, string $name, array $options = array()) {
        $field = parent::Field($label, $name, $options);
        if ($options['height']) {
            $field->height = $options['height'];
            unset($options['height']);
        }
//        $field->addClass('multi_checkbox_container');
        return $field;
    }
    
    public function parse($value) : bool {
        $result = true;
        if (! is_array($value)) $value = array();
        if ($this->is_required && ! count($value)) {
            $this->triggerError(\Platform\Utilities\Translation::translateForUser(\Platform\Utilities\Translation::translateForUser('This is a required field')));
            $result = false;
        }
        if ($result && $this->allowed_options !== false) {
            foreach ($value as $v) {
                if (! in_array($v, $this->getAllowedOptions())) {
                    $this->triggerError(\Platform\Utilities\Translation::translateForUser('One or more selected values are not allowed'));
                    $result = false;
                }
            }
        }
        $this->value = $value;
        return $result;
    }    
    
    public function renderInput() {
        $allowed_options = $this->getAllowedOptions();
        if (! $this->value) $this->value = array();
        $style = 'max-width: '.$this->field_width.';';
        if ($this->height) $style = 'max-height: '.$this->height.'px; overflow: auto; padding: 3px;';
        echo '<div data-fieldclass="'.$this->getFieldClass().'" id="'.$this->getFieldIdForHTML().'" style="'.$style.'" class="'.$this->getFieldClasses().'" data-realname="'.$this->name.'"'.$this->additional_attributes.'>';
        foreach ($this->options as $key => $option) {
            $checked = in_array($key, $this->value) ? ' checked' : '';
            $class = in_array($key, $allowed_options) ? '' : ' platform_hidden_option';
            echo '<div class="platform_multicheck_option'.$class.'"><input style="vertical-align: -1px; margin: 0px;" type="checkbox" name="'.$this->name.'[]" value="'.$key.'"'.$checked.'> '.$option.'</div>';
        }
        echo '</div>';
    }
}