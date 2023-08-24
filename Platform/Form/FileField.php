<?php
namespace Platform\Form;

use \Platform\File;

class FileField extends Field {
    
    public $images_only = false;
    
    protected static $component_class = 'platform_component_file_field';
    
    public function __construct() {
        parent::__construct();
        static::JSFile(\Platform\Utilities\Utilities::directoryToURL(__DIR__).'/js/Field.js'); 
        static::JSFile(\Platform\Utilities\Utilities::directoryToURL(__DIR__).'/js/FileField.js'); 
    }
    
    public static function Field(string $label, string $name, array $options = []): static {
        $images_only = false;
        if ($options['images_only']) {
            $images_only = true;
            unset($options['images_only']);
        }
        $field = parent::Field($label, $name, $options);
        $field->images_only = $images_only;
        return $field;
    }
    
    public function parse($value) : bool {
        $this->value = array(
            'mimetype' => $value['mimetype'],
            'status' => $value['status'],
            'original_file' => $value['original_file'],
            'temp_file' => $value['temp_file'],
            'file_id' => 0
        );
        if ($value['status'] == 'changed') {
            $file = new File();
            $folder = File::getFullFolderPath('temp');
            $file->attachFile($folder.$value['temp_file']);
            if ($this->images_only && ! $file->isImage()) {
                $this->triggerError('This file must be an image.');
                return false;
            }
        }
        return true;
    }
    
    public function renderInput() {
        $value = $this->getValue();
        if (! is_array($value)) $value = array();
        echo '<input type="hidden" name="'.$this->getName().'[mimetype]" value="'.$value['mimetype'].'">';
        echo '<input type="hidden" name="'.$this->getName().'[status]" value="'.$value['status'].'">';
        echo '<input type="hidden" name="'.$this->getName().'[original_file]" value="'.$value['original_file'].'">';
        echo '<input type="hidden" name="'.$this->getName().'[temp_file]" value="'.$value['temp_file'].'">';
        echo '<iframe data-fieldclass="'.$this->getFieldClass().'" class="'.$this->getFieldClasses().'" id="'.$this->getFieldIdForHTML().'" style="max-width: '.$this->field_width.';" data-name="'.$this->getName().'" class="platform_file_input_frame" src="/Platform/Form/php/file.php?form_name='.$this->getFormId().'&field_name='.$this->getName().'&file_id='.$value['file_id'].'&original_file='.$value['originalfile'].'" frameborder=0 height=36 style="vertical-align: top;"></iframe>';
    }
    
    public function setValue($value) {
        if (! is_array($value)) $this->value = array('file_id' => $value);
        else $this->value = $value;
    }
}