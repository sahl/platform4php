<?php
namespace Platform;

class FieldFile extends Field {
    
    public function __construct($label, $name, $options = array()) {
        parent::__construct($label, $name, $options);
        $this->classes[] = '';
    }
    
    public function parse($value) {
        $this->value = array(
            'mimetype' => $value['mimetype'],
            'status' => $value['status'],
            'original_file' => $value['original_file'],
            'temp_file' => $value['temp_file'],
            'file_id' => 0
        );
        return true;
    }
    
    public function renderInput() {
        $value = $this->getValue();
        if (! is_array($value)) $value = array();
        echo '<input type="hidden" name="'.$this->getName().'[mimetype]" value="'.$value['mimetype'].'">';
        echo '<input type="hidden" name="'.$this->getName().'[status]" value="'.$value['status'].'">';
        echo '<input type="hidden" name="'.$this->getName().'[original_file]" value="'.$value['original_file'].'">';
        echo '<input type="hidden" name="'.$this->getName().'[temp_file]" value="'.$value['temp_file'].'">';
        echo '<br><iframe id="'.$this->getFieldIdForHTML().'" data-name="'.$this->getName().'" class="'.Design::getClass('file_input_frame', 'file_select_frame').'" src="/Platform/Field/php/file.php?form_name='.$this->getFormId().'&field_name='.$this->getName().'&file_id='.$value['file_id'].'&original_file='.$value['originalfile'].'" frameborder=0 width=100% height=50 style="vertical-align: top;"></iframe>';
    }
    
    public function setValue($value) {
        if (! is_array($value)) $this->value = array('file_id' => $value);
        else $this->value = $value;
    }
}