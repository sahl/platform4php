<?php
namespace Platform\Form;
/**
 * Field for a combobox which is linked to a Datarecord object and can only select items of that type.
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=field_class
 */

class DatarecordComboboxField extends IndexedComboboxField {
    
    protected $filter = null;
    
    public function __construct() {
        parent::__construct();
        $this->addPropertyMap([
            'connected_class' => false,
            'filter' => false
        ]);
    }
    
    public static function Field(string $label, string $name, array $options = array()) {
        $options['reserved_options'] = ['datarecord_class'];
        $field = parent::Field($label, $name, $options);
        $field->addClass('platform_datarecord_combobox');
        if ($options['datarecord_class']) {
            $field->connected_class = $options['datarecord_class'];
            unset($options['datarecord_class']);
        }
        return $field;
    }
    
    public function autoComplete(string $term): array {
        if (!class_exists($this->connected_class)) return [];
        $filter = $this->filter ? \Platform\Filter::getFilterFromJSON($this->filter) : null;
        return $this->connected_class::findByKeywords($term, 'autocomplete', $filter);
    }
    
    public function handleIO(): array {
        switch ($_POST['event']) {
            case 'autocomplete':
                return $this->autoComplete($_POST['term']);
            case 'lookup':
                $results = $this->autoComplete($_POST['term']);
                if (count($results)) return ['status' => 1, 'id' => $results[0]['real_id']];
                return ['status' => 0];
                
        }
        return parent::handleIO();
    }
    
    
    public function resolveID($search_id): array {
        if (class_exists($this->connected_class)) {
            $object = new $this->connected_class();
            $object->loadForRead($search_id, false);
            if ($object->isInDatabase() && $object->canAccess()) return ['status' => true, 'id' => $object->getKeyValue(), 'visual' => $object->getTextTitle()];
        }
        return ['status' => false];
    }
    
    public function parse($value) : bool {
        $result = true;
        $previous_id = $this->value['id'];
        if (! $value['id']) {
            if (trim($value['visual'])) {
                // Try to resolve visual value to an ID
                $datarecordcollection = $this->connected_class::findByKeywords($value['visual'], 'Collection', null, false);
                if ($datarecordcollection->getCount()) {
                    foreach ($datarecordcollection as $record) {
                        if ($record->canAccess() || $this->always_allow_selected_value && $record->getKeyValue() == $previous_id) {
                            $object = $record;
                            $value['id'] = $record->getKeyValue();
                            break;
                        }
                    }
                }
                // Check for mismatch value
                if (! $value['id']) {
                    $this->triggerError('This is not a valid value for this field');
                    $result = false;
                } else {
                    $value['visual'] = $object->getTextTitle();
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
            if (! $object->isInDatabase() || (! $object->canAccess() && (! $this->always_allow_selected_value || $value['id'] != $previous_id))) {
                $this->triggerError('This is not a valid value for this field');
                $result = false;
            }
            else $value['visual'] = $object->getTextTitle();
        }
        $this->setValue($value);
        return $result;
    }
    
    public function setValue($value) {
        if (is_numeric($value)) $this->value = $value;
        else parent::setValue($value);
    }
    
    /**
     * Attach a filter to this datarecordcombobox
     * @param \Platform\Filter\Filter $filter
     */
    public function setFilter(\Platform\Filter\Filter $filter) {
        $this->filter = $filter->getAsJSON();
    }
    
    public function prepareData() {
        parent::prepareData();
        if (! is_array($this->value)) {
            if (! $this->value) {
                $this->value = [];
                return;
            }
            $object = new $this->connected_class();
            $object->loadForRead($this->value);
            $visual_value = $object->getTextTitle();
            $this->value = array('id' => $this->value, 'visual' => $visual_value);
        }
    }
}