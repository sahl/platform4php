<?php
namespace Platform;

class FieldMultiplier extends Field {
    
    private $contained_fields = array();
    
    private $error_cache = array();
    
    public function __construct($label = '', $name = '', $options = array()) {
        parent::__construct('', $name, $options);
        $this->classes[] = 'w3-input';
        $this->value = array();
    }

    /**
     * Add one or more fields to this multiplier
     * @param array|Field $fields One or more fields to add
     */
    public function addFields($fields) {
        if (! is_array($fields)) $fields = array($fields);
        foreach ($fields as $field) {
            if ($field instanceof FieldMultiplier) trigger_error('You cannot add a multiplier to another multiplier!', E_USER_ERROR);
            $this->contained_fields[] = $field;
        }
    }
    
    /**
     * Get a field by name
     * @param string $fieldname Field name
     * @return boolean|Field The field or false if no field was found
     */
    public function getFieldByName($fieldname) {
        /* @var $field Field */
        foreach ($this->contained_fields as $field) {
            if ($fieldname == $field->getName()) return $field;
        }
        return false;
    }
    
    
    /**
     * Add all fields from a form file to this multiplier
     * @param string $filename Filename
     */
    public function addFieldsFromForm($filename) {
        $text = file_get_contents($filename);
        if ($text === false) trigger_error('Error opening form '.$filename, E_USER_ERROR);
        $this->addFields(Form::parseFieldsFromText($text));
    }
    
    public function parse($values) {
        $totalresult = true;
        // Always remove last entry as it is empty
        array_pop($values);
        // Validate section fields
        for ($i = 0; $i < count($values); $i++) {
            foreach ($this->contained_fields as $field) {
                // Bail in certain cases
                if ($field instanceof FieldHTML) continue;
                // Parse value for this field
                $result = $field->parse($values[$i][$field->getName()]);
                // Extract value to own cache
                $this->value[$i][$field->getName()] = $field->getValue();
                // Fail if error and store error in error cache
                if (! $result) {
                    $totalresult = false;
                    $this->error_cache[$i][$field->getName()] = $field->getErrorText();
                }
            }
        }
        return $totalresult;
    }
    
    public function render() {
        echo '<div class="platform_form_multiplier" id="#'.$this->getName().'">';
        for ($i = 0; $i < count($this->value)+1; $i++) {
            echo '<div class="platform_form_multiplier_element">';
            foreach ($this->contained_fields as $field) {
                // Store old field name
                $old_field_name = $field->getName();
                // Generate new field name
                $field->setName($this->getName().'['.$i.']['.$old_field_name.']');
                // Set value and trigger error if any
                if (isset($this->value[$i][$old_field_name])) {
                    $field->setValue($this->value[$i][$old_field_name]);
                } else {
                    $field->setValue('');
                }
                if (isset($this->error_cache[$i][$old_field_name])) {
                    $field->triggerError($this->error_cache[$i][$old_field_name]);
                } else {
                    $field->clearError();
                }
                $field->render();
                // Restore old name
                $field->setName($old_field_name);
            }
            echo '</div>';
        }
        echo '</div>';
    }
    
    public function setValue($value) {
        if (! is_array($value)) trigger_error('Tried to pass non-array value to a multiplier', E_USER_ERROR);
        parent::setValue($value);
    }
}