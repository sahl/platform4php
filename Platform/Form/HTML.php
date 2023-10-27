<?php
namespace Platform\Form;
/**
 * Field for adding HTML in a form
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=field_class
 */

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