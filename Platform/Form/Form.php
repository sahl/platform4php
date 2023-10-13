<?php
namespace Platform\Form;

use Platform\Currency\Currency;
use Platform\Currency\Rate;
use Platform\Form\Field;
use Platform\Form\HTML;
use Platform\Page\Page;
use Platform\Property;
use Platform\UI\Component;
use Platform\UI\FieldComponent;
use Platform\Utilities\Errorhandler;
use Platform\Utilities\Time;
use Platform\Utilities\Translation;

class Form extends Component {
    
    const SAVE_NO = 0;
    const SAVE_SESSION = 1;
    const SAVE_PROPERTY = 2;

    private $action = '';
    
    private $auto_submit = false;
    
    private $event = 'submit';
    
    private static $field_name_space = [];
    
    private $fields = array();
    
    private $global_errors = array();
    
    static protected $component_class = 'platform_component_form';
    
    protected $layout = null;
    
    private $layout_applied = false;
    
    protected $default_label_alignment = Field::LABEL_ALIGN_LEFT;
    
    public static $is_secure = false;

    private $save_on_submit = self::SAVE_NO;
    
    private $script = null;
    
    private $validationfunctions = array();
    
    public function __construct() {
        Page::JSFile('/Platform/Form/js/Form.js');
        Page::CSSFile('/Platform/Form/css/Form.css');
        Page::CSSFile('/Platform/Form/css/Layout.css');
        $this->addFormFieldNameSpace('Platform\\Form');
        
        $this->addPropertyMap(['form_id' => '']);
        
        parent::__construct();
    }
    
    public static function Form(string $form_id, string $filename = '') : Form {
        $form = new static();
        $form->setFormID($form_id);
        $form->setID($form_id.'_component');
        if ($filename) $form->getFromFile ($filename);
        return $form;
    }
    
    /**
     * Add one or more fields to a form
     * @param Field|array<\Platform\Form\Field> $fields Field(s) to add
     */
    public function addField($fields) {
        Errorhandler::checkParams($fields, array('\\Platform\\Form\\Field', 'array'));
        if (! is_array($fields)) $fields = array($fields);
        foreach ($fields as $field) {
            if (! $field instanceof Field) trigger_error('Added non-field object to form', E_USER_ERROR);
            $this->fields[] = $field;
        }
        $field->attachToForm($this);
    }
    
    /**
     * Add fields after another field in this form. If no match the field are
     * inserted last in form
     * @param Field|array<\Platform\Form\Field> $fields Field(s) to add
     * @param string $fieldname Field name to insert this field after.
     */
    public function addFieldAfter($fields, string $fieldname = '') {
        Errorhandler::checkParams($fields, array('\\Platform\\Form\\Field', 'array'));
        if (! is_array($fields)) $fields = array($fields);
        $newfields = array(); $inserted = false;
        foreach ($this->fields as $formfield) {
            $newfields[] = $formfield;
            if ($formfield->getName() == $fieldname && $fieldname && ! $inserted) {
                foreach ($fields as $field) {
                    if (! $field instanceof Field) trigger_error('Added non-field object to form', E_USER_ERROR);
                    $newfields[] = $field;
                    $field->attachToForm($this);
                }
                $inserted = true;
            }
        }
        if (! $inserted) {
            foreach ($fields as $field) {
                if (! $field instanceof Field) trigger_error('Added non-field object to form', E_USER_ERROR);
                $newfields[] = $formfield;
            }
        }
        $this->fields = $newfields;
    }
    
