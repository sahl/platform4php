<?php
namespace Platform\Datarecord;
/**
 * Type class for primary key
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=type_class
 */


class KeyType extends IntegerType {

    public function __construct(string $name, string $title = '', array $options = []) {
        parent::__construct($name, $title, $options);
        $this->form_visibility = self::FORM_HIDDEN;
        $this->setPrimaryKey();
    }
    
    /**
     * Get a form field for editing fields of this type
     * @return \Platform\Form\Field
     */
    public function getBaseFormField() : ?\Platform\Form\Field {
        return \Platform\Form\HiddenField::Field($this->title, $this->name, $this->getFormFieldOptions());
    }
    
}

