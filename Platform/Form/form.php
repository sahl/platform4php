<?php
namespace Platform;

class Form {
    
    private $formid = array();

    private $fields = array();
    
    private $validationfunctions = array();
    
    private $action = 'submit';
    
    public function __construct($formid, $filename = '') {
        $this->formid = $formid;
        if ($filename) $this->getFromFile ($filename);
    }
    
    public function addField($field) {
        if (! $field instanceof Field) trigger_error('Added non-field object to form', E_USER_ERROR);
        $this->fields[] = $field;
        $field->attachToForm($this);
    }
    
    public function addValidationFunction($function) {
        if (!is_callable($function)) trigger_error('Added invalid validation function to form', E_USER_ERROR);
        $this->validationfunctions[] = $function;
    }
    
    public function addHTML($html) {
        $this->addField(new FieldHTML($html));
    }
    
    public function getFieldByName($fieldname) {
        /* @var $field Field */
        foreach ($this->fields as $field) {
            if ($fieldname == $field->getName()) return $field;
        }
        return false;
    }
    
    public function getFromFile($filename) {
        $text = file_get_contents($filename);
        if ($text === false) trigger_error('Error opening form '.$filename, E_USER_ERROR);
        $this->parseFromText($text);
    }
    
    public function getId() {
        return $this->formid;
    }
    
    public function getValues() {
       $result = array();
       foreach ($this->fields as $field) {
            /* @var $field Field */
            if ($field instanceof FieldHTML) continue;
            $value = $field->getValue();
            if ($value !== null) $result[$field->getName()] = $value;
       }
       return $result;
    }
    
    public function isSubmitted() {
        return ($_POST['form_name'] == $this->formid);
    }
    
    public function parseFromText($text) {
        // Explode on tags
        $elements = explode('<', $text);
        // The first element is not a tag
        $this->addHTML(array_shift($elements));
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
                    $this->addField(new $class($label, $name, $tag['properties']));
                    $this->addHTML($tag['text']);
                } else {
                    $this->addHTML('<'.$element);
                }
            } else {
                $this->addHTML('<'.$element);
            }
        }
    }
    
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
    
    public function render() {
        echo '<form id="'.$this->formid.'" method="post" class="platform_form">';
        echo '<input type="hidden" name="form_name" value="'.$this->formid.'">';
        echo '<input type="hidden" name="form_action" value="submit">';
        echo '<input type="hidden" name="form_hiddenfields" value="">';
           
        foreach ($this->fields as $field) {
            /* @var $field Field */
            $field->render();
        }
        echo '</form>';
    }
    
    public function setAction($action) {
        $this->action = $action;
    }
    
    public function setValues($values) {
        if (! is_array($values)) return;
        foreach ($values as $fieldname => $value) {
            $formfield = $this->getFieldByName($fieldname);
            if ($formfield) {
                $formfield->setValue($value);
            }
        }
    }
    
    public function validate() {
        $result = true;
        
        // Determine hidden fields
        $hiddenfields = $_POST['form_hiddenfields'] ? explode(' ', $_POST['form_hiddenfields']) : array();
        
        foreach ($this->fields as $field) {
            /* @var $field Field */
            if ($field instanceof FieldHTML || in_array($field->getName(), $hiddenfields) && ! $field instanceof FieldHidden) continue;
            $result = $field->parse($_POST[$field->getName()]) && $result;
        }
        foreach ($this->validationfunctions as $validationfunction) {
            $result = $result && call_user_func($validationfunction, $this);
        }
        
        return $result;
    }
}