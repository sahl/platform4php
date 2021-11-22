<?php
namespace Platform\Form;

class MultiField extends MultiplierSection {
    
    public function __construct(string $label, string $name, array $options = array()) {
        parent::__construct($label, $name, $options);
    }
    
    public static function construct(Field $field) {
        $finalfield = new MultiField('', '', []);
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