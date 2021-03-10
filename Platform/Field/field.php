<?php
namespace Platform;

class Field {
    
    const LABEL_ALIGN_TOP = 1;
    const LABEL_ALIGN_LEFT = 2;
    const LABEL_ALIGN_BOTTOM = 3;
    const LABEL_ALIGN_RIGHT = 4;
    const LABEL_ALIGN_NONE = 5;
    const LABEL_ALIGN_AUTO = 10;
    
    /**
     * Classes to add to form field
     * @var array 
     */
    protected $classes = array('platform_form_field');
    
    /**
     * Classes to add to form field container
     * @var array
     */
    protected $container_classes = array('platform_form_field_container');
    
    /**
     * Special styles for the container
     * @var array
     */
    protected $container_styles = array();
    
    /**
     * The current default label placement
     * @var int 
     */
    private static $default_label_alignment = self::LABEL_ALIGN_AUTO;

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
     * Label placement of this component
     * @var int
     */
    protected $label_alignment = self::LABEL_ALIGN_AUTO;

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
        Errorhandler::checkParams($label, 'string', $name, 'string', $options, 'array');
        if (in_array($name, array('form_event', 'form_name', 'form_hidden_fields'))) trigger_error('Used reserved form name', E_USER_ERROR);
        $this->label = $label;
        $this->name = $name;
        
        $this->setLabelAlignment(self::getDefaultLabelAlignment());
        
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
        if ($options['dont-clear']) {
            $this->addClass('platform_dont_clear');
            unset($options['dont-clear']);
        }
        if ($options['heading']) {
            $this->setHeading($options['heading']);
            unset($options['heading']);
        }
        if ($options['class']) {
            $this->addClass($options['class']);
            unset($options['class']);
        }
        if ($options['container-class']) {
            $this->addContainerClass($options['containerclass']);
            unset($options['containerclass']);
        }        
        if ($options['container-style']) {
            $this->container_styles[] = $options['container-style'];
            unset($options['container-style']);
        }        
        if ($options['label-alignment']) {
            switch (strtolower($options['label-alignment'])) {
                case 'auto': 
                    $this->setLabelAlignment(self::LABEL_ALIGN_AUTO);
                    break;
                case 'top': 
                    $this->setLabelAlignment(self::LABEL_ALIGN_TOP);
                    break;
                case 'bottom': 
                    $this->setLabelAlignment(self::LABEL_ALIGN_BOTTOM);
                    break;
                case 'left': 
                    $this->setLabelAlignment(self::LABEL_ALIGN_LEFT);
                    break;
                case 'right': 
                    $this->setLabelAlignment(self::LABEL_ALIGN_RIGHT);
                    break;
                case 'none': 
                    $this->setLabelAlignment(self::LABEL_ALIGN_NONE);
                    break;
                default:
                    trigger_error('Unknown alignment: '.$options['label-alignment'], E_USER_ERROR);
            }
            unset($options['label-alignment']);
        }
        
        if ($options['autofocus']) {
            $this->addClass('platform_autofocus');
            unset($options['platform_autofocus']);
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
        Errorhandler::checkParams($classes, array('string', 'array'));
        if (! is_array($classes)) $classes = array($classes);
        foreach ($classes as $class) $this->classes[] = $class;
    }
    
    /**
     * Add one or more class to the container of the field
     * @param string|array $classes Class name or array of class names
     */
    public function addContainerClass($classes) {
        Errorhandler::checkParams($classes, array('string', 'array'));
        if (! is_array($classes)) $classes = array($classes);
        foreach ($classes as $class) $this->container_classes[] = $class;
    }
    
    /**
     * Add a style to the container of this field
     * @param string|array $styles Style or array of styles
     */
    public function addContainerStyle($styles) {
        Errorhandler::checkParams($styles, array('string', 'array'));
        if (! is_array($styles)) $styles = array($styles);
        foreach ($styles as $style) $this->container_styles[] = $style;
    }
    
    /**
     * Add errors from this field to the given array
     * @param array $error_array Array to add to
     */
    public function addErrors(&$error_array) {
        Errorhandler::checkParams($error_array, 'array');
        if ($this->isError()) $error_array[$this->getFieldIdForHTML ()] = $this->getErrorText ();
    }
    
    /**
     * Attach this field to a form
     * @param \Platform\Form $form
     */
    public function attachToForm($form) {
        Errorhandler::checkParams($form, '\\Platform\\Form');
        $this->form = $form;
    }
    
    /**
     * Clear any error from this field
     */
    public function clearError() {
        if (! $this->is_error) return;
        $this->is_error = false;
        Utility::arrayRemove($this->classes, 'formfield_error');
        $this->errortext = '';
    }
    
