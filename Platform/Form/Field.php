<?php
namespace Platform\Form;

use Platform\Form;
use Platform\Utilities;
use Platform\Utilities\Errorhandler;

class Field {
    
    const LABEL_ALIGN_TOP = 1;
    const LABEL_ALIGN_LEFT = 2;
    const LABEL_ALIGN_BOTTOM = 3;
    const LABEL_ALIGN_RIGHT = 4;
    const LABEL_ALIGN_NONE = 5;
    const LABEL_ALIGN_AUTO = 10;
    
    const DEFAULT_HEIGHT = 0;
    
    const FIELD_SIZE_NORMAL = '280px';
    const FIELD_SIZE_SMALL = '120px';
    const FIELD_SIZE_TINY = '50px';
    
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
     * @var bool|string 
     */
    protected $errortext = false;
    
    /**
     * Form reference
     * @var Form 
     */
    protected $form = null;
    
    /**
     * Used for layout. Adds this field to the given group.
     * @var type
     */
    protected $group = 0;
    
    /**
     * Heading for fields including a heading
     * @var string 
     */
    protected $heading = '(Pick one)';
    
    /**
     * Indicate if the field is in error
     * @var bool 
     */
    protected $is_error = false;
    
    /**
     * Indicate if field is required
     * @var bool 
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
     * Label width
     * @var int
     */
    protected $label_width = 130;
    
    /**
     * Width of input field
     * @var int
     */
    public $field_width = self::FIELD_SIZE_NORMAL;
    
