<?php
namespace Platform;

class FilterCondition {
    
    /**
     * Name of field to filter on
     * @var string 
     */
    public $fieldname = '';
    
    /**
     * Value to filter against
     * @var mixed 
     */
    public $value = '';
    
    /**
     * The filter containing this condition
     * @var Filter 
     */
    public $filter = null;
    
    /**
     * Attach a filter to this condition
     * @param \Platform\Filter $filter
     */
    public function attachFilter($filter) {
        if (! $filter instanceof Filter) trigger_error('Can only attach filter', E_USER_ERROR);
        $this->filter = $filter;
    }
    
    /**
     * Get a SQL field value for this condition
     * @global array $platform_configuration
     * @return string SQL statement
     */
    protected function getSQLFieldValue($value) {
        global $platform_configuration;
        
        if (! $this->filter instanceof Filter) trigger_error('No filter attached', E_USER_ERROR);
        if (! $this->fieldname) return '';
        
        $field_definition = $this->filter->getBaseObject()->getFieldDefinition($this->fieldname);
        
        // Fail on metadata fields or fields not in database
        if ($field_definition['store_in_database'] === false || $field_definition['store_in_metadata'] === true) trigger_error('Can only filter on fields in database (yet!). And '.$this->fieldname.' is not in the database!', E_USER_ERROR);
        
        // We need to do something extra for password fields
        if ($field_definition['fieldtype'] == Datarecord::FIELDTYPE_PASSWORD) $value = md5($value.$platform_configuration['password_salt']);
        
        // Handle timestamps
        if ($value instanceof Timestamp) $value = $value->getTime();
        
        return $this->filter->getBaseObject()->getFieldForDatabase($this->fieldname, $value);
    }
    
    /**
     * Override to get a SQL Fragment representing the condition
     * @return string
     */
    public function getSQLFragment() {
        return '';
    }
    
}