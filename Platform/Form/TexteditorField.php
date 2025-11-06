<?php
namespace Platform\Form;
/**
 * Field for inputting HTML in a visual editor
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=field_class
 */

class TexteditorField extends TextField {
    
    public static $component_class = 'platform_component_texteditor_field';
    
    public function __construct() {
        parent::__construct();
        static::JSFile('https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote-lite.min.js');
        static::CSSFile('https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote-lite.min.css');
        static::JSFile(\Platform\Utilities\Utilities::directoryToURL(__DIR__).'/js/Field.js'); 
        static::JSFile(\Platform\Utilities\Utilities::directoryToURL(__DIR__).'/js/TexteditorField.js');
        $this->addFieldClass('texteditor');
    }    
    
    public function renderInput() {
        echo '<textarea data-fieldclass="'.$this->getFieldClass().'" class="'.$this->getFieldClasses().'" style="'.$this->getFieldStyleString().'" name="'.$this->name.'" id="'.$this->getFieldIdForHTML().'"'.$this->additional_attributes.'>';
        echo htmlentities($this->value, ENT_QUOTES);
        echo '</textarea>';
    }
}