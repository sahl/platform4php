<?php
namespace Platform;

class Field {
    
    /**
     * Classes to add to form field
     * @var array 
     */
    protected $classes = array('platform_form_field');
    
    /**
     * Classes to add to form field container
     * @var array
     */
    protected $container_classes = array('formfield_container');
    
    /**
     * Special styles for the container
     * @var array
     */
    protected $container_styles = array();

    /**
     * Field error message
     * @var boolean|string 
     */
    protected $errortext = false;
    
    /**
     * Form reference
     * @var Form 
     */
    protected $form = null;
    
    /**
     * Heading for fields including a heading
     * @var string 
     */
    protected $heading = '(Pick one)';
    
    /**
     * Indicate if the field is in error
     * @var boolean 
     */
    protected $is_error = false;
    
    /**
     * Indicate if field is required
     * @var boolean 
     */
    protected $is_required = false;
    
    /**
     * Field label
     * @var string 
     */
    protected $label = '';

    /**
     * Field name (in HTML)
     * @var string 
     */
    protected $name = '';
    
    /**
     * Field options if field have specific options
     * @var array 
     */
    protected $options = array();
    
    /**
     * Field value
     * @var object 
     */
    protected $value = null;
    
    /**
     * Additional attributes for tag
     * @var string 
     */
    protected $additional_attributes = '';
    
    /**
     * Construct the form field
     * @param string $label Field label
     * @param string $name Field name
     * @param array $options Field options
     */
    public function __construct($label, $name, $options = array()) {
        if (in_array($name, array('form_action', 'form_name', 'form_hidden_fields'))) trigger_error('Used reserved form name', E_USER_ERROR);
        $this->label = $label;
        $this->name = $name;
        if ($options['required']) {
            $this->is_required = true;
            unset($options['required']);
        }
        if ($options['value']) {
            $this->setValue($options['value']);
            unset($options['value']);
        }
        if ($options['options']) {
            $this->setOptions($options['options']);
            unset($options['options']);
        }
        if ($options['heading']) {
            $this->setHeading($options['heading']);
            unset($options['heading']);
        }
        if ($options['class']) {
            $this->addClass($options['class']);
            unset($options['class']);
        }
        if ($options['containerclass']) {
            $this->addContainerClass($options['containerclass']);
            unset($options['containerclass']);
        }        
        
        if ($this->is_required) $this->classes[] = 'form_required_field';
        
        foreach ($options as $key => $val) {
            $this->additional_attributes .= ' '.$key.'="'.$val.'"';
        }
    }
    
    /**
     * Add one or more class to the field
     * @param string|array $classes Class name or array of class names
     */
    public function addClass($classes) {
        if (! is_array($classes)) $classes = array($classes);
        foreach ($classes as $class) $this->classes[] = $class;
    }
    
    /**
     * Add one or more class to the container of the field
     * @param string|array $classes Class name or array of class names
     */
    public function addContainerClass($classes) {
        if (! is_array($classes)) $classes = array($classes);
        foreach ($classes as $class) $this->container_classes[] = $class;
    }
    
    /**
     * Add a style to the container of this field
     * @param string|array $styles Style or array of styles
     */
    public function addContainerStyle($styles) {
        if (! is_array($styles)) $styles = array($styles);
        foreach ($styles as $style) $this->container_styles[] = $style;
    }
    
    /**
     * Attach this field to a form
     * @param \Platform\Form $form
     */
    public function attachToForm($form) {
        if (! $form instanceof Form) trigger_error('Tried to attach field to non-form object', E_USER_ERROR);
        $this->form = $form;
    }
    
    /**
     * Clear any error from this field
     */
    public function clearError() {
        if (! $this->is_error) return;
        $this->is_error = false;
        array_remove($this->classes, 'formfield_error');
        $this->errortext = '';
    }
    
    /**
     * Get a string with all classes.
     * @return string
     */
    public function getClassString() {
        return implode(' ',$this->classes);
    }
    
    /**
     * Get a string with all classes for container.
     * @return string
     */
    public function getContainerClassString() {
        return implode(' ',$this->container_classes);
    }    
    
    public function getStyleString() {
        return implode(';',$this->container_styles);
    }
    
    /**
     * Get error text (if any)
     * @return string
     */
    public function getErrorText() {
        return $this->errortext;
    }

    /**
     * Get a unique html ID for the field.
     * @return string
     */
    public function getFieldIdForHTML() {
        if ($this->form) return $this->form->getId().'_'.$this->name;
        return $this->name;
    }
    
    /**
     * Get html ID for this form
     * @return string
     */
    public function getFormId() {
        return $this->form ? $this->form->getId() : '';
    }
    
    /**
     * Get field name
     * @return string
     */
    public function getName() {
        return $this->name;
    }
    
    /**
     * Get field value
     * @return object
     */
    public function getValue() {
        return $this->value;
    }
    
    /**
     * Parse a value and validates it against the field
     * @param object $value
     * @return boolean True if value was valid for field
     */
    public function parse($value) {
        $this->value = $value;
        if ($this->is_required && ! strlen($value)) {
            $this->triggerError('This is a required field');
            return false;
        }
        return true;
    }
    
    /**
     * Render the field
     */
    public function render() {
        echo '<div class="'.$this->getContainerClassString().'" id="'.$this->getFieldIdForHTML().'_container" style="'.$this->getStyleString().'">';
        $this->renderLabel();
        $this->renderInput();
        $this->renderErrorContainer($this->errortext);
        echo '</div>';
    }
    
    /**
     * Render the error container for the field
     * @param string $text Error text to show
     */
    public function renderErrorContainer($text = '') {
        $add = $text ? ' style="display:block;"' : '';
        echo '<div class="formfield_error_container"'.$add.'>'.$text.'</div>';
    }
    
    /**
     * Render the input field
     */
    public function renderInput() {
        
    }
    
    /**
     * Render the label
     */
    public function renderLabel() {
        if (! $this->label) return;
        echo '<label for="'.$this->getFieldIdForHTML().'">'.$this->label.'</label>';
        if ($this->is_required) echo ' (<span style="color:red">*</span>)';
    }
    
    public function setContainerClasses($classes) {
        $this->container_classes = $classes;
    }

    /**
     * Set the heading of the field
     * @param string $heading
     */
    public function setHeading($heading) {
        $this->heading = $heading;
    }
    
    /**
     * Set field name
     * @param string $new_name New field name
     */
    public function setName($new_name) {
        $this->name = $new_name;
    }
    
    
    /**
     * Set the options of the field
     * @param array $options
     */
    public function setOptions($options) {
        if (! is_array($options)) $options = array($options);
        $this->options = $options;
    }
    
    /**
     * Set the value of this field (with no validation)
     * @param object $value
     */
    public function setValue($value) {
        $this->value = $value;
    }
    
    /**
     * Trigger an error on this field
     * @param string $errortext Error text
     */
    public function triggerError($errortext = '') {
        // We cannot trigger an error, if an error is already triggered.
        if ($this->is_error) return;
        $this->is_error = true;
        $this->classes[] = 'formfield_error';
        $this->errortext = $errortext;
    }
}