    /**
     * Add fields before another field in this form. If no match field are inserted
     * first in form
     * @param Field|array<\Platform\Form\Field> $fields Field(s) to add
     * @param string $fieldname Field name to insert this field before.
     */
    public function addFieldBefore($fields, string $fieldname = '') {
        Errorhandler::checkParams($fields, array('\\Platform\\Form\\Field', 'array'));
        if (! is_array($fields)) $fields = array($fields);
        $newfields = array(); $inserted = false;
        foreach ($this->fields as $formfield) {
            if ($formfield->getName() == $fieldname && $fieldname && ! $inserted) {
                foreach ($fields as $field) {
                    if (! $field instanceof Field) trigger_error('Added non-field object to form', E_USER_ERROR);
                    $newfields[] = $formfield;
                    $field->attachToForm($this);
                }
                $inserted = true;
            }
            $newfields[] = $formfield;
        }
        if (! $inserted) {
            foreach (array_reverse($fields) as $field) {
                if (! $field instanceof Field) trigger_error('Added non-field object to form', E_USER_ERROR);
                array_unshift($newfields, $field);
            }
        }
        $this->fields = $newfields;
    }

    /**
     * Add a namespace to search for form fields when parsing fields from html files
     * @param string $namespace Namespace to search
     */
    public static function addFormFieldNameSpace(string $namespace) {
        self::$field_name_space[] = $namespace;
    }
    
    /**
     * Add a validation function to this form.
     * @param function $function
     */
    public function addValidationFunction($function) {
        if (!is_callable($function)) trigger_error('Added invalid validation function to form', E_USER_ERROR);
        $this->validationfunctions[] = $function;
    }
    
    /**
     * Add a HTML-section to this form
     * @param string $html HTML to add
     */
    public function addHTML(string $html) {
        $this->addField(HTML::HTML($html));
    }

    /**
     * Destroy an index in the given array
     * @param array $index Index into array
     * @param array $post Array to destroy from
     */
    private static function destroyIndexFromPost($index, &$post) {
        $current = array_shift($index);
        if ($current == '') return;
        if (count($index)) {
            if (isset($post[$current])) self::destroyIndexFromPost($index, $post[$current]);
        } else {
            unset($post[$current]);
        }
    }

    /**
     * Extract a value from a complex field name such as field[1]
     * @param string $fieldname The complex field name
     * @param array $fragment The input array to retrieve data from
     * @return mixed
     */
    private static function extractValue(string $fieldname, array &$fragment) {
        if (preg_match('/^(.*?)\\[(.*?)\\](.*)/', $fieldname, $matches)) {
            if ($fragment[$matches[1]] === null) return null;
            return self::extractValue($matches[2].$matches[3], $fragment[$matches[1]]);
        }
        return $fragment[$fieldname];
    }
    
    /**
     * Get all errors hashed by field ids
     */
    public function getAllErrors() : array {
        $errors = array();
        foreach ($this->fields as $field) {
            if ($field->isError()) $field->addErrors($errors);
        }
        // Gather global errors
        if (count($this->global_errors)) $errors['__global'] = $this->global_errors;
        return $errors;
    }
    
    /**
     * Get all attached fields
     * @return array
     */
    public function getAllFields() : array {
        return $this->fields;
    }
    
    /**
     * Get this form as HTML
     * @return string Form as html
     */
    public function getAsHTML() : string {
        ob_start();
        $this->render();
        $html = ob_get_clean();
        return $html;
    }
    
    /**
     * Get the default label alignment for this form
     * @return int
     */
    public function getDefaultLabelAlignment() : int {
        return $this->default_label_alignment;
    }

    /**
     * Get the event of this form (if it was posted)
     * @return string|bool Event name or false if this form wasn't posted.
     */
    public function getEvent() {
        return $this->isSubmitted() ? $_POST['form_event'] : false;
    }

    /**
     * Get a field from the form by name. If a multiplier is present in the form
     * a field from that can be found by using a name on the following form:
     * MULTIPLIER_FIELD_NAME_IN_FORM/FIELD_NAME_IN_MULTIPLIER
     * TODO: Handle nested multipliers
     * @param string $fieldname Field name to find
     * @return bool|Field The field or false if no field was found
     */
    public function getFieldByName(string $fieldname) {
        if (strpos($fieldname,'/')) {
            $segments = explode('/',$fieldname);
            if (count($segments) != 2) return false;
            $field = $this->getFieldByName($segments[0]);
            if ($field instanceof FieldMultiplier) {
                return $field->getFieldByName($segments[1]);
            }
            return false;
        }
        /* @var $field Field */
        foreach ($this->fields as $field) {
            if ($fieldname == $field->getName()) return $field;
        }
        return false;
    }
    
