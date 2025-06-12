<?php
namespace Platform\Form;
/**
 * Field for adding a section to a form containing other fields which will repeat itself.
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=field_class
 */

use Platform\Form\Form;

class MultiplierSection extends Field {
    
    protected $contained_fields = array();
    
    protected $error_cache = array();
    
    protected static $component_class = 'platform_component_form_multiplier_section';
    
    public function __construct() {
        parent::__construct();
        static::JSFile(\Platform\Utilities\Utilities::directoryToURL(__DIR__).'js/Field.js');
        static::JSFile(\Platform\Utilities\Utilities::directoryToURL(__DIR__).'js/MultiplierSection.js');
        $this->value = [];
        $this->addClass('platform_form_multiplier');
    }
    
    public static function Field(string $label = '', string $name = '', array $options = array()) {
        $field = parent::Field($label, $name, $options);
        $field->addClass('platform_formfield_container');
        if ($options['sortable']) {
            $field->addClass('platform_sortable');
            unset($options['sortable']);
        }
        // No label for this field?
        return $field;
    }
    
    /**
     * Add errors from this field to the given array
     * @param array $error_array Array to add to
     */
    public function addErrors(array &$error_array) {
        for ($i = 0; $i < count($this->value)+1; $i++) {
            foreach ($this->contained_fields as $field) {
                // Store old field name
                $old_field_name = $field->getName();
                // Generate new field name
                $field->setName($this->getName().'['.$i.']['.$old_field_name.']');
                if (isset($this->error_cache[$i][$old_field_name])) {
                    $error_array[$field->getName()] = $this->error_cache[$i][$old_field_name];
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
        if (! is_array($fields)) $fields = array($fields);
        foreach ($fields as $field) {
            if (! $field->getName() && ! $field instanceof HTML) trigger_error('No name', E_USER_ERROR);
            //if ($field instanceof FieldMultiplier) trigger_error('You cannot add a multiplier to another multiplier!', E_USER_ERROR);
            if ($this->form) $field->setID($this->form->getFormId().'_'.$this->getName().'_'.$field->getName().'_component');
            $this->contained_fields[] = $field;
        }
    }

    
    /**
     * Add all fields from a form file to this multiplier
     * @param string $filename Filename
     */
    public function addFieldsFromForm(string $filename) {
        $text = file_get_contents($filename);
        if ($text === false) trigger_error('Error opening form '.$filename, E_USER_ERROR);
        $this->addFields(Form::parseFieldsFromText($text));
    }

    /**
     * Attach this field to a form
     * @param \Platform\Form\Form $form
     */
    public function attachToForm(Form $form) {
        parent::attachToForm($form);
        foreach ($this->contained_fields as $field) {
            $field->attachToForm($form);
            $field->setID($form->getFormId().'_'.$this->getName().'_'.$field->getName().'_component');
        }
    }
    
    /**
     * Get a field by name
     * @param string $fieldname Field name
     * @return bool|Field The field or false if no field was found
     */
    public function getFieldByName(string $fieldname) {
        /* @var $field Field */
        foreach ($this->contained_fields as $field) {
            if ($fieldname == $field->getName()) return $field;
        }
        return false;
    }
    
    /**
     * Check if this field is in error
     * @return bool
     */
    public function isError() : bool {
        return count($this->error_cache) > 0;
    }
    
    
    public function parse($values) : bool {
        // Determine hidden fields
        $hiddenfields = $_POST['form_hiddenfields'] ? explode(' ', $_POST['form_hiddenfields']) : array();
        
        $totalresult = true;
        if (! is_array($values)) return true;
        
        // Always remove last entry as it is empty
        array_pop($values);
        $this->value = array();
        // Validate section fields
        for ($i = 0; $i < count($values); $i++) {
            foreach ($this->contained_fields as $field) {
                $adjustedname = $this->getName().'['.$i.']['.$field->getName().']';
                // Bail in certain cases
                if ($field instanceof \Platform\Form\HTML || in_array($adjustedname, $hiddenfields) && ! $field instanceof FieldHidden) continue;
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
    public function removeFieldByName(string $fieldname) {
        $new_fields = array();
        foreach ($this->contained_fields as $field) {
            if ($fieldname != $field->getName()) $new_fields[] = $field;
        }
        $this->contained_fields = $new_fields;
    }
    
    public function renderInput() {
        echo '<div data-fieldclass="'.$this->getFieldClass().'" class="'.$this->getFieldClasses().' platform_form_multiplier_container" style="margin:0px;padding:0px;'.$this->getFieldStyleString().'" id="'.$this->getFieldIdForHTML().'" data-basename="'.$this->getName().'" '.$this->additional_attributes.'>';
        for ($i = 0; $i < count($this->value)+1; $i++) {
            echo '<div class="platform_form_multiplier_element">';
            foreach ($this->contained_fields as $field) {
                // Store old field name
                $old_field_name = $field->getName();
                // Generate new field name
                $field->setName($this->getName().'['.$i.']['.$old_field_name.']');
                if ($this->form) $field->setID($this->form->getFormId().'_'.$field->getName().'_component');
                // Set value and trigger error if any
                if (! $field instanceof HTML) {
                    if (isset($this->value[$i][$old_field_name])) {
                        $field->setValue($this->value[$i][$old_field_name]);
                    } else {
                        $field->setValue($field instanceof MultiplierSection ? array() : '');
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
        echo '</div>';
    }
    
    public function setValue($value) {
        if ($value === null) $value = [];
        if (! is_array($value)) trigger_error('Tried to pass non-array value to a multiplier', E_USER_ERROR);
        parent::setValue($value);
    }
}