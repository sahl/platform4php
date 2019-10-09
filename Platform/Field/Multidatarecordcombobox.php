<?php
namespace Platform;

class FieldMultidatarecordcombobox extends FieldMultiplier {
    
    public function __construct($label, $name, $options = array()) {
        $datarecordcombobox = new FieldDatarecordcombobox('', 'innercombobox', array('class' => $options['class']));
        $datarecordcombobox->setContainerClasses(array());
        unset($options['class']);
        parent::__construct($label, $name, $options);
        $this->addFields($datarecordcombobox);
    }
    
    public function parse($values) {
        $result = parent::parse($values);
        if ($result && $this->is_required && ! count($this->getValue())) {
            $this->triggerError('This is a required field');
            $result = false;
        }
        return $result;
    }
    
    public function getValue() {
        $values = parent::getValue();
        $real_results = array();
        foreach ($values as $value) {
            $real_results[] = $value['innercombobox'];
        }
        return $real_results;
    }
    
    public function setValue($values) {
        if (! is_array($values)) $values = array();
        $real_values = array();
        foreach ($values as $value) {
            $real_values[] = array('innercombobox' => $value);
        }
        parent::setValue($real_values);
    }
}