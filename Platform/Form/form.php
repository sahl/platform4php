<?php
namespace Platform;

class Form {
    
    private $form_id = array();

    private $fields = array();
    
    private $validationfunctions = array();
    
    private $action = 'submit';
    
    public function __construct($form_id, $filename = '') {
        $this->form_id = $form_id;
        if ($filename) $this->getFromFile ($filename);
    }
    
    /**
     * Add one or more fields to a form
     * @param \Platform\Field|array<\Platform\Field> $fields Field(s) to add
     */
    public function addField($fields) {
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
        $this->addField(new FieldHTML($html));
    }
    
    private static function extractValue($fieldname, &$fragment) {
        if (preg_match('/^(.*?)\\[(.*?)\\](.*)/', $fieldname, $matches)) {
            return self::extractValue($matches[2].$matches[3], $fragment[$matches[1]]);
        }
        return $fragment[$fieldname];
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
        $text = file_get_contents($filename);
        if ($text === false) trigger_error('Error opening form '.$filename, E_USER_ERROR);
        foreach (self::parseFieldsFromText($text) as $field) {
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
    private static function parseTag($tagtext) {
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
     * Render the form
     */
    public function render() {
        echo '<form id="'.$this->form_id.'" method="post" class="platform_form">';
        echo '<input type="hidden" name="form_name" value="'.$this->form_id.'">';
        echo '<input type="hidden" name="form_action" value="submit">';
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
     * Set the base action of the form
     * @param type $action
     */
    public function setAction($action) {
        $this->action = $action;
    }
    
    /**
     * Add values to this form
     * @param array $values Values hashed by their field name
     */
    public function setValues($values) {
        if (! is_array($values)) return;
        foreach ($values as $fieldname => $value) {
            $formfield = $this->getFieldByName($fieldname);
            if ($formfield) {
                $formfield->setValue($value);
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