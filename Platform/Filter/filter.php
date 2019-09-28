<?php
namespace Platform;

class Filter {
    
    /**
     * An instance of the class the filter works on.
     * @var Datarecord 
     */
    private $base_object = null;
    
    /**
     * The base condition of the filter.
     * @var FilterCondition 
     */
    private $base_condition = null;
        
    /**
     * Construct a filter
     * @param string $classname Class name of the base class to operate on.
     */
    public function __construct($classname) {
        $this->base_object = new $classname();
        if (! $this->base_object instanceof Datarecord) trigger_error('Must attach Datarecord to filter');
    }
    
    /**
     * Add a condition to the filter. Several conditions can be added, and will
     * be AND'ed together
     * @param \Platform\FilterCondition $condition Condition to add
     */
    public function addCondition($condition) {
        if (! $condition instanceof FilterCondition) trigger_error('Invalid condition added to filter!', E_USER_ERROR);
        $condition->attachFilter($this);
        if ($this->base_condition == null) {
            $this->base_condition = $condition;
        } else {
            $this->base_condition = new FilterConditionAND($this->base_condition, $condition);
        }
    }
    
    /**
     * Execute this filter
     * @return DatarecordCollection The result of the filter.
     */
    public function execute() {
        return $this->base_object->getCollectionFromSQL($this->getSQL());
    }
    
    /**
     * Execute this filter and get first result
     * @return Datarecord|boolean First result or false if no results
     */
    public function executeAndGetFirst() {
        $collection = $this->base_object->getCollectionFromSQL($this->getSQL());
        if (! $collection->getCount()) return false;
        return $collection->get(0);
    }
    
    /**
     * Get the base object of this filter.
     * @return string
     */
    public function getBaseObject() {
        return $this->base_object;
    }
    
    /**
     * Get a SQL statement representing this filter.
     * @return string
     */
    public function getSQL() {
        $sql = 'SELECT * FROM '.$this->base_object->getDatabaseTable();
        if ($this->base_condition instanceof FilterCondition) $sql .= ' WHERE '.$this->base_condition->getSQLFragment();
        return $sql;
    }
    
}