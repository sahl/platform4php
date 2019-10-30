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
     * Decode a condition from an array.
     * @param array $array Array earlier packed with toArray()
     * @return \Platform\FilterConditionMatch|\Platform\FilterConditionLike|\Platform\FilterConditionNOT|\Platform\FilterConditionLesserEqual|\Platform\FilterConditionOneOf|\Platform\FilterConditionGreater|\Platform\FilterConditionGreaterEqual|\Platform\FilterConditionLesser|\Platform\FilterConditionOR|\Platform\FilterConditionAND
     */
    public static function getConditionFromArray($array) {
        switch ($array['type']) {
            case 'AND':
                return new FilterConditionAND(self::getConditionFromArray($array['condition1']), self::getConditionFromArray($array['condition2']));
            case 'NOT':
                return new FilterConditionNOT(self::getConditionFromArray($array['condition']));
            case 'OR':
                return new FilterConditionOR(self::getConditionFromArray($array['condition1']), self::getConditionFromArray($array['condition2']));
            case 'Greater':
                return new FilterConditionGreater($array['fieldname'], $array['value']);
            case 'GreaterEqual':
                return new FilterConditionGreaterEqual($array['fieldname'], $array['value']);
            case 'Lesser':
                return new FilterConditionLesser($array['fieldname'], $array['value']);
            case 'LesserEqual':
                return new FilterConditionLesserEqual($array['fieldname'], $array['value']);
            case 'Like':
                return new FilterConditionLike($array['fieldname'], $array['value']);
            case 'Match':
                return new FilterConditionMatch($array['fieldname'], $array['value']);
            case 'OneOf':
                return new FilterConditionOneOf($array['fieldname'], $array['value']);
            default:
                trigger_error('Could not parse FilterCondition', E_USER_ERROR);
        }
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
    
    /**
     * Get this condition expressed as an array.
     * @return array
     */
    public function toArray() {
        return array('condition' => 'none');
    }
    
}