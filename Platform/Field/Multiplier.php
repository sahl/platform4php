<?php
namespace Platform;

class FieldMultiplier extends Field {
    
    protected $contained_fields = array();
    
    private $error_cache = array();
    
    public function __construct($label = '', $name = '', $options = array()) {
        Errorhandler::checkParams($label, 'string', $name, 'string', $options, 'array');
        $this->classes[] = 'platform_form_multiplier_element platform_formfield_container';
        $this->container_classes[] = 'platform_form_multiplier';
        $this->value = array();
        if ($options['sortable']) {
            $this->container_classes[] = 'platform_sortable';
            unset($options['sortable']);
        }
        parent::__construct($label, $name, $options);
    }
    
    /**
     * Add errors from this field to the given array
     * @param array $error_array Array to add to
     */
    public function addErrors(&$error_array) {
        Errorhandler::checkParams($error_array, 'array');
        for ($i = 0; $i < count($this->value)+1; $i++) {
            foreach ($this->contained_fields as $field) {
                // Store old field name
                $old_field_name = $field->getName();
                // Generate new field name
                $field->setName($this->getName().'['.$i.']['.$old_field_name.']');
                if (isset($this->error_cache[$i][$old_field_name])) {
                    $error_array[$field->getFieldIdForHTML ()] = $this->error_cache[$i][$old_field_name];
                }
                $field->setName($old_field_name);
            }
        }
    }
    
    /**
     * Add one or more fields to this multiplier
     * @param array|Field $fields One or more fields to add
     */
    public function addFields($fields) {
        Errorhandler::checkParams($classes, array('array', '\\Platform\\Field'));
        if (! is_array($fields)) $fields = array($fields);
        foreach ($fields as $field) {
            //if ($field instanceof FieldMultiplier) trigger_error('You cannot add a multiplier to another multiplier!', E_USER_ERROR);
            $this->contained_fields[] = $field;
        }
    }

    
    /**
     * Add all fields from a form file to this multiplier
     * @param string $filename Filename
     */
    public function addFieldsFromForm($filename) {
        Errorhandler::checkParams($filename, 'string');
        $text = file_get_contents($filename);
        if ($text === false) trigger_error('Error opening form '.$filename, E_USER_ERROR);
        $this->addFields(Form::parseFieldsFromText($text));
    }

    /**
     * Attach this field to a form
     * @param \Platform\Form $form
     */
    public function attachToForm($form) {
        Errorhandler::checkParams($form, '\\Platform\\Form');
        $this->form = $form;
        foreach ($this->contained_fields as $field) $field->attachToForm($form);
    }
    
    /**
     * Get a field by name
     * @param string $fieldname Field name
     * @return boolean|Field The field or false if no field was found
     */
    public function getFieldByName($fieldname) {
        Errorhandler::checkParams($fieldname, 'string');
        /* @var $field Field */
        foreach ($this->contained_fields as $field) {
            if ($fieldname == $field->getName()) return $field;
        }
        return false;
    }
    
    /**
     * Check if this field is in error
     * @return boolean
     */
    public function isError() {
        return count($this->error_cache) > 0;
    }
    
    
    public function parse($values) {

        // Determine hidden fields
        $hiddenfields = $_POST['form_hiddenfields'] ? explode(' ', $_POST['form_hiddenfields']) : array();
        
        $totalresult = true;
        // Always remove last entry as it is empty
        array_pop($values);
        $this->value = array();
        // Validate section fields
        for ($i = 0; $i < count($values); $i++) {
            foreach ($this->contained_fields as $field) {
                $adjustedname = $this->getName().'['.$i.']['.$field->getName().']';
                // Bail in certain cases
                if ($field instanceof FieldHTML || in_array($adjustedname, $hiddenfields) && ! $field instanceof FieldHidden) continue;
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
    
    /**
     * Remove a field by name from this multiplier
     * @param string $fieldname Field name
     */
    public function removeFieldByName($fieldname) {
        Errorhandler::checkParams($fieldname, 'string');
        $new_fields = array();
        foreach ($this->contained_fields as $field) {
            if ($fieldname != $field->getName()) $new_fields[] = $field;
        }
        $this->contained_fields = $new_fields;
    }
    
    public function renderInput() {
        for ($i = 0; $i < count($this->value)+1; $i++) {
            echo '<div class="'.$this->getClassString().'" id="'.$this->getFieldIdForHTML().'" '.$this->additional_attributes.'>';
            foreach ($this->contained_fields as $field) {
                // Store old field name
                $old_field_name = $field->getName();
                // Generate new field name
                $field->setName($this->getName().'['.$i.']['.$old_field_name.']');
                // Set value and trigger error if any
                if (! $field instanceof FieldHTML) {
                    if (isset($this->value[$i][$old_field_name])) {
                        $field->setValue($this->value[$i][$old_field_name]);
                    } else {
                        $field->setValue($field instanceof FieldMultiplier ? array() : '');
                    }
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
    }
    
    public function setValue($value) {
        if (! is_array($value)) trigger_error('Tried to pass non-array value to a multiplier', E_USER_ERROR);
        parent::setValue($value);
    }
}