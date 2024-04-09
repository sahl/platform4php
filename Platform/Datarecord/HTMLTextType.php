<?php
namespace Platform\Datarecord;
/**
 * Type class for HTML text
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=type_class
 */


class HTMLTextType extends BigTextType {
    
    public function getFormField(): \Platform\Form\Field {
        if ($this->isReadonly() || $this->isInvisible()) return null;
        return \Platform\Form\TexteditorField::Field($this->title, $this->name, $this->getFormFieldOptions());
    }

    public function getSQLFieldType() : string {
        return 'MEDIUMTEXT NOT NULL';
    }
    
}

