<?php
namespace Platform;

class FieldFile extends Field {
    
    public function __construct($label, $name, $options = array()) {
        parent::__construct($label, $name, $options);
        $this->classes[] = '';
    }
    
    public function parse($value) {
        $this->value = array(
            'mimetype' => $_POST[$this->getName().'__mimetype'],
            'status' => $_POST[$this->getName().'__status'],
            'originalfile' => $_POST[$this->getName().'__originalfile'],
            'tempfile' => $_POST[$this->getName().'__tempfile'],
            'fileid' => 0
        );
        return true;
    }
    
    public function renderInput() {
        $value = $this->getValue();
        if (! is_array($value)) $value = array();
        echo '<input type="hidden" name="'.$this->getName().'__mimetype" value="'.$value['mimetype'].'">';
        echo '<input type="hidden" name="'.$this->getName().'__status" value="'.$value['status'].'">';
        echo '<input type="hidden" name="'.$this->getName().'__originalfile" value="'.$value['originalfile'].'">';
        echo '<input type="hidden" name="'.$this->getName().'__tempfile" value="'.$value['tempfile'].'">';
        echo ' <iframe id="'.$this->getFieldIdForHTML().'" class="file_select_frame" src="/Platform/Field/php/file.php?formname='.$this->getFormId().'&fieldname='.$this->getName().'&currentfileid='.$value['fileid'].'&originalfile='.$value['originalfile'].'" frameborder=0 width=400 height=170 style="vertical-align: top;"></iframe>';    }
    
    public function setValue($value) {
        if (! is_array($value)) $this->value = array('fileid' => $value);
        else $this->value = $value;
    }
}