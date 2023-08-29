<?php
namespace Platform\Form;

class ComboboxField extends Field {
    
    protected $datasource = false;
    
    protected static $component_class = 'platform_component_form_comboboxfield';
    
    public function __construct() {
        parent::__construct();
        static::JSFile(\Platform\Utilities\Utilities::directoryToURL(__DIR__).'/js/Combobox.js');
    }
    
    public static function Field(string $label, string $name, array $options = array()) {
        $field = parent::Field($label, $name, $options);
        $field->addClass('platform_combobox');
        if ($options['datasource']) {
            $this->setDatasource($options['datasource']);
            unset($options['datasource']);
        }
        return $field;
    }
    
    public function renderInput() {
        echo '<input data-fieldclass="'.$this->getFieldClass().'" class="'.$this->getFieldClasses().'" type="text" style="max-width: '.$this->field_width.';" id="'.$this->getFieldIdForHTML().'" name="'.$this->name.'" value="'.htmlentities($this->value, ENT_QUOTES).'"'.$this->additional_attributes.' data-source="'.$this->datasource.'">';
    }
    
    public function setDatasource(string $datasource) {
        $this->datasource = $datasource;
    }
}