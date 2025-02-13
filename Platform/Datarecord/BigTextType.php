<?php
namespace Platform\Datarecord;
/**
 * Type class for big text
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=type_class
 */


class BigTextType extends TextType {
    
    protected $string_length = (1<<24)-1;
    
    protected $default_value = '';
    
    public function getLogValue($value) : string {
        return \Platform\Utilities\Utilities::condenseLongText((string)$value);
    }
    
    public function getFullValue($value, Collection &$collection = null): string {
        return str_replace("\n", "<br>", parent::getFullValue($value, $collection));
    }
    
    public function getBaseFormField(): ?\Platform\Form\Field {
        return \Platform\Form\TextareaField::Field($this->title, $this->name, $this->getFormFieldOptions());
    }

    public function getSQLFieldType() : string {
        return 'MEDIUMTEXT NOT NULL';
    }
    
}

