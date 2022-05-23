<?php
namespace Platform\Form;

class ComboboxField extends Field {
    
    protected $datasource = false;
    
    public function __construct(string $label, string $name, array $options = array()) {
        $this->classes[] = 'platform_combobox';
        if ($options['datasource']) {
            $this->setDatasource($options['datasource']);
            unset($options['datasource']);
        }
        parent::__construct($label, $name, $options);
    }
    
    public function renderInput() {
        echo '<input class="'.$this->getClassString().'" type="text" style="max-width: '.$this->field_width.'px;" id="'.$this->getFieldIdForHTML().'" name="'.$this->name.'" value="'.htmlentities($this->value, ENT_QUOTES).'"'.$this->additional_attributes.' data-source="'.$this->datasource.'">';
    }
    
    public function setDatasource(string $datasource) {
        $this->datasource = $datasource;
    }
}