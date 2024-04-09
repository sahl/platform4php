<?php
namespace Platform\Form;
/**
 * Field for a combobox which is linked to a Datarecord object and can only select items of that type.
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=field_class
 */

class DatarecordcomboboxField extends IndexedComboboxField {
    
    protected $filter = null;
    
    public function __construct() {
        parent::__construct();
        $this->addPropertyMap([
            'connected_class' => false
        ]);
    }
    
    public static function Field(string $label, string $name, array $options = array()) {
        $field = parent::Field($label, $name, $options);
        $field->addClass('platform_datarecord_combobox');
        if ($options['datarecord_class']) {
            $field->connected_class = $options['datarecord_class'];
            $field->setDatasource('/Platform/Form/php/io_combobox.php?class='.$field->connected_class);
            unset($options['datarecord_class']);
        }
        return $field;
    }
    
    public function handleIO(): array {
        if ($_POST['event'] == 'resolve') {
            $output = ['visual' => ''];
            if (class_exists($this->connected_class)) {
                $object = new $this->connected_class();
                $object->loadForRead($_POST['id'], false);
                if ($object->isInDatabase() && $object->canAccess()) $output = ['visual' => strip_tags($object->getTitle())];
            }
            return $output;
        }
        if ($_POST['event'] == 'autocomplete') {
            if (!class_exists($this->connected_class)) { $output = array(); }
            else {
                if ($_POST['filter']) $filter = \Platform\Filter::getFilterFromJSON($_POST['filter']);
                else $filter = null;
                $output = $this->connected_class::findByKeywords($_POST['term'], 'autocomplete', $filter);
            }
            return ['callback_options' => $output];
            
        }
        return parent::handleIO();
    }
    
    public function parse($value) : bool {
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
    
    /**
     * Attach a filter to this datarecordcombobox
     * @param \Platform\Filter\Filter $filter
     */
    public function setFilter(\Platform\Filter\Filter $filter) {
        $this->filter = $filter;
        $this->additional_attributes .= ' data-filter="'. htmlentities($filter->getAsJSON()).'"';
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