<?php
namespace Platform\Datarecord;
/**
 * Type class for reference to image
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=type_class
 */


class ImageType extends FileType {
    
    /**
     * Get a form field for editing fields of this type
     * @return \Platform\Form\Field
     */
    public function getFormField() : \Platform\Form\Field {
        return \Platform\Form\FileField::Field($this->title, $this->name);
    }
    
}