    /**
     * Get the ID of the form
     * @return int
     */
    public function getFormId() : string {
        return $this->form_id;
    }    
    
    /**
     * Get form fields from a file
     * @param string $filename
     */
    public function getFromFile(string $filename) {
        $text = file_get_contents($filename);
        if ($text === false) trigger_error('Error opening form '.$filename, E_USER_ERROR);
        $this->getFromText($text);
    }
    
    /**
     * Get form fields from a text string
     * @param string $text Text string
     */
    public function getFromText(string $text) {
        foreach (static::parseFieldsFromText($text) as $field) {
            $this->addField($field);
        }
    }
    
    /**
     * Transforms a name like formfield[innervalue][2] to an array [formfield,innervalue,2]
     * @param string $form_name
     * @return array
     */
    private static function getIndexFromFormName($form_name) : array {
        return explode(',', str_replace(['[',']'], [',',''], $form_name));
    }
    
    
    /**
     * Get all values from the form
     * @return array
     */
    public function getValues() : array {
       $result = array();
       foreach ($this->fields as $field) {
            /* @var $field Field */
            if ($field instanceof HTML) continue;
            $value = $field->getValue();
            if ($value !== null) self::injectValue($field->getName(), $result, $value);
       }
       return $result;
    }
    
    /**
     * Get form values from serialized form output
     * @param string $serialized_form
     */
    public function getValuesFromSerializedForm(string $serialized_form) {
        $values = [];
        foreach (explode ('&', $serialized_form) as $parts) {
            $splitpos = strpos($parts,'=');
            $name = urldecode(substr($parts,0,$splitpos));
            $value = urldecode(substr($parts,$splitpos+1));
            self::injectValue($name, $values, $value);
        }
        $this->setValues($values);
    }
    
    /**
     * Inject a value into a complex field name
     * @param string $fieldname Complex field name
     * @param mixed $target Value array
     * @param mixed $value Value to inject
     */
    private static function injectValue(string $fieldname, &$target, $value) {
        if (preg_match('/^(.*?)\\[(.*?)\\](.*)/', $fieldname, $matches)) {
            if ($matches[2] == '' && $matches[3] == '') {
                $target[substr($fieldname,0,-2)][] = $value;
            } else {
                self::injectValue($matches[2].$matches[3], $target[$matches[1]], $value);
            }
            return;
        }
        $target[$fieldname] = $value;
    }
    
    
    /**
     * Return true if this form was submitted
     * @return bool
     */
    public function isSubmitted() : bool {
        return ($_POST['form_name'] && $_POST['form_name'] == $this->form_id);
    }
    
    /**
     * Load values previously stored by calling saveOnSubmit on the same form
     * @param bool $auto_submit_if_values If true then autosubmit the form is values were found
     * @return bool True if some values was loaded, otherwise false
     */
    public function loadValues(bool $auto_submit_if_values = false) : bool {
        // Try for a property
        $values = Property::getForCurrentUser('platform_saved_forms', $this->form_id);
        // Try for session
        if (! $values) $values = $_SESSION['platform']['saved_forms'][$this->form_id];
        // See if we got anything
        if ($values) {
            $this->getValuesFromSerializedForm($values);
            if ($auto_submit_if_values) $this->setAutoSubmit(true);
            return true;
        }
        return false;
    }
    
