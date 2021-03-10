<?php
namespace Platform;

class FieldSubmit extends Field {
    
    public function __construct($label, $name, $options = array()) {
        Errorhandler::checkParams($label, 'string', $name, 'string', $options, 'array');
        parent::__construct($label, $name, $options);
        $this->classes[] = 'platform_submit';
        // This never holds a label
        $this->setLabelAlignment(self::LABEL_ALIGN_NONE);
    }
    
    public function renderInput() {
        echo '<input class="'.$this->getClassString().'" type="submit" value="'.$this->label.'" id="'.$this->getFieldIdForHTML().'"'.$this->additional_attributes.'>';
    }
}