    protected function getAutoLabelAlignment() {
        return self::LABEL_ALIGN_LEFT;
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
    
    /**
     * Get the default label placement
     * @return int
     */
    public static function getDefaultLabelAlignment() {
        return self::$default_label_alignment;
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
        if ($this->form) return $this->form->getFormId().'_'.$this->getName();
        return $this->name;
    }

    /**
     * Get the final label alignment of this field, resolving auto labels
     * @return int
     */
    public function getFinalLabelAlignment() {
        return $this->label_alignment == self::LABEL_ALIGN_AUTO ? $this->getAutoLabelAlignment() : $this->label_alignment;
    }
    
    /**
     * Get the label alignment of this field
     * @return int
     */
    public function getLabelAlignment() {
        return $this->label_alignment;
    }
    
    /**
     * Get html ID for this form
     * @return string
     */
    public function getFormId() {
        return $this->form ? $this->form->getId() : '';
    }
    
    /**
     * Get the field label
     * @return string
     */
    public function getLabel() {
        return $this->label;
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
     * Check if this field is in error
     * @return boolean
     */
    public function isError() {
        return $this->is_error;
    }
    
    private static function isValidLabelPlacement($label_placement) {
        return in_array($label_placement, [self::LABEL_ALIGN_TOP, self::LABEL_ALIGN_LEFT, self::LABEL_ALIGN_BOTTOM, self::LABEL_ALIGN_RIGHT, self::LABEL_ALIGN_NONE, self::LABEL_ALIGN_AUTO]);
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

        // Handle alignment
        if (! $this->getLabel()) $this->setLabelAlignment (self::LABEL_ALIGN_NONE);
        
        switch ($this->getFinalLabelAlignment()) {
            case self::LABEL_ALIGN_LEFT:
                echo '<div class="platform_field_label_container">';
                $this->renderLabel();
                echo '<div class="platform_field_input_container">';
                $this->renderInput();
                $this->renderErrorContainer($this->errortext);
                echo '</div>';
                echo '</div>';
            break;
            case self::LABEL_ALIGN_TOP:
                $this->renderLabel();
                echo '<div class="platform_field_input_container">';
                $this->renderInput();
                echo '</div>';
                $this->renderErrorContainer($this->errortext);
            break;
            case self::LABEL_ALIGN_BOTTOM:
                echo '<div class="platform_field_input_container">';
                $this->renderInput();
                $this->renderErrorContainer($this->errortext);
                echo '</div>';
                $this->renderLabel();
            break;
            case self::LABEL_ALIGN_RIGHT:
                echo '<div class="platform_field_label_container">';
                echo '<div class="platform_field_input_container">';
                $this->renderInput();
                $this->renderErrorContainer($this->errortext);
                echo '</div>';
                $this->renderLabel();
                echo '</div>';
            break;
            case self::LABEL_ALIGN_NONE:
                $this->renderInput();
                $this->renderErrorContainer($this->errortext);
            break;
        }
        
        echo '</div>';
    }
    
    /**
     * Render the error container for the field
     * @param string|boolean $text Error text to show
     */
    public function renderErrorContainer($text = '') {
        Errorhandler::checkParams($text, array('string', 'boolean'));
        $add = $text ? ' style="display:block;"' : '';
        echo '<div class="platform_field_error_container"'.$add.'>'.$text.'</div>';
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
        switch ($this->getFinalLabelAlignment()) {
            case self::LABEL_ALIGN_TOP:
            case self::LABEL_ALIGN_LEFT:
                echo '<label for="'.$this->getFieldIdForHTML().'">'.$this->label;
                if ($this->is_required) echo ' <span style="color:red; font-size: 0.8em;">*</span>';
                echo ':</label>';
            break;
            case self::LABEL_ALIGN_RIGHT:
                echo '<label for="'.$this->getFieldIdForHTML().'" class="platform_right_label"> - '.$this->label;
                if ($this->is_required) echo ' <span style="color:red; font-size: 0.8em;">*</span>';
                echo '</label>';
            break;
            case self::LABEL_ALIGN_BOTTOM:
                echo '<label for="'.$this->getFieldIdForHTML().'" class="platform_bottom_label">'.$this->label;
                if ($this->is_required) echo ' <span style="color:red; font-size: 0.8em;">*</span>';
                echo '</label>';
            break;
        }
        
    }
    
    /**
     * Set the classes of the container object
     * @param array $classes
     */
    public function setContainerClasses($classes) {
        Errorhandler::checkParams($classes, 'array');
        $this->container_classes = $classes;
    }
    
    /**
     * Set the default label alignment
     * @param int $label_alignment Label alignment
     */
    public static function setDefaultLabelAlignment(int $label_alignment) {
        if (! self::isValidLabelPlacement($label_alignment)) trigger_error('Invalid label placement: '.$label_alignment, E_USER_ERROR);
        self::$default_label_alignment = $label_alignment;
    }

    /**
     * Set the heading of the field
     * @param string $heading
     */
    public function setHeading(string $heading) {
        $this->heading = $heading;
    }
    
    /**
     * Set label alignment of this component
     * @param int $label_alignment
     */
    public function setLabelAlignment(int $label_alignment) {
        if (! self::isValidLabelPlacement($label_alignment)) trigger_error('Invalid label placement: '.$label_alignment, E_USER_ERROR);
        $this->label_alignment = $label_alignment;
    }
    
    /**
     * Set field name
     * @param string $new_name New field name
     */
    public function setName($new_name) {
        Errorhandler::checkParams($new_name, 'string');
        $this->name = $new_name;
    }
    
    
    /**
     * Set the options of the field
     * @param array $options
     */
    public function setOptions($options) {
        Errorhandler::checkParams($options, 'array');
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
        Errorhandler::checkParams($errortext, 'string');
        // We cannot trigger an error, if an error is already triggered.
        if ($this->is_error) return;
        $this->is_error = true;
        $this->classes[] = 'formfield_error';
        $this->errortext = $errortext;
    }
}