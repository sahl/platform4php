<?php
namespace Platform\Datarecord;
/**
 * Type class for passwords
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=type_class
 */


class PasswordType extends TextType {

    public function getSQLFieldType() : string {
        return 'VARCHAR(256) NOT NULL';
    }
    
    public function getFullValue($value, Collection &$collection = null): string {
        return $this->getTextValue($value);
    }
    
    public function getTextValue($value, Collection &$collection = null): string {
        return $value ? '********' : '';
    }
    
    public function getFormField(): \Platform\Form\Field {
        return \Platform\Form\PasswordField::Field($this->title, $this->name);
    }
    
}

