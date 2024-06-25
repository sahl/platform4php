<?php
namespace Platform\Form;
/**
 * Base class for other fields. Extend this class to create new form fields
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=field_class
 */

use Platform\Datarecord\Collection;
use Platform\Form\Form;
use Platform\UI\Component;
use Platform\Utilities\Errorhandler;
use Platform\Utilities\Utilities;

class Field extends Component {
    
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
    
    static protected $component_class = 'platform_component_field';
    
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
    protected $options = [];
    
    /**
     * Field options can have colours.
     * @var array
     */
    protected $options_colours = [];
    
    /**
     * Field option colours (if they should be coloured)
     * @var array
     */
    protected $colours = [];
    
    /**
     * Placeholder text for the input field
     * @var type
     */
    protected $placeholder = '';
    
    /**
     * Classes to apply to field
     * @var array
     */
    protected $field_classes = [];
    
    /**
     * Styles to apply to field
     * @var array
     */
    protected $field_styles = [];
    
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
    
    public function __construct() {
        parent::__construct();
        $this->addFieldClass('platform_form_field');
        $this->addClass('platform_form_field_component');
        static::JSFile(Utilities::directoryToURL(__DIR__).'js/Field.js');
    }
    
    /**
     * Construct the form field
     * @param string $label Field label
     * @param string $name Field name
     * @param array $options Field options
     */
    public static function Field(string $label, string $name, array $options = array()) {
        $field = new Static();
        if (in_array($name, array('form_event', 'form_name', 'form_hidden_fields'))) trigger_error('Used reserved form name', E_USER_ERROR);
        $field->label = $label;
        $field->name = $name;
        
        if (array_key_exists('required', $options)) {
            $field->is_required = true;
            unset($options['required']);
        }
        if (array_key_exists('value', $options)) {
            $field->setValue($options['value']);
            unset($options['value']);
        }
        if (array_key_exists('options', $options)) {
            $field->setOptions($options['options']);
            unset($options['options']);
        }
        if (array_key_exists('options_colours', $options)) {
            $field->setOptionsColours($options['options_colours']);
            unset($options['options_colours']);
        }
        if (array_key_exists('dont-clear', $options)) {
            $field->addFieldClass('platform_dont_clear');
            unset($options['dont-clear']);
        }
        if (array_key_exists('heading', $options)) {
            $field->setHeading($options['heading']);
            unset($options['heading']);
        }
        if (array_key_exists('class', $options)) {
            $field->addFieldClass($options['class']);
            unset($options['class']);
        }
        if (array_key_exists('containerclass', $options)) {
            $field->addClass($options['containerclass']);
            unset($options['containerclass']);
        }        
        if (array_key_exists('container-style', $options)) {
            $field->container_styles[] = $options['container-style'];
            unset($options['container-style']);
        }        
        if (array_key_exists('field-width', $options)) {
            $field->setFieldWidth($options['field-width']);
            unset($options['field-width']);
        }
        if (array_key_exists('placeholder', $options)) {
            $field->setPlaceholder($options['placeholder']);
            unset($options['placeholder']);
        }
        if (array_key_exists('label-alignment', $options)) {
            switch (strtolower($options['label-alignment'])) {
                case 'auto': 
                    $field->setLabelAlignment(self::LABEL_ALIGN_AUTO);
                    break;
                case 'top': 
                    $field->setLabelAlignment(self::LABEL_ALIGN_TOP);
                    break;
                case 'bottom': 
                    $field->setLabelAlignment(self::LABEL_ALIGN_BOTTOM);
                    break;
                case 'left': 
                    $field->setLabelAlignment(self::LABEL_ALIGN_LEFT);
                    break;
                case 'right': 
                    $field->setLabelAlignment(self::LABEL_ALIGN_RIGHT);
                    break;
                case 'none': 
                    $field->setLabelAlignment(self::LABEL_ALIGN_NONE);
                    break;
                default:
                    trigger_error('Unknown alignment: '.$options['label-alignment'], E_USER_ERROR);
            }
            unset($options['label-alignment']);
        }
        
        if (array_key_exists('label-width', $options)) {
            $field->setLabelWidth($options['label-width']);
            unset($options['label-width']);
        }
        
        if (array_key_exists('autofocus', $options)) {
            $field->addFieldClass('platform_autofocus');
            unset($options['autofocus']);
        }
        
        if (array_key_exists('group', $options)) {
            $field->setGroup($options['group']);
            unset($options['group']);
        }
        
        if ($field->is_required) $field->addClass('form_required_field');
        
        // Add the rest of the options as attributes
        foreach ($options as $key => $val) {
            // Some options can be reserved by subclasses. These shouldn't be added as attributes
            if ($key == 'reserved_options' || array_key_exists('reserved_options', $options) && in_array($key, $options['reserved_options'])) continue;
            $field->addAttribute($key, $val);
        }
        return $field;
    }
    
    /**
     * Add an attribute to this form field
     * @param string $attribute Name of attribute to add
     * @param type $value Value of attribute (if any).
     */
    public function addAttribute(string $attribute, $value = false) {
        if ($value === false) $this->additional_attributes .= ' '.$attribute;
        else $this->additional_attributes .= ' '.$attribute.'="'.$value.'"';
    }
    
    /**
     * Add one or more class to the field itself
     * @param string|array $classes Class name or array of class names
     */
    public function addFieldClass($classes) {
        if (! is_array($classes)) $classes = array($classes);
        foreach ($classes as $class) $this->field_classes[] = $class;
    }
    
    /**
     * Add a style to the container of this field
     * @param string|array $styles Style or array of styles
     */
    public function addFieldStyle($styles) {
        if (! is_array($styles)) $styles = array($styles);
        foreach ($styles as $style) $this->field_styles[] = $style;
    }
    
    /**
     * Add errors from this field to the given array
     * @param array $error_array Array to add to
     */
    public function addErrors(array &$error_array) {
        if ($this->isError()) $error_array[$this->getName()] = $this->getErrorText ();
    }
    
    /**
     * Attach this field to a form
     * @param Form $form
     */
    public function attachToForm(Form $form) {
        $this->form = $form;
        $this->setID($form->getFormId().'_'.$this->getName().'_component');
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
    
    public function getFieldClasses() : string {
        return implode(' ', $this->field_classes);
    }
    
    /**
     * Get current placeholder text for this field
     * @return string
     */
    public function getPlaceholder() : string {
        return $this->placeholder;
    }
    
    /**
     * Check if this field is required
     * @return bool
     */
    public function getRequired() : bool {
        return $this->is_required;
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
     * Get options
     * @return array
     */
    public function getOptions() : array {
        return $this->options;
    }
    
    /**
     * Get options colours
     * @return array
     */
    public function getOptionsColours() : array {
        return $this->options_colours;
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
    
    protected function prepareData() {
        parent::prepareData();
        $this->addData('field_name', $this->name);
    }
    
    /**
     * Render the field
     */
    public function renderContent() {
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
                echo '<label style="width: '.$this->label_width.'px;" for="'.$this->getFieldIdForHTML().'" class="platform_right_label">'.$this->label;
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
        $this->addFieldClass('platform_autofocus');
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
    public function setOptions(array|Collection $options) {
        if ($options instanceof Collection) $options = $options->getAllAsArray();
        $this->options = $options;
    }
    
    /**
     * Set the colours of the options of this field
     * @param array|Collection $options_colours
     */
    public function setOptionsColours(array|Collection $options_colours) {
        if ($options_colours instanceof Collection) $options_colours = $options_colours->getAllAsArray();
        $this->options_colours = $options_colours;
    }
    
    /**
     * Set placeholder text for this field
     * @param string $placeholder
     */
    public function setPlaceholder(string $placeholder) {
        $this->placeholder = $placeholder;
    }
    
    /**
     * Set if the field is requried.
     * @param bool $required
     */
    public function setRequired(bool $required) {
        $this->is_required = $required;
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
        $this->addFieldClass('formfield_error');
        $this->errortext = $errortext;
    }
}