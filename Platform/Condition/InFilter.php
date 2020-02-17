<?php
namespace Platform;

class ConditionInFilter extends Condition {
    
    private $other_filter = false;
    
    public function __construct($fieldname, $filter) {
        $this->fieldname = $fieldname;
        $this->other_filter = $filter;
    }
    
    public function getSQLFragment() {
        $class_of_field = $this->other_filter->getBaseObject()->getFieldDefinition($this->fieldname)['foreign_class'];
        $class_of_filter = $this->filter->getBaseClassName();
        
        if ($class_of_field != $class_of_filter) trigger_error('Class '.$class_of_field.' is not compatible with '.$class_of_filter.' in InFilter condition!', E_USER_ERROR);
        
        return $this->filter->getBaseObject()->getKeyField().' IN (SELECT DISTINCT '.$this->fieldname.' FROM '.$this->other_filter->getBaseObject()->getDatabaseTable().$this->other_filter->getSQLWhere().')';

    }
    
    /**
     * Get this condition expressed as an array.
     * @return array
     */
    public function toArray() {
        return array(
            'type' => 'InFilter',
            'filter' => $this->other_filter
        );
    }
}