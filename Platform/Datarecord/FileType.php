<?php
namespace Platform\Datarecord;
/**
 * Type class for reference to file
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=type_class
 */


class FileType extends SingleReferenceType {
    
    /**
     * Indicate if we should keep the file when the reference is deleted
     * @var bool
     */
    protected static $keep_file_on_delete = false;

    /**
     * Name of foreign class pointed to by this field
     * @var string
     */
    protected $foreign_class = null;
    
    /**
     * Construct a field of this type
     * @param string $name Field name
     * @param string $title Field title
     * @param type $options Field options
     */
    public function __construct(string $name, string $title = '', array $options = []) {
        $valid_options = ['keep_file_on_delete'];
        foreach ($valid_options as $valid_option) {
            if ($options[$valid_option]) {
                $this->$valid_option = $options[$valid_option];
                unset($options[$valid_option]);
            }
        }
        $options['foreign_class'] = 'Platform\File\File';
        parent::__construct($name, $title, $options);
    }
    
    /**
     * Get a form field for editing fields of this type
     * @return \Platform\Form\Field
     */
    public function getFormField() : ?\Platform\Form\Field {
        if ($this->isReadonly() || $this->isInvisible()) return null;
        return \Platform\Form\FileField::Field($this->title, $this->name, $this->getFormFieldOptions());
    }
    
    public function getFormValue($value): mixed {
        return (int)$value;
    }
    
    /**
     * Get the value for logging fields of this type
     * @param mixed $value
     * @return string
     */
    public function getLogValue($value) : string {
        if ($value === null) return 'NULL';
        return 'FILE#'.$value;
    }
    
    /**
     * Do an integrity check of this field
     * @return array
     */
    public function integrityCheck() : array {
        return [];
    }
    
    /**
     * Parse a value of this type
     * @param type $value
     * @return type
     */
    public function parseValue($value, $existing_value = null) {
        if ($value instanceof \Platform\File\File) $value = $value->getKeyValue();
        return $value;
    }
    
    /**
     * Remove a reference to the given object from the value (if present)
     * @param mixed $value
     * @param Datarecord $object
     * @return mixed
     */
    public function removeReferenceToObject($value, Datarecord $object) {
        if ($object instanceof $this->foreign_class && $object->getKeyValue() == $value) return null;
        return $value;
    }
    
    /**
     * Get SQL sort or return false if we can't sort by SQL
     * @param bool $descending True if we need descending sort
     * @return string|bool Sort string or false if we can't sort.
     */
    public function getSQLSort(bool $descending = false) {
        return false;
    }
}