    /**
     * Height of the entire input construction
     * @var int
     */
    public $row_height = self::DEFAULT_HEIGHT;

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
     * Placeholder text for the input field
     * @var type
     */
    protected $placeholder = '';
    
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
    public function __construct(string $label, string $name, array $options = array()) {
        if (in_array($name, array('form_event', 'form_name', 'form_hidden_fields'))) trigger_error('Used reserved form name', E_USER_ERROR);
        $this->label = $label;
        $this->name = $name;
        
        $this->setLabelAlignment(self::getDefaultLabelAlignment());
        
        if ($options['required']) {
            $this->is_required = true;
            unset($options['required']);
        }
        if (isset($options['value'])) {
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
        if ($options['field-width']) {
            $this->setFieldWidth($options['field-width']);
            unset($options['field-width']);
        }
        if ($options['placeholder']) {
            $this->setPlaceholder($options['placeholder']);
            unset($options['placeholder']);
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
        
        if ($options['label-width']) {
            $this->setLabelWidth($options['label-width']);
            unset($options['label-width']);
        }
        
        if ($options['autofocus']) {
            $this->addClass('platform_autofocus');
            unset($options['platform_autofocus']);
        }
        
        if ($options['group']) {
            $this->setGroup($options['group']);
            unset($options['group']);
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
    public function addErrors(array &$error_array) {
        if ($this->isError()) $error_array[$this->getFieldIdForHTML ()] = $this->getErrorText ();
    }
    
    /**
     * Attach this field to a form
     * @param \Platform\Form $form
     */
    public function attachToForm(Form $form) {
        $this->form = $form;
    }
    
    /**
     * Clear any error from this field
     */
    public function clearError() {
        if (! $this->is_error) return;
        $this->is_error = false;
        Utilities::arrayRemove($this->classes, 'formfield_error');
        $this->errortext = '';
    }
    
    protected function getAutoLabelAlignment() : int {
        if ($this->form) return $this->form->getDefaultLabelAlignment ();
        return self::LABEL_ALIGN_LEFT;
    }
    
    /**
     * Get a string with all classes.
     * @return string
     */
    public function getClassString() : string {
        return implode(' ',$this->classes);
    }
    
    /**
     * Get a string with all classes for container.
     * @return string
     */
    public function getContainerClassString() : string {
        return implode(' ',$this->container_classes);
    }    
    
    /**
     * Get the default label placement
     * @return int
     */
    public static function getDefaultLabelAlignment() : int {
        return self::$default_label_alignment;
    }
    
    /**
     * Get the short class name of this field
     * @return string
     */
    public function getFieldClass() : string {
        $class = get_called_class();
        if (strpos($class,'\\') !== false) $class = substr($class, strrpos($class,'\\')+1);
        return $class;
    }
    
    /**
     * Get current placeholder text for this field
     * @return string
     */
    public function getPlaceholder() : string {
        return $this->placeholder;
    }
    
    public function getStyleString() : string {
        return implode(';',$this->container_styles);
    }
    
    /**
     * Get error text (if any)
     * @return string
     */
    public function getErrorText() : string {
        return $this->errortext;
    }

    /**
     * Get a unique html ID for the field.
     * @return string
     */
    public function getFieldIdForHTML() : string {
        if ($this->form) return $this->form->getFormId().'_'.$this->getName();
        return $this->name;
    }

    /**
     * Get the final label alignment of this field, resolving auto labels
     * @return int
     */
    public function getFinalLabelAlignment() : int {
        return $this->label_alignment == self::LABEL_ALIGN_AUTO ? $this->getAutoLabelAlignment() : $this->label_alignment;
    }
    
    /**
     * Get the label alignment of this field
     * @return int
     */
    public function getLabelAlignment() : int {
        return $this->label_alignment;
    }
    
    /**
     * Get html ID for this form
     * @return string
     */
    public function getFormId() : string {
        return $this->form ? $this->form->getId() : '';
    }
    
    /**
     * Get assigned group of this field. Used for layout
     * @return int
     */
    public function getGroup() : int {
        return $this->group;
    }
    
    /**
     * Get the field label
     * @return string
     */
    public function getLabel() : string {
        return $this->label;
    }
    
    /**
     * Get field name
     * @return string
     */
    public function getName() : string {
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
     * @return bool
     */
    public function isError() : bool {
        return $this->is_error;
    }
    
    public static function isValidLabelPlacement($label_placement) : bool {
        return in_array($label_placement, [self::LABEL_ALIGN_TOP, self::LABEL_ALIGN_LEFT, self::LABEL_ALIGN_BOTTOM, self::LABEL_ALIGN_RIGHT, self::LABEL_ALIGN_NONE, self::LABEL_ALIGN_AUTO]);
    }
    
    /**
     * Parse a value and validates it against the field
     * @param object $value
     * @return bool True if value was valid for field
     */
    public function parse($value) : bool {
        $this->value = $value;
        if ($this->is_required && is_string($value) && ! strlen($value)) {
            $this->triggerError('This is a required field');
            return false;
        }
        return true;
    }
    
    /**
     * Render the field
     */
    public function render() {
        echo '<div data-fieldclass="'.$this->getFieldClass().'" class="'.$this->getContainerClassString().'" id="'.$this->getFieldIdForHTML().'_container" style="min-height: '.$this->row_height.'px;'.$this->getStyleString().'">';

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
     * @param string|bool $text Error text to show
     */
    public function renderErrorContainer($text = '') {
        Errorhandler::checkParams($text, array('string', 'bool'));
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
                echo '<label style="width: '.$this->label_width.'px;" for="'.$this->getFieldIdForHTML().'" class="platform_top_label">'.$this->label;
                if ($this->is_required) echo ' <span style="color:red; font-size: 0.8em;">*</span>';
                echo '</label>';
            break;
            case self::LABEL_ALIGN_LEFT:
                echo '<label style="width: '.$this->label_width.'px;" for="'.$this->getFieldIdForHTML().'" class="platform_left_label">'.$this->label;
                if ($this->is_required) echo ' <span style="color:red; font-size: 0.8em;">*</span>';
                echo '</label>';
            break;
            case self::LABEL_ALIGN_RIGHT:
                echo '<label style="width: '.$this->label_width.'px;" for="'.$this->getFieldIdForHTML().'" class="platform_right_label"> - '.$this->label;
                if ($this->is_required) echo ' <span style="color:red; font-size: 0.8em;">*</span>';
                echo '</label>';
            break;
            case self::LABEL_ALIGN_BOTTOM:
                echo '<label style="width: '.$this->label_width.'px;" for="'.$this->getFieldIdForHTML().'" class="platform_bottom_label">'.$this->label;
                if ($this->is_required) echo ' <span style="color:red; font-size: 0.8em;">*</span>';
                echo '</label>';
            break;
        }
        
    }
    
    /**
     * Set the classes of the container object
     * @param array $classes
     */
    public function setContainerClasses(array $classes) {
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
     * Set the width of the field itself
     * @param type $width
     */
    public function setFieldWidth($width) {
        $this->field_width = $width;
    }
    
    /**
     * Set the form to focus this field
     */
    public function setFocus() {
        $this->addClass('platform_autofocus');
    }
    
    /**
     * Set the group for which to add this field. Used for layout.
     * @param int $group
     */
    public function setGroup(int $group) {
        $this->group = $group;
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
    public function setName(string $new_name) {
        $this->name = $new_name;
    }
    
    /**
     * Set the options of the field
     * @param array $options
     */
    public function setOptions(array $options) {
        $this->options = $options;
    }
    
    /**
     * Set placeholder text for this field
     * @param string $placeholder
     */
    public function setPlaceholder(string $placeholder) {
        $this->placeholder = $placeholder;
    }
    
    /**
     * Set the label of this field
     * @param string $label
     */
    public function setLabel(string $label) {
        $this->label = $label;
    }
    
    /**
     * Set the label width of this field
     * @param int $label_width Label width in px
     */
    public function setLabelWidth(int $label_width) {
        $this->label_width = $label_width;
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
    public function triggerError(string $errortext = '') {
        // We cannot trigger an error, if an error is already triggered.
        if ($this->is_error) return;
        $this->is_error = true;
        $this->classes[] = 'formfield_error';
        $this->errortext = $errortext;
    }
}