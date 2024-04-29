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
    protected function getBaseFormField() : ?\Platform\Form\Field {
        $options = $this->getFormFieldOptions();
        $options['images_only'] = true;
        return \Platform\Form\FileField::Field($this->title, $this->name, $options);
    }
    
    /**
     * Validate if this is a valid value for fields of this type
     * @param mixed $value
     * @return mixed True if no problem or otherwise a string explaining the problem
     */
    public function validateValue($value) {
        $result = parent::validateValue($value);
        return $result;
    }
    
}