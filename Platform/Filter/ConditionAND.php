<?php
namespace Platform;

class FilterConditionAND extends FilterCondition {
    
    private $condition1 = null, $condition2 = null;
    
    public function __construct($condition1, $condition2) {
        if (! $condition1 instanceof FilterCondition) trigger_error('Parameter 1 must be FilterCondition', E_USER_ERROR);
        if (! $condition2 instanceof FilterCondition) trigger_error('Parameter 2 must be FilterCondition', E_USER_ERROR);
        
        $this->condition1 = $condition1;
        $this->condition2 = $condition2;
    }
    
    public function getSQLFragment() {
        return '('.$this->condition1->getSQLFragment().' AND '.$this->condition2->getSQLFragment().')';
    }
}