    /**
     * Parse form fields from a text string
     * @param string $text
     * @return array<\Platform\Form\Field> The parsed form fields.
     */
    public static function parseFieldsFromText(string $text) {
        $fields = array(); $storedfields = array();
        // Explode on tags
        $elements = explode('<', $text);
        // The first element is not a tag
        $fields[] = new HTML(array_shift($elements));
        // Rest of elements are tags and text
        foreach ($elements as $element) {
            $tag = self::parseTag($element);
            // Check for special tag
            if (substr($tag['tag'],0,1) != '/') {
                // Predefined
                switch ($tag['tag']) {
                    case 'button':
                        $class = 'Platform\\Form\\Button';
                        break;
                    case 'submit':
                        $class = 'Platform\\Form\\SubmitButton';
                        break;
                    case 'multiplier':
                        $class = 'Platform\\Form\\MultiplierSection';
                        break;
                    default:
                        foreach (self::$field_name_space as $name_space) {
                            $class = $name_space.'\\'.ucfirst($tag['tag'].'Field');
                            if (class_exists($class)) break;
                        }
                }
                if (class_exists($class)) {
                    $label = (string)$tag['properties']['label'];
                    $name = (string)$tag['properties']['name'];
                    unset($tag['properties']['label']);
                    unset($tag['properties']['name']);
                    // Handle value properties
                    foreach ($tag['properties'] as $key => $value) {
                        if (substr($key,0,6) == 'value-') {
                            $tag['properties']['options'][substr($key,6)] = Translation::translateForUser($value);
                            unset($tag['properties'][$key]);
                        }
                    }
                    
                    $fields[] = $class::Field($label, $name, $tag['properties']);
                    // If we encounter a multiplier, we want to direct the following fields into that
                    if (strtolower($tag['tag']) == 'multiplier') {
                        $storedfields[] = $fields;
                        $fields = array();
                    }
                    $fields[] = HTML::HTML($tag['text']);
                } else {
                    $fields[] = HTML::HTML('<'.$element);
                }
            } else {
                if (strtolower($tag['tag']) == '/multiplier') {
                    // If we encounter a multiplier end, then we put all the stored fields into that, and resumes normal operation
                    $restorefields = array_pop($storedfields);
                    $multiplier = end($restorefields);
                    $multiplier->addFields($fields);
                    reset($restorefields);
                    $fields = $restorefields;
                    $fields[] = HTML::HTML($tag['text']);
                }
                $fields[] = HTML::HTML('<'.$element);
            }
        }
        return $fields;
    }
    
    /**
     * Parse a HTML tag into an understandable format
     * @param string $tagtext Tag
     * @return array Parsed format
     */
    protected static function parseTag(string $tagtext) : array {
        $tagname = '';
        $properties = array();
        $currentname = '';
        $currentvalue = '';
        $delimiter = false;
        
        $mode = 'PARSETAG';
        foreach (str_split($tagtext) as $character) {
            switch ($mode) {
                case 'PARSETAG':
                    // Skip start tag (if present)
                    if ($character == '<') continue;
                    elseif ($character == ' ') {
                        $mode = 'PARSENAME';
                        continue;
                    }
                    elseif ($character == '>') {
                        $mode = 'RUNOUT';
                        continue;
                    }
                    $tagname .= $character;
                    break;
                case 'PARSENAME':
                    if ($character == ' ') {
                        if ($currentname == '') continue;
                        $properties[$currentname] = true;
                        $currentname = '';
                        continue;
                    } elseif ($character == '>') {
                        if ($currentname) $properties[$currentname] = true;
                        $currentname = '';
                        $mode = 'RUNOUT';
                        continue;
                    }
                    elseif ($character == '=') {
                        $mode = 'PARSEVALUE';
                        continue;
                    }
                    $currentname .= $character;
                    break;
                case 'PARSEVALUE':
                    if ($character == '\'' || $character == '"') {
                        if ($delimiter === false) {
                            $delimiter = $character;
                            continue;
                        } elseif ($delimiter == $character) {
                            $delimiter = false;
                            $properties[$currentname] = $currentvalue;
                            $currentname = ''; $currentvalue = '';
                            $mode = 'PARSENAME';
                            continue;
                        }
                    }
                    if ($character == ' ' && $delimiter === false) {
                        $mode = 'PARSENAME';
                        $properties[$currentname] = $currentvalue;
                        $currentname = ''; $currentvalue = '';
                        continue;
                    }
                    if ($character == '>' && $delimiter === false) {
                        $mode = 'RUNOUT';
                        $properties[$currentname] = $currentvalue;
                        $currentname = ''; $currentvalue = '';
                        continue;
                    }
                    $currentvalue .= $character;
                    break;
                case 'RUNOUT':
                    $currentvalue .= $character;
                    break;
            }
        }
        // Final handling
        if ($currentname) {
            $properties[$currentname] = $currentvalue;
            $currentvalue = '';
        }
        return array('tag' => $tagname, 'properties' => $properties, 'text' => $currentvalue);
    }
    
    
    protected function prepareData() {
        parent::prepareData();
        if ($this->save_on_submit != self::SAVE_NO) $this->addData('save_on_submit', $this->save_on_submit);
    }
    
