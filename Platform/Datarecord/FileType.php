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
    protected $keep_file_on_delete = false;
    
    /**
     * Folder where to keep file
     * @var string
     */
    protected $folder = '';

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
        $valid_options = ['keep_file_on_delete', 'folder'];
        
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
    protected function getBaseFormField() : ?\Platform\Form\Field {
        return \Platform\Form\FileField::Field($this->title, $this->name, $this->getFormFieldOptions());
    }
    
    public function getFormValue($value): mixed {
        return (int)$value;
    }
    
    /**
     * Get the json store value for fields of this type
     * @param mixed $value
     * @param bool $include_binary_data If true, then include any binary data if available
     * @return mixed
     */
    public function getJSONValue($value, $include_binary_data = false) {
        if ($value === null) return null;
        $file = new \Platform\File\File();
        $file->loadForRead($value, false);
        if (! $file->isInDatabase()) return null;
        $result = ['filename' => $file->filename, 'mimetype' => $file->mimetype];
        if ($include_binary_data) $result['binary'] = base64_encode($file->getFileContent());
        return $result;
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
     * Get all the options of this type as an array.
     * @return array
     */
    public function getOptionsAsArray() : array {
        $result = parent::getOptionsAsArray();
        $valid_options = ['keep_file_on_delete', 'folder'];
        
        foreach ($valid_options as $option) {
            if ($this->$option != null) $result[$option] = $this->$option;
        }
        return $result;
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
        if ($value instanceof \Platform\File\File) return $value->file_id;
        elseif (is_array($value)) {
            if (! $value['action']) return $existing_value;
            if ($value['action'] == 'remove') {
                // The file was removed
                if (! $this->keep_file_on_delete) {
                    $file = new \Platform\File\File();
                    $file->loadForWrite((int)$existing_value, false);
                    if ($file->isInDatabase()) $file->delete();
                }
                return null;
            }
            // Check if we have an attached file object
            $file = new \Platform\File\File();
            if ($existing_value) $file->loadForWrite($existing_value, false);
            $file->filename = $value['filename'];
            $file->folder = $this->folder;
            $this->mimetype = $value['mimetype'];
            $folder = \Platform\File\File::getFullFolderPath('temp');
            if ($value['temp_file']) $file->attachFile($folder.$value['temp_file']);
            if ($value['binary']) {
                $binary = base64_decode($value['binary']);
                if ($binary !== false) $file->attachBinaryData($binary);
            }
            $file->save(false, true);
            return $file->file_id;
        }
        return null;
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
    
    /**
     * Validate if this is a valid value for fields of this type
     * @param mixed $value
     * @return mixed True if no problem or otherwise a string explaining the problem
     */
    public function validateValue($value) {
        if ($value === null || $value instanceof \Platform\File\File) return true;
        $result = static::arrayCheck($value, ['action'], ['filename', 'mimetype', 'binary', 'temp_file']);
        if ($result !== true) return $result;
        if (! in_array($value['action'], ['add', 'remove'])) return \Platform\Utilities\Translation::translateForUser('action property must be add or remove.');
        if (! $value['temp_file'] && ! $value['binary']) return \Platform\Utilities\Translation::translateForUser('No file data provided.');
        if ($value['binary'] && base64_decode($value['binary']) === false) return \Platform\Utilities\Translation::translateForUser('Binary data must be base64 encoded.');
        return true;
    }
}

