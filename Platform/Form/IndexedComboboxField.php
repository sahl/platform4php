<?php
namespace Platform\Form;
/**
 * Field for an indexed combobox
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=field_class
 */

class IndexedComboboxField extends ComboboxField {
    
    protected $datasource = false;
    
    public static function Field(string $label, string $name, array $options = array()) {
        $field = parent::Field($label, $name, $options);
        $field->addClass('platform_indexed_combobox');
        return $field;
    }
    
    public function getValue() {
        if (is_array($this->value)) return (int)$this->value['id'];
        return 0;
    }
    
    public function parse($value) : bool {
        $result = true;
        if (! is_array($value)) $value = array('id' => 0);
        if (! $value['id'] && $this->is_required) {
            $this->triggerError('This is a required field');
            $result = false;
        }
        $this->setValue($value);
        return $result;
    }
    
    
    public function renderInput() {
         if (! is_array($this->value)) $this->value = array();
        $placeholder = trim($this->placeholder) ? ' placeholder="'.$this->placeholder.'"' : '';
        echo '<input type="hidden" name="'.$this->name.'[id]" value="'.$this->value['id'].'">';
        echo '<input data-fieldclass="'.$this->getFieldClass().'" id="'.$this->getFieldIdForHTML().'" style="max-width: '.$this->field_width.';"'.$placeholder.' class="'.$this->getFieldClasses().'" type="text" data-realname="'.$this->name.'" name="'.$this->name.'[visual]" value="'.htmlentities($this->value['visual'], ENT_QUOTES).'"'.$this->additional_attributes.' data-source="'.$this->datasource.'">';
    }
    
    public function setValue($value) {
        if (! is_array($value)) {
            $this->value = array();
        } else {
            if (! isset($value['id']) && ! isset($value['visual'])) trigger_error('Invalid value fed to FieldIndexedCombobox', E_USER_ERROR);
            $this->value = $value;
        }
    }
    
}