    /**
     * Remove all fields from this form.
     */
    public function removeAllFields() {
        $this->fields = [];
    }
    
    /**
     * Removes a field from the form by name. If a multiplier is present in the form
     * a field from that can be found by using a name on the following form:
     * MULTIPLIER_FIELD_NAME_IN_FORM/FIELD_NAME_IN_MULTIPLIER
     * TODO: Handle nested multipliers
     * @param string $fieldname Field name to find
     */
    public function removeFieldByName(string $fieldname) {
        if (strpos($fieldname,'/')) {
            $segments = explode('/',$fieldname);
            if (count($segments) != 2) return false;
            $field = $this->getFieldByName($segments[0]);
            if ($field instanceof FieldMultiplier) {
                $field->removeFieldByName($segments[1]);
            }
            return;
        }
        /* @var $field Field */
        $newfields = [];
        foreach ($this->fields as $field) {
            if ($fieldname != $field->getName()) $newfields[] = $field;
        }
        $this->fields = $newfields;
        return;
    }

    /**
     * Render the form
     */
    public function renderContent() {
        if ($this->script) Page::JSFile ($this->script);
        
        /**
         * Apply layout if not yet applied
         */
        if (! $this->layout_applied && $this->layout) {
            $this->layout->apply($this);
            $this->layout_applied = true;
        }
        
        $form_class_string = 'platform_form';
        
        if ($this->auto_submit) $form_class_string .= ' platform_form_auto_submit';
        
        $additional_data = '';
        
        echo '<form id="'.$this->form_id.'" method="post" class="'.$form_class_string.'" action="'.$this->action.'"'.$additional_data.'>';
        echo '<input type="hidden" name="form_name" value="'.$this->form_id.'">';
        echo '<input type="hidden" name="form_event" value="'.$this->event.'">';
        echo '<input type="hidden" name="form_hiddenfields" value="">';
        
        echo '<div class="platform_form_global_error_container"></div>';
           
        foreach ($this->fields as $field) {
            /* @var $field Field */
            $field->render();
        }
        echo '</form>';
    }
    
    /**
     * Replaces a field in the form with a new field. If no match the field are
     * inserted last in form
     * @param Field|array<\Platform\Form\Field> $fields Field(s) to add
     * @param string $fieldname Field name to replace.
     */
    public function replaceField($fields, string $fieldname) {
        Errorhandler::checkParams($fields, array('\\Platform\\Form\\Field', 'array'));
        if (! is_array($fields)) $fields = array($fields);
        $newfields = array(); $inserted = false;
        foreach ($this->fields as $formfield) {
            if ($formfield->getName() == $fieldname && $fieldname && ! $inserted) {
                foreach ($fields as $field) {
                    if (! $field instanceof Field) trigger_error('Added non-field object to form', E_USER_ERROR);
                    $field->attachToForm($this);
                    $newfields[] = $field;
                }
                $inserted = true;
            } else {
                $newfields[] = $formfield;
            }
        }
        if (! $inserted) {
            foreach ($fields as $field) {
                if (! $field instanceof Field) trigger_error('Added non-field object to form', E_USER_ERROR);
                $field->attachToForm($this);
                $newfields[] = $formfield;
            }
        }
        $this->fields = $newfields;
    }
    
    
    /**
     * Set the action of the form
     * @param string $action
     */
    public function setAction(string $action) {
        $this->action = $action;
    }
    
    /**
     * Set if this form should autosubmit
     * @param bool $auto_submit
     */
    public function setAutoSubmit(bool $auto_submit = true) {
        $this->auto_submit = $auto_submit;
    }
    
    /**
     * Set the default label alignment for labels in this form
     * @param int $label_alignment
     */
    public function setDefaultLabelAlignment(int $label_alignment) {
        if (! Field::isValidLabelPlacement($label_alignment)) trigger_error('Invalid label placement: '.$label_alignment, E_USER_ERROR);
        $this->default_label_alignment = $label_alignment;
    }
    
    /**
     * Set the event to transmit with the form
     * @param string $event
     */
    public function setEvent(string $event) {
        $this->event = $event;
    }
    
    /**
     * Set the HTML ID of this form
     * @param string $id
     */
    public function setFormID(string $id) {
        $this->form_id = $id;
    }
    
    /**
     * Set label width for all fields added to this form
     * @param int $width
     */
    public function setLabelWidth(int $width) {
        foreach ($this->fields as $field) $field->setLabelWidth ($width);
    }
    
    /**
     * Set the layout of this form
     * @param Layout $layout
     */
    public function setLayout(Layout $layout) {
        $this->layout = $layout;
    }
    
    /**
     * Set if this form should be saved on submit
     * @param int $save_value SAVE_NO, SAVE_SESSION or SAVE_PROPERTY
     */
    public function setSaveOnSubmit(int $save_value = self::SAVE_SESSION) {
        if (! in_array($save_value, [self::SAVE_NO, self::SAVE_SESSION, self::SAVE_PROPERTY])) trigger_error('Invalid save on submit value', E_USER_ERROR);
        $this->save_on_submit = $save_value;
    }
    
    /**
     * Set a script for handling this form
     * @param string $script
     */
    public function setScript(string $script) {
        $this->script = $script;
    }
    
    /**
     * Add values to this form
     * @param array $values Values hashed by their field name
     */
    public function setValues(array $values) {
        foreach ($this->getAllFields() as $field) {
            $field_name = $field->getName();
            // Check for double array field name
            if (preg_match("/^(.+)\\[(.*)\\]\\[(.*)\\]$/i",$field_name, $target)) {
                if (isset($values[$target[1]][$target[2]][$target[3]]) && is_array($values[$target[1]][$target[2]]))
                    $field->setValue($values[$target[1]][$target[2]][$target[3]]);
            // Check for single array field name
            } elseif (preg_match("/^(.+)\\[(.*)\\]$/i",$field_name, $target)) {
                if (isset($values[$target[1]][$target[2]]) && is_array($values[$target[1]]))
                    $field->setValue($values[$target[1]][$target[2]]);
            // ...otherwise assume normal field name
            } else {
                if (isset($values[$field_name]))
                    $field->setValue($values[$field_name]);
            }
        }
    }
    
    /**
     * Trigger a global error on this form (a form error not related to a specific field)
     * @param string $error_text The error text
     */
    public function triggerGlobalError(string $error_text) {
        $this->global_errors[] = $error_text;
    }

    /**
     * Validates this form
     * @return bool True if valid input
     */
    public function validate() : bool {
        $result = true;
        
        // Determine hidden fields
        $hidden_fields = $_POST['form_hiddenfields'] ? explode(' ', $_POST['form_hiddenfields']) : array();

        foreach ($hidden_fields as $hidden_field) {
            $indexes = self::getIndexFromFormName($hidden_field);
            self::destroyIndexFromPost($indexes, $_POST);
        }
        
        foreach ($this->fields as $field) {
            /* @var $field Field */
            if ($field instanceof HTML || in_array($field->getName(), $hidden_fields) && ! $field instanceof HiddenField) continue;
            $result = $field->parse(self::extractValue($field->getName(), $_POST)) && $result;
        }
        foreach ($this->validationfunctions as $validationfunction) {
            $result = $result && call_user_func($validationfunction, $this);
        }
        
        return $result;
    }
}