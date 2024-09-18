<?php
namespace Platform\Form;
/**
 * Field for inputting a formatted number
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=field_class
 */

use Platform\Utilities\NumberFormat;
use Platform\Utilities\Utilities;

class FormattedNumberField extends Field {
    
    protected static $component_class = 'platform_component_formatted_number_field';
    
    public $minimum_value = false; // min/max allowed value or false for no limit
    public $maximum_value = false;
    protected $minimum_decimals = 0;
    protected $maximum_decimals = 1000;
    
    public function __construct() {
        parent::__construct();
        static::JSFile(Utilities::directoryToURL(__DIR__).'/js/FormattedNumberField.js'); 
    }
    
    public static function Field(string $label, string $name, array $options = array()) {
        $options['reserved_options'] = ['minimum_decimals', 'maximum_decimals', 'minimum_value', 'maximum_value'];
        $field = parent::Field($label, $name, $options);
        if (isset($options['minimum_value'])) {
            $field->minimum_value = (float)$options['minimum_value'];
            unset($options['minimum_value']);
        }
        if (isset($options['maximum_value'])) {
            $field->maximum_value = (float)$options['maximum_value'];
            unset($options['maximum_value']);
        }
        if (isset($options['minimum_decimals'])) {
            $field->minimum_decimals = (int)$options['minimum_decimals'];
            unset($options['minimum_decimals']);
        }
        if (isset($options['maximum_decimals'])) {
            $field->maximum_decimals = (int)$options['maximum_decimals'];
            unset($options['maximum_decimals']);
        }
        return $field;
    }
    
    /**
     * Parse a value and validates it against the field
     * @param object $value
     * @return bool True if value was valid for field
     */
    public function parse($value) : bool {
        $this->value = $value;
        $result = parent::parse($value);
        if (! $result) return false;
        
        if (!NumberFormat::isValid($value)) {
            $this->triggerError(\Platform\Utilities\Translation::translateForUser('Invalid number'));
            return false;
        }
        
        $parsed_number = NumberFormat::getUnformattedNumber($value);
        
        if (NumberFormat::getNumberOfDecimals($parsed_number) && $this->maximum_decimals == 0) {
            $this->triggerError(\Platform\Utilities\Translation::translateForUser('Decimal numbers not allowed'));
            return false;
        }
        
        if (NumberFormat::getNumberOfDecimals($parsed_number) > $this->maximum_decimals) {
            $this->triggerError(\Platform\Utilities\Translation::translateForUser('Max use %1 decimals', $this->maximum_decimals));
            return false;
        }
        
        return true;
    }
    
    public function getValue() {
        if ($this->value === '' || ! NumberFormat::isValid($this->value)) return null;
        return NumberFormat::getUnformattedNumber($this->value);
    }
    
    
    public function setValue($value) {
        if ($value !== null) {
            // Get number of decimals
            $number_of_decimals = NumberFormat::getNumberOfDecimals($value);
            $decimals_to_display = max(min($number_of_decimals, $this->maximum_decimals), $this->minimum_decimals);
            $this->value = NumberFormat::getFormattedNumber($value, $decimals_to_display, true);
        } else {
            $this->value = '';
        }
    }    
    
    public function prepareData() {
        parent::prepareData();
        $this->addData('minimum_decimals', $this->minimum_decimals);
    }
    
    public function renderInput() {
        $placeholder = trim($this->placeholder) ? ' placeholder="'.$this->placeholder.'"' : '';
        echo '<input data-fieldclass="'.$this->getFieldClass().'" class="'.$this->getFieldClasses().'" style="max-width: '.$this->field_width.';"'.$placeholder.' type="text" name="'.$this->name.'" id="'.$this->getFieldIdForHTML().'" value="'.htmlentities($this->value, ENT_QUOTES).'"'.$this->additional_attributes.'>';
    }
}