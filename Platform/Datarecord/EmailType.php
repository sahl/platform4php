<?php
namespace Platform\Datarecord;
/**
 * Type class for emails
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=type_class
 */


class EmailType extends TextType {

    public function getSQLFieldType() : string {
        return 'VARCHAR(255) NOT NULL';
    }
    
    public function getFullValue($value, Collection &$collection = null): string {
        return $value ? '<a href="mailto:'.$value.'">'.htmlentities($value).'</a>' : '';
    }
    
    protected function getBaseFormField(): \Platform\Form\Field {
        return \Platform\Form\EmailField::Field($this->title, $this->name, $this->getFormFieldOptions());
    }
}

