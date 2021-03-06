<?php
namespace Platform;

class FieldCombobox extends Field {
    
    protected $datasource = false;
    
    public function __construct($label, $name, $options = array()) {
        Errorhandler::checkParams($label, 'string', $name, 'string', $options, 'array');
        $this->classes[] = 'platform_combobox';
        if ($options['datasource']) {
            $this->setDatasource($options['datasource']);
            unset($options['datasource']);
        }
        parent::__construct($label, $name, $options);
    }
    
    public function renderInput() {
        echo '<input class="'.$this->getClassString().'" type="text" id="'.$this->getFieldIdForHTML().'" name="'.$this->name.'" value="'.htmlentities($this->value, ENT_QUOTES).'"'.$this->additional_attributes.' data-source="'.$this->datasource.'">';
    }
    
    public function setDatasource($datasource) {
        $this->datasource = $datasource;
    }
}