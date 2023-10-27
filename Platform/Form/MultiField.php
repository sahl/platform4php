<?php
namespace Platform\Form;
/**
 * Field that can transform other fields into an array
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=field_class
 */

class MultiField extends MultiplierSection {
    
    protected static $component_class = 'platform_component_form_multi_field';
    
    public function __construct() {
        parent::__construct();
        static::JSFile(\Platform\Utilities\Utilities::directoryToURL(__DIR__).'/js/Field.js');
        static::JSFile(\Platform\Utilities\Utilities::directoryToURL(__DIR__).'/js/MultiField.js');
    }
    
    public static function Field(string $label = '', string $name = '', array $options = []) {
        $field = parent::Field($label, $name, $options);
        return $field;
    }
    
    public static function MultiField(Field $field) {
        $finalfield = static::Field();
        $finalfield->addMultiField($field);
        return $finalfield;
    }
    
    public function addMultiField(Field $field) {
        // Modify this field
        $this->setLabel($field->getLabel());
        $this->setName($field->getName());

        // Modify origin field
        $field->setLabel('');
        $field->setName('innerfield');
        $this->addFields($field);
    }
    
    public function parse($values) : bool {
        $result = parent::parse($values);
        if ($result && $this->is_required && ! count($this->getValue())) {
            $this->triggerError('This is a required field');
            $result = false;
        }
        return $result;
    }
    
    public function getValue() {
        $values = parent::getValue();
        $real_results = array();
        foreach ($values as $value) {
            $real_results[] = $value['innerfield'];
        }
        return $real_results;
    }
    
    public function setValue($values) {
        if (! is_array($values)) $values = array();
        $real_values = array();
        foreach ($values as $value) {
            $real_values[] = array('innerfield' => $value);
        }
        parent::setValue($real_values);
    }
}