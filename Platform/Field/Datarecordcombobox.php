<?php
namespace Platform;

class FieldDatarecordcombobox extends FieldCombobox {
    
    private $connected_class = false;
    
    public function __construct($label, $name, $options = array()) {
        $this->classes[] = 'w3-input platform_combobox platform_datarecord_combobox';
        if ($options['class']) {
            $this->connected_class = $options['class'];
            $this->setDatasource('/Platform/Field/php/io_combobox.php?class='.$this->connected_class);
            unset($options['class']);
        }
        parent::__construct($label, $name, $options);
    }
    
    public function getValue() {
        if (is_array($this->value)) return $this->value['id'];
        return 0;
    }
    
    public function parse($value) {
        $result = true;
        if (! $value['id']) {
            if (trim($value['visual'])) {
                // Try to resolve visual value to an ID
                $datarecordcollection = $this->connected_class::findByKeywords($value['visual']);
                if ($datarecordcollection->getCount()) {
                    $object = $datarecordcollection->get(0);
                    $value['id'] = $object->getRawValue($this->connected_class::getKeyField());
                }
                // Check for mismatch value
                if (! $value['id']) {
                    $this->triggerError('This is not a valid value for this field');
                    $result = false;
                } else {
                    $value['visual'] = strip_tags($object->getTitle());
                }
            } else {
                // No content. Check if required
                if ($this->is_required) {
                    $this->triggerError('This is a required field');
                    $result = false;
                }
            }
        } else {
            // Check for valid ID
            $object = new $this->connected_class();
            $object->loadForRead($value['id']);
            if (! $object->isInDatabase()) {
                $this->triggerError('This is not a valid value for this field');
                $result = false;
            }
            else $value['visual'] = strip_tags($object->getTitle());
        }
        $this->setValue($value);
        return $result;
    }
    
    public function renderInput() {
        if (! is_array($this->value)) $this->value = array();
        echo '<input type="hidden" name="'.$this->name.'[id]" id="'.$this->getFieldIdForHTML().'" value="'.$this->value['id'].'">';
        echo '<input class="'.$this->getClassString().'" type="text" name="'.$this->name.'[visual]" value="'.$this->value['visual'].'"'.$this->additional_attributes.' data-source="'.$this->datasource.'">';
    }
    
    public function setValue($value) {
        if (! is_array($value)) {
            if (! $value) {
                $this->value = array();
                return;
            }
            $object = new $this->connected_class();
            $object->loadForRead($value);
            $visual_value = strip_tags($object->getTitle());
            $this->value = array('id' => $value, 'visual' => $visual_value);
        } else {
            $this->value = $value;
        }
    }
}