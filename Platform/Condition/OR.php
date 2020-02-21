<?php
namespace Platform;

class ConditionOR extends Condition {
    
    private $condition1 = null, $condition2 = null;
    
    public function __construct($condition1, $condition2) {
        Errorhandler::checkParams($condition1, '\\Platform\\Condition', $condition2, '\\Platform\\Condition');
        
        $this->condition1 = $condition1;
        $this->condition2 = $condition2;
    }
    
    /**
     * Attach a filter to this condition
     * @param \Platform\Filter $filter
     */
    public function attachFilter($filter) {
        Errorhandler::checkParams($filter, '\\Platform\\Filter');
        $this->condition1->attachFilter($filter);
        $this->condition2->attachFilter($filter);
        parent::attachFilter($filter);
    }
    
    
    public function getSQLFragment() {
        return '('.$this->condition1->getSQLFragment().' OR '.$this->condition2->getSQLFragment().')';
    }
    
    /**
     * Get this condition expressed as an array.
     * @return array
     */
    public function toArray() {
        return array(
            'type' => 'OR',
            'condition1' => $this->condition1->toArray(),
            'condition2' => $this->condition2->toArray()
        );
    }
}