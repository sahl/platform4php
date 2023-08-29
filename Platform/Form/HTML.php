<?php
namespace Platform\Form;

class HTML extends Field {
    
    public static $fieldcounter = 0;
    
    public static function Field(string $label, string $name, array $options = []): Field {
        if ($name == '') $name = 'html_field'.(static::$fieldcounter++);
        return parent::Field($label, $name, $options);
    }
    
    public static function HTML(string $html) {
        $field = static::Field('', '');
        $field->value = $html;
        return $field;
    }
    
    public function parse($value) : bool {
        return true;
    }
    
    public function render() {
        echo $this->value;
    }
}