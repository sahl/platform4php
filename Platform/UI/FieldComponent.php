<?php
namespace Platform\UI;

class FieldComponent extends Component {
    
    /**
     * Used for pointing back to the field containing this component
     * @var \Platform\Form\ComponentField
     */
    private $field = null;
    
    public function __construct() {
        parent::__construct();
        $this->addPropertyMap(['name' => null, 'value' => null]);
    }
    
    /**
     * Attach a field to this component
     * @param \Platform\Form\ComponentField $field
     */
    public final function attachField(\Platform\Form\ComponentField $field) {
        $this->field = $field;
    }
    
    /**
     * Overwrite this to perform a parse of your component field. Must return true
     * if the field validates.
     * @param mixed $value Value from form
     * @return bool True if value is valid
     */
    public function parse($value) : bool {
        $this->value = $value;
        return true;
    }
    
    public function prepareData() {
        $this->addClass('platform_field_component_'.$this->name);
        $this->setID($this->field->getFieldIdForHTML());
        parent::prepareData();
    }
    
    /**
     * Overwrite to receive options from the form field
     * @param array $options
     */
    public function setOptions(array $options) {
        
    }
    
    /**
     * Trigger an error in the field behind this component
     * @param string $errortext
     */
    public function triggerError(string $errortext = '') {
        $this->field->triggerError($errortext);
    }
}