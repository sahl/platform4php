<?php
namespace Platform\Datarecord;
/**
 * Type class for HTML text
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=type_class
 */


class HTMLTextType extends BigTextType {
    
    protected function getBaseFormField() : ?\Platform\Form\Field {
        return \Platform\Form\TexteditorField::Field($this->title, $this->name, $this->getFormFieldOptions());
    }

    public function getSQLFieldType() : string {
        return 'MEDIUMTEXT NOT NULL';
    }
    
}

