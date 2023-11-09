<?php
namespace Platform\Datarecord;
/**
 * Type class for HTML text
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=type_class
 */


class HTMLTextType extends BigTextType {
    
    public function getFormField(): \Platform\Form\Field {
        return \Platform\Form\TexteditorField::Field($this->title, $this->name);
    }

    public function getSQLFieldType() : string {
        return 'MEDIUMTEXT NOT NULL';
    }
    
}

