<?php
namespace Platform\Form;

class ComponentField extends Field {
    
    private $component = null;
    
    /**
     * Construct a ComponentField
     * @param string $label Label for field
     * @param string $name Name of field
     * @param array $options Options
     */
    public function __construct(string $label, string $name, array $options = array()) {
        parent::__construct($label, $name, $options);
        $this->addClass('platform_form_component');
    }
    
    /**
     * Attach a component to use for this field
     * @param \Platform\UI\FieldComponent $component
     */
    public function attachComponent(\Platform\UI\FieldComponent $component) {
        $this->component = $component;
        $this->component->attachField($this);
        $this->component->name = $this->getName();
    }
    
    public function parse($value) : bool {
        if ($this->component === null) trigger_error('You must attach a component before parse.', E_USER_ERROR);
        if (! parent::parse($value)) return false;
        if (! $this->component->parse($value)) return false;
        $this->value = (int)$this->value;
        return true;
    }
    
    public function setOptions(array $options) {
        if ($this->component === null) trigger_error('You must attach a component before setting options.', E_USER_ERROR);
        $this->component->setOptions($options);
    }
    
    public function setValue($value) {
        if ($this->component === null) trigger_error('You must attach a component before setting a value.', E_USER_ERROR);
        parent::setValue($value);
        $this->component->value = $value;
    }
    
    public function renderInput() {
        if ($this->component === null) trigger_error('You must attach a component before rendering.', E_USER_ERROR);
        $this->component->render();
    }
}