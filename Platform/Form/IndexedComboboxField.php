<?php
namespace Platform\Form;

class IndexedComboboxField extends ComboboxField {
    
    protected $datasource = false;
    
    public function __construct(string $label, string $name, array $options = array()) {
        $this->classes[] = 'platform_indexed_combobox';
        parent::__construct($label, $name, $options);
    }
    
    public function getValue() {
        if (is_array($this->value)) return $this->value['id'];
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
        echo '<input type="hidden" name="'.$this->name.'[id]" value="'.$this->value['id'].'">';
        echo '<input id="'.$this->getFieldIdForHTML().'" style="max-width: '.$this->field_width.';" class="'.$this->getClassString().'" type="text" data-realname="'.$this->name.'" name="'.$this->name.'[visual]" value="'.$this->value['visual'].'"'.$this->additional_attributes.' data-source="'.$this->datasource.'">';
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