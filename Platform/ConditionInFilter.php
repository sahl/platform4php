<?php
namespace Platform;

class ConditionInFilter extends Condition {
    
    private $other_filter = false;
    
    public function __construct(string $fieldname, Filter $filter) {
        $this->fieldname = $fieldname;
        $this->other_filter = $filter;
    }
    
    /**
     * Get this condition expressed as an array.
     * @return array
     */
    public function getAsArray() : array {
        return array(
            'type' => 'InFilter',
            'filter' => $this->other_filter->getAsArray()
        );
    }
    
    public function getSQLFragment() : string {
        if ($this->manual_match) return 'TRUE';
        $class_of_field = $this->other_filter->getBaseObject()->getFieldDefinition($this->fieldname)['foreign_class'];
        $class_of_filter = $this->filter->getBaseClassName();
        
        if ($class_of_field != $class_of_filter) trigger_error('Class '.$class_of_field.' is not compatible with '.$class_of_filter.' in InFilter condition!', E_USER_ERROR);
        
        // Check if we can do this in SQL or we need to manually run the other filter
        if ($this->other_filter->willSearchMetadata()) {
            // Slow
            $datacollection = $this->other_filter->execute();
            $values = array_unique($datacollection->getAllRawValues($this->fieldname));
            return count($values) ? 
                $this->filter->getBaseObject()->getKeyField().' IN ('.implode(',',$values).')' : 
                'FALSE';
        } else {
            // Fast
            return $this->filter->getBaseObject()->getKeyField().' IN (SELECT DISTINCT '.$this->fieldname.' FROM '.$this->other_filter->getBaseObject()->getDatabaseTable().$this->other_filter->getSQLWhere().')';
        }
    }
    
    private $filter_result = false;
    
    public function match(Datarecord $object, bool $force_manual = false) : bool {
        if (! $force_manual && ! $this->manual_match) return true;
        // Manual match
        if (! $this->filter_result) {
            $this->filter_result = $this->other_filter->execute()->getAllRawValues($this->fieldname);
        }
        return in_array($object->getRawValue($this->filter->getBaseObject()->getKeyField()), $this->filter_result);
    }
    
    public function validate() {
        // Validation
        $definition = $this->other_filter->getBaseObject()->getFieldDefinition($this->fieldname);
        if (! $definition) return array('Invalid field '.$this->fieldname.' for infilter condition');
        if ($definition['store_in_database'] === false) return array('Field '.$this->fieldname.' is not stored in database for infilter condition');
        if (! in_array(
                $definition['fieldtype'], 
                array(Datarecord::FIELDTYPE_FILE, Datarecord::FIELDTYPE_IMAGE, Datarecord::FIELDTYPE_REFERENCE_SINGLE)
            ))
            return array('Field '.$this->field.' does not work with infilter condition');
        
        // Determine SQL use
        $this->setManualMatch($definition['store_in_metadata'] ? true : false);
        return true;
    }
}