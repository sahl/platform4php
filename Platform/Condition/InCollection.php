<?php
namespace Platform;

class ConditionInCollection extends Condition {
    
    private $collection = false;
    
    public function __construct($fieldname, $collection) {
        Errorhandler::checkParams($fieldname, 'string', $collection, '\\Platform\\Collection');
        $this->fieldname = $fieldname;
        $this->collection = $collection;
    }
    
    /**
     * Get this condition expressed as an array.
     * @return array
     */
    public function getAsArray() {
        trigger_error('Condition inCollection cannot be saved as an array.', E_USER_ERROR);
    }

    public function getSQLFragment() {
        if ($this->manual_match) return 'TRUE';
        // If collection is empty this can never match.
        if ($this->collection->getCount() == 0) return 'FALSE';
        $remote_class = $this->collection->getCollectionType();
        if (!class_exists($remote_class)) trigger_error('Class '.$remote_class.' does not exists!', E_USER_ERROR);
        $class_of_field = $remote_class::getFieldDefinition($this->fieldname)['foreign_class'];
        $class_of_filter = $this->filter->getBaseClassName();
        
        if ($class_of_field != $class_of_filter) trigger_error('Class '.$class_of_field.' is not compatible with '.$class_of_filter.' in InCollection condition!', E_USER_ERROR);
        
        if (! $this->collection->getCount()) return 'FALSE';
        
        return $this->filter->getBaseObject()->getKeyField().' IN ('.implode(',', $this->collection->getAllRawValues($this->fieldname)).')';
    }
    
    private $collection_content = false;
    
    public function match($object) {
        if (! $this->manual_match) return true;
        if ($this->collection->getCount() == 0) return false;
        if (! $this->collection_content) {
            $this->collection_content = $this->collection->getAllRawValues($this->fieldname);
        }
        return in_array($object->getRawValue($this->filter->getBaseObject()->getKeyField()), $this->collection_content);
    }
    
    public function validate() {
        // Validation
        return true;
    }
    
}