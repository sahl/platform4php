<?php
namespace Platform;

class FieldCombobox extends Field {
    
    protected $datasource = false;
    
    public function __construct($label, $name, $options = array()) {
        $this->classes[] = 'w3-input platform_combobox';
        if ($options['datasource']) {
            $this->setDatasource($options['datasource']);
            unset($options['datasource']);
        }
        parent::__construct($label, $name, $options);
    }
    
    public function renderInput() {
        echo '<input class="'.$this->getClassString().'" type="text" id="'.$this->getFieldIdForHTML().'" name="'.$this->name.'" value="'.$this->value.'"'.$this->additional_attributes.' data-source="'.$this->datasource.'">';
    }
    
    public function setDatasource($datasource) {
        $this->datasource = $datasource;
    }
}