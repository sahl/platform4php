<?php
namespace Platform;

class FilterConditionNOT extends FilterCondition {
    
    private $condition = null;
    
    public function __construct($condition) {
        if (! $condition instanceof FilterCondition) trigger_error('Parameter 1 must be FilterCondition', E_USER_ERROR);
        $this->condition = $condition;
    }
    
    /**
     * Attach a filter to this condition
     * @param \Platform\Filter $filter
     */
    public function attachFilter($filter) {
        $this->condition->attachFilter($filter);
        parent::attachFilter($filter);
    }
    
    public function getSQLFragment() {
        return 'NOT ('.$this->condition->getSQLFragment().')';
    }
}