<?php
namespace Platform;

class Form {
    
    private $form_id = array();

    private $fields = array();
    
    private $validationfunctions = array();
    
    private $action = '';
    
    private $script = null;
    
    private $event = 'submit';
    
    public function __construct($form_id, $filename = '') {
        Errorhandler::checkParams($form_id, 'string', $filename, 'string');
        $this->form_id = $form_id;
        if ($filename) $this->getFromFile ($filename);
    }
    
    /**
     * Add one or more fields to a form
     * @param \Platform\Field|array<\Platform\Field> $fields Field(s) to add
     */
    public function addField($fields) {
        Errorhandler::checkParams($fields, array('\\Platform\\Field', 'array'));
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
     * @param \Platform\Field|array<\Platform\Field> $fields Field(s) to add
     * @param string $fieldname Field name to insert this field after.
     */
    public function addFieldAfter($fields, $fieldname = '') {
        Errorhandler::checkParams($fields, array('\\Platform\\Field', 'array'), $fieldname, 'string');
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
     * @param \Platform\Field|array<\Platform\Field> $fields Field(s) to add
     * @param string $fieldname Field name to insert this field before.
     */
    public function addFieldBefore($fields, $fieldname = '') {
        Errorhandler::checkParams($fields, array('\\Platform\\Field', 'array'), $fieldname, 'string');
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
    public function addHTML($html) {
        Errorhandler::checkParams($html, 'string');
        $this->addField(new FieldHTML($html));
    }
    
    private static function extractValue($fieldname, &$fragment) {
        Errorhandler::checkParams($fieldname, 'string');
        if (preg_match('/^(.*?)\\[(.*?)\\](.*)/', $fieldname, $matches)) {
            return self::extractValue($matches[2].$matches[3], $fragment[$matches[1]]);
        }
        return $fragment[$fieldname];
    }
    
    /**
     * Get all errors hashed by field ids
     */
    public function getAllErrors() {
        $errors = array();
        foreach ($this->fields as $field) {
            if ($field->isError()) $field->addErrors($errors);
        }
        return $errors;
    }
    
    /**
     * Get all attached fields
     * @return type
     */
    public function getAllFields() {
        return $this->fields;
    }
    
    /**
     * Get this form as HTML
     * @return string Form as html
     */
    public function getAsHTML() {
        ob_start();
        $this->render();
        $html = ob_get_clean();
        return $html;
    }

    /**
     * Get the event of this form (if it was posted)
     * @return string|boolean Event name or false if this form wasn't posted.
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
     * @return boolean|\Platform\Field The field or false if no field was found
     */
    public function getFieldByName($fieldname) {
        Errorhandler::checkParams($fieldname, 'string');
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
     * Get form fields from a file
     * @param string $filename
     */
    public function getFromFile($filename) {
        Errorhandler::checkParams($filename, 'string');
        $text = file_get_contents($filename);
        if ($text === false) trigger_error('Error opening form '.$filename, E_USER_ERROR);
        foreach (static::parseFieldsFromText($text) as $field) {
            $this->addField($field);
        }
    }
    
    /**
     * Get the ID of the form
     * @return int
     */
    public function getId() {
        return $this->form_id;
    }
    
    /**
     * Get all values from the form
     * @return array
     */
    public function getValues() {
       $result = array();
       foreach ($this->fields as $field) {
            /* @var $field Field */
            if ($field instanceof FieldHTML) continue;
            $value = $field->getValue();
            if ($value !== null) self::injectValue($field->getName(), $result, $value);
       }
       return $result;
    }
    
    private static function injectValue($fieldname, &$target, $value) {
        Errorhandler::checkParams($fieldname, 'string');
        if (preg_match('/^(.*?)\\[(.*?)\\](.*)/', $fieldname, $matches)) {
            self::injectValue($matches[2].$matches[3], $target[$matches[1]], $value);
            return;
        }
        $target[$fieldname] = $value;
    }
    
    
    /**
     * Return true if this form was submitted
     * @return boolean
     */
    public function isSubmitted() {
        return ($_POST['form_name'] == $this->form_id);
    }
    
    /**
     * Parse form fields from a text string
     * @param string $text
     * @return array<\Platform\Field> The parsed form fields.
     */
    public static function parseFieldsFromText($text) {
        Errorhandler::checkParams($text, 'string');
        $fields = array(); $storedfields = array();
        // Explode on tags
        $elements = explode('<', $text);
        // The first element is not a tag
        $fields[] = new FieldHTML(array_shift($elements));
        // Rest of elements are tags and text
        foreach ($elements as $element) {
            $tag = self::parseTag($element);
            // Check for special tag
            if (substr($tag['tag'],0,1) != '/') {
                $class = 'Platform\\Field'.ucfirst($tag['tag']);
                if (class_exists($class)) {
                    $label = $tag['properties']['label'];
                    $name = $tag['properties']['name'];
                    unset($tag['properties']['label']);
                    unset($tag['properties']['name']);
                    $fields[] = new $class($label, $name, $tag['properties']);
                    // If we encounter a multiplier, we want to direct the following fields into that
                    if (strtolower($tag['tag']) == 'multiplier') {
                        $storedfields[] = $fields;
                        $fields = array();
                    }
                    $fields[] = new FieldHTML($tag['text']);
                } else {
                    $fields[] = new FieldHTML('<'.$element);
                }
            } else {
                if (strtolower($tag['tag']) == '/multiplier') {
                    // If we encounter a multiplier end, then we put all the stored fields into that, and resumes normal operation
                    $restorefields = array_pop($storedfields);
                    $multiplier = end($restorefields);
                    $multiplier->addFields($fields);
                    reset($restorefields);
                    $fields = $restorefields;
                    $fields[] = new FieldHTML($tag['text']);
                }
                $fields[] = new FieldHTML('<'.$element);
            }
        }
        return $fields;
    }
    
    /**
     * Parse a HTML tag into an understandable format
     * @param string $tagtext Tag
     * @return array Parsed format
     */
    protected static function parseTag($tagtext) {
        Errorhandler::checkParams($tagtext, 'string');
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
    public function removeFieldByName($fieldname) {
        Errorhandler::checkParams($fieldname, 'string');
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
        $newfields = $this->fields;
        foreach ($this->fields as $field) {
            if ($fieldname != $field->getName()) $newfields[] = $field;
        }
        $this->fields = $newfields;
        return;
    }

    /**
     * Render the form
     */
    public function render() {
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
     * @param \Platform\Field|array<\Platform\Field> $fields Field(s) to add
     * @param string $fieldname Field name to replace.
     */
    public function replaceField($fields, $fieldname) {
        Errorhandler::checkParams($fields, array('\\Platform\\Field', 'array'));
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
    public function setAction($action) {
        Errorhandler::checkParams($action, 'string');
        $this->action = $action;
    }
    
    /**
     * Set the event to transmit with the form
     * @param string $event
     */
    public function setEvent($event) {
        Errorhandler::checkParams($event, 'string');
        $this->event = $event;
    }
    
    /**
     * Set the HTML ID of this form
     * @param string $id
     */
    public function setID($id) {
        $this->form_id = $id;
    }
    
    public function setScript($script) {
        Errorhandler::checkParams($script, 'string');
        $this->script = $script;
    }
    
    /**
     * Add values to this form
     * @param array $values Values hashed by their field name
     */
    public function setValues($values) {
        Errorhandler::checkParams($values, 'array');
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
     * @return boolean True if valid input
     */
    public function validate() {
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