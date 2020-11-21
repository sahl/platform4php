<?php
namespace Platform;

class Condition {
    
    /**
     * Name of field to filter on
     * @var string 
     */
    public $fieldname = '';
    
    /**
     * The filter containing this condition
     * @var Filter 
     */
    public $filter = null;
    
    /**
     * Indicate that this condition must use manual match
     * @var boolean
     */
    public $manual_match = false;

    /**
     * Value to filter against
     * @var mixed 
     */
    public $value = '';
    
    /**
     * Attach a filter to this condition
     * @param \Platform\Filter $filter
     */
    public function attachFilter($filter) {
        Errorhandler::checkParams($filter, '\\Platform\\Filter');
        $this->filter = $filter;
        
        $field_definition = $this->filter->getBaseObject()->getFieldDefinition($this->fieldname);
        $this->manual_match = $field_definition['store_in_metadata'];
        if ($this->manual_match) $filter->setSearchMetadata();
    }
    
    /**
     * Get this condition expressed as an array.
     * @return array
     */
    public function getAsArray() {
        return array('condition' => 'none');
    }
    
    public function getAsJSON() {
        return json_encode($this->getAsArray());
    }
    
    /**
     * Decode a condition from an array.
     * @param array $array Array earlier packed with toArray()
     * @return \Platform\ConditionMatch|\Platform\ConditionLike|\Platform\ConditionNOT|\Platform\ConditionLesserEqual|\Platform\ConditionOneOf|\Platform\ConditionGreater|\Platform\ConditionGreaterEqual|\Platform\FilterConditionLesser|\Platform\ConditionOR|\Platform\ConditionAND
     */
    public static function getConditionFromArray($array) {
        Errorhandler::checkParams($array, array('null', 'array'));
        if ($array === null) return null;
        switch ($array['type']) {
            case 'AND':
                return new ConditionAND(self::getConditionFromArray($array['condition1']), self::getConditionFromArray($array['condition2']));
            case 'NOT':
                return new ConditionNOT(self::getConditionFromArray($array['condition']));
            case 'OR':
                return new ConditionOR(self::getConditionFromArray($array['condition1']), self::getConditionFromArray($array['condition2']));
            case 'Greater':
                return new ConditionGreater($array['fieldname'], $array['value']);
            case 'GreaterEqual':
                return new ConditionGreaterEqual($array['fieldname'], $array['value']);
            case 'Lesser':
                return new ConditionLesser($array['fieldname'], $array['value']);
            case 'LesserEqual':
                return new ConditionLesserEqual($array['fieldname'], $array['value']);
            case 'Like':
                return new ConditionLike($array['fieldname'], $array['value']);
            case 'Match':
                return new ConditionMatch($array['fieldname'], $array['value']);
            case 'OneOf':
                return new ConditionOneOf($array['fieldname'], $array['value']);
            case 'InFilter':
                return new ConditionInFilter($array['fieldname'], $array['filter']);
            default:
                return new Condition();
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
        //if ($field_definition['store_in_database'] === false || $field_definition['store_in_metadata'] === true) trigger_error('Can only filter on fields in database (yet!). And '.$this->fieldname.' is not in the database!', E_USER_ERROR);
        
        // We need to do something extra for password fields
        if ($field_definition['fieldtype'] == Datarecord::FIELDTYPE_PASSWORD) $value = md5($value.$platform_configuration['password_salt']);
        
        // Handle timestamps
        if ($value instanceof Time) $value = $value->get();
        
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
     * Check if this filter matches the given object
     * @param Datarecord $object
     * @return boolean True if match
     */
    public function match($object) {
        return true;
    }
    
    /**
     * Indicate if this condition should use manual matching
     * @param type $manual_match
     */
    public function setManualMatch($manual_match = true) {
        Errorhandler::checkParams($match, 'bool');
        $this->manual_match = $manual_match;
    }
    
    /**
     * Validate if this condition is compatible with the object.
     * @return boolean|array True or an array of problems
     */
    public function validate() {
        return array('Unknown condition added to filter');
    }
    
}