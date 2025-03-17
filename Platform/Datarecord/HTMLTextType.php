<?php
namespace Platform\Datarecord;
/**
 * Type class for HTML text
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=type_class
 */


class HTMLTextType extends BigTextType {
    
    protected $default_value = '';
    
    public function getBaseFormField() : ?\Platform\Form\Field {
        return \Platform\Form\TexteditorField::Field($this->title, $this->name, $this->getFormFieldOptions());
    }
    
    public function getFullValue($value, Collection &$collection = null): string {
        return (string)$value;
    }
    
    public function getTextValue($value, Collection &$collection = null): string {
        return (string)strip_tags($value);
    }

    public function getSQLFieldType() : string {
        return 'MEDIUMTEXT NOT NULL';
    }
    
}

