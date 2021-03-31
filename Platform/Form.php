<?php
namespace Platform;

use Platform\Form\HTML;
use Platform\Utilities\Errorhandler;

class Form extends \Platform\UI\Component {
    
    private $form_id = array();

    private $fields = array();
    
    private $validationfunctions = array();
    
    private $action = '';
    
    private $script = null;
    
    private $event = 'submit';
    
    private static $field_name_space = ['Platform\\Form'];
    
    public function __construct(string $form_id, string $filename = '') {
        Page::JSFile('/Platform/Form/js/form.js');
        Page::JSFile('/Platform/Form/js/autosize.js');
        Page::JSFile('/Platform/Form/js/multiplier.js');
        Page::JSFile('/Platform/Form/js/combobox.js');
        Page::JSFile('/Platform/Form/js/texteditor.js');
        Page::JSFile('https://cdn.jsdelivr.net/npm/summernote@0.8.16/dist/summernote-lite.min.js');
        Page::CSSFile('/Platform/Form/css/form.css');
        Page::CSSFile('/Platform/Form/css/texteditor.css');
        Page::CSSFile('https://cdn.jsdelivr.net/npm/summernote@0.8.16/dist/summernote-lite.min.css');
        
        parent::__construct();
        $this->form_id = $form_id;
        if ($filename) $this->getFromFile ($filename);
    }
    
    /**
     * Add one or more fields to a form
     * @param \Platform\Form\Field|array<\Platform\Form\Field> $fields Field(s) to add
     */
    public function addField($fields) {
        Errorhandler::checkParams($fields, array('\\Platform\\Form\\Field', 'array'));
        if (! is_array($fields)) $fields = array($fields);
        foreach ($fields as $field) {
            if (! $field instanceof \Platform\Form\Field) trigger_error('Added non-field object to form', E_USER_ERROR);
            $this->fields[] = $field;
        }
        $field->attachToForm($this);
    }
    
    /**
     * Add fields after another field in this form. If no match the field are
     * inserted last in form
     * @param \Platform\Form\Field|array<\Platform\Form\Field> $fields Field(s) to add
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
                    $newfields[] = $formfield;
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
     * @param \Platform\Form\Field|array<\Platform\Form\Field> $fields Field(s) to add
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
        $this->addField(new Form\HTML($html));
    }

    /**
     * Extract a value from a complex field name such as field[1]
     * @param string $fieldname The complex field name
     * @param array $fragment The input array to retrieve data from
     * @return mixed
     */
    private static function extractValue(string $fieldname, array &$fragment) {
        if (preg_match('/^(.*?)\\[(.*?)\\](.*)/', $fieldname, $matches)) {
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
     * @return bool|\Platform\Form\Field The field or false if no field was found
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
        foreach (static::parseFieldsFromText($text) as $field) {
            $this->addField($field);
        }
    }
    
    /**
     * Get all values from the form
     * @return array
     */
    public function getValues() : array {
       $result = array();
       foreach ($this->fields as $field) {
            /* @var $field Field */
            if ($field instanceof FieldHTML) continue;
            $value = $field->getValue();
            if ($value !== null) self::injectValue($field->getName(), $result, $value);
       }
       return $result;
    }
    
    /**
     * Inject a value into a complex field name
     * @param string $fieldname Complex field name
     * @param array $target Value array
     * @param mixed $value Value to inject
     */
    private static function injectValue(string $fieldname, array &$target, $value) {
        if (preg_match('/^(.*?)\\[(.*?)\\](.*)/', $fieldname, $matches)) {
            self::injectValue($matches[2].$matches[3], $target[$matches[1]], $value);
            return;
        }
        $target[$fieldname] = $value;
    }
    
    
    /**
     * Return true if this form was submitted
     * @return bool
     */
    public function isSubmitted() : bool {
        return ($_POST['form_name'] == $this->form_id);
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
                    $fields[] = new $class($label, $name, $tag['properties']);
                    // If we encounter a multiplier, we want to direct the following fields into that
                    if (strtolower($tag['tag']) == 'multiplier') {
                        $storedfields[] = $fields;
                        $fields = array();
                    }
                    $fields[] = new HTML($tag['text']);
                } else {
                    $fields[] = new HTML('<'.$element);
                }
            } else {
                if (strtolower($tag['tag']) == '/multiplier') {
                    // If we encounter a multiplier end, then we put all the stored fields into that, and resumes normal operation
                    $restorefields = array_pop($storedfields);
                    $multiplier = end($restorefields);
                    $multiplier->addFields($fields);
                    reset($restorefields);
                    $fields = $restorefields;
                    $fields[] = new HTML($tag['text']);
                }
                $fields[] = new HTML('<'.$element);
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
        echo '<form id="'.$this->form_id.'" method="post" class="platform_form" action="'.$this->action.'">';
        echo '<input type="hidden" name="form_name" value="'.$this->form_id.'">';
        echo '<input type="hidden" name="form_event" value="'.$this->event.'">';
        echo '<input type="hidden" name="form_hiddenfields" value="">';
           
        foreach ($this->fields as $field) {
            /* @var $field Field */
            $field->render();
        }
        echo '</form>';
    }
    
    /**
     * Replaces a field in the form with a new field. If no match the field are
     * inserted last in form
     * @param \Platform\Form\Field|array<\Platform\Form\Field> $fields Field(s) to add
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
    public function setID(string $id) {
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
     * Validates this form
     * @return bool True if valid input
     */
    public function validate() : bool {
        $result = true;
        
        // Determine hidden fields
        $hiddenfields = $_POST['form_hiddenfields'] ? explode(' ', $_POST['form_hiddenfields']) : array();
        
        foreach ($this->fields as $field) {
            /* @var $field Field */
            if ($field instanceof FieldHTML || in_array($field->getName(), $hiddenfields) && ! $field instanceof FieldHidden) continue;
            $result = $field->parse(self::extractValue($field->getName(), $_POST)) && $result;
        }
        foreach ($this->validationfunctions as $validationfunction) {
            $result = $result && call_user_func($validationfunction, $this);
        }
        
        return $result;
    }
}