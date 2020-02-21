<?php
namespace Platform;

class ConditionNOT extends Condition {
    
    private $condition = null;
    
    public function __construct($condition) {
        Errorhandler::checkParams($condition, '\\Platform\\Condition');
        $this->condition = $condition;
    }
    
    /**
     * Attach a filter to this condition
     * @param \Platform\Filter $filter
     */
    public function attachFilter($filter) {
        Errorhandler::checkParams($filter, '\\Platform\\Filter');
        $this->condition->attachFilter($filter);
        parent::attachFilter($filter);
    }
    
    public function getSQLFragment() {
        return 'NOT ('.$this->condition->getSQLFragment().')';
    }
    
    /**
     * Get this condition expressed as an array.
     * @return array
     */
    public function toArray() {
        return array(
            'type' => 'NOT',
            'condition' => $this->condition->toArray()
        );
    }
    
}