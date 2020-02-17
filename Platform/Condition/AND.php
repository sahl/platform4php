<?php
namespace Platform;

class ConditionAND extends Condition {
    
    private $condition1 = null, $condition2 = null;
    
    public function __construct($condition1, $condition2) {
        if (! $condition1 instanceof Condition) trigger_error('Parameter 1 must be FilterCondition', E_USER_ERROR);
        if (! $condition2 instanceof Condition) trigger_error('Parameter 2 must be FilterCondition', E_USER_ERROR);
        
        $this->condition1 = $condition1;
        $this->condition2 = $condition2;
    }
    
    /**
     * Attach a filter to this condition
     * @param \Platform\Filter $filter
     */
    public function attachFilter($filter) {
        $this->condition1->attachFilter($filter);
        $this->condition2->attachFilter($filter);
        parent::attachFilter($filter);
    }
    
    
    public function getSQLFragment() {
        return '('.$this->condition1->getSQLFragment().' AND '.$this->condition2->getSQLFragment().')';
    }
    
    /**
     * Get this condition expressed as an array.
     * @return array
     */
    public function toArray() {
        return array(
            'type' => 'AND',
            'condition1' => $this->condition1->toArray(),
            'condition2' => $this->condition2->toArray()
        );
    }
}