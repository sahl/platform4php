<?php
namespace Platform\Form;
/**
 * Field for an indexed combobox
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=field_class
 */

class IndexedComboboxField extends ComboboxField {
    
    protected static $component_class = 'platform_component_form_indexed_combobox_field';
    
    public function __construct() {
        parent::__construct();
        static::JSFile(\Platform\Utilities\Utilities::directoryToURL(__DIR__).'/js/ComboboxField.js');
        static::JSFile(\Platform\Utilities\Utilities::directoryToURL(__DIR__).'/js/IndexedComboboxField.js');
        $this->setMode(self::MODE_CALLBACK);
        $this->setStrict();
    }
    
    public function getValue() {
        if (is_array($this->value)) return (int)$this->value['id'];
        return 0;
    }
    
    public function handleIO(): array {
        switch ($_POST['event']) {
            case 'autocomplete':
                $result = [];
                foreach ($this->autoComplete($_POST['term']) as $id => $value) {
                    $result[] = ['real_id' => $id, 'value' => $value];
                }
                return $result;
            case 'resolve':
                return $this->resolveID($_POST['id']);
        }
        return parent::handleIO();
    }
    
    /**
     * Find the visual representation of a given ID. Can be overridden for better performance.
     * @param mixed $search_id
     * @return array Result array
     */
    public function resolveID($search_id) : array {
        $all_results = $this->autoComplete('');
        foreach ($all_results as $id => $value) {
            if ($search_id == $id) return ['status' => true, 'real_id' => $id, 'visual' => $value];
        }
        return ['status' => false];
    }    
    
    public function parse($value) : bool {
        $result = true;
        if (! is_array($value)) $value = array('id' => 0);
        if (! $value['id'] && $this->is_required) {
            $this->triggerError('This is a required field');
            $result = false;
        }
        $this->value = $value;
        return $result;
    }
    
    
    public function renderInput() {
         if (! is_array($this->value)) $this->value = array();
        $placeholder = trim($this->placeholder) ? ' placeholder="'.$this->placeholder.'"' : '';
        echo '<input type="hidden" name="'.$this->name.'[id]" value="'.$this->value['id'].'">';
        echo '<input id="'.$this->getFieldIdForHTML().'" style="'.$this->getFieldStyleString().'"'.$placeholder.' class="'.$this->getFieldClasses().'" type="text" data-realname="'.$this->name.'" name="'.$this->name.'[visual]" value="'.htmlentities($this->value['visual'], ENT_QUOTES).'"'.$this->additional_attributes.'>';
    }
    
    public function setValue($value) {
        if (! is_array($value)) {
            $this->value = array();
        } else {
            if (! isset($value['id']) || ! isset($value['visual'])) trigger_error('Invalid value fed to FieldIndexedCombobox', E_USER_ERROR);
            $this->value = $value;
        }
    }
    
}