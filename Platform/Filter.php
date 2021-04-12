<?php
namespace Platform;

class Filter {
    
    /**
     * The name of the base class
     * @var string
     */
    private $base_classname = null;

    /**
     * An instance of the class the filter works on.
     * @var Datarecord 
     */
    private $base_object = null;
    
    /**
     * The base condition of the filter.
     * @var Condition 
     */
    private $base_condition = null;

    /**
     * Store filter errors
     * @var array 
     */
    protected $errors = array();
    
    /**
     * Indicate if we should perform an access check
     * @var bool
     */
    protected $perform_access_check = false;
    
    /**
     * Indicate if a condition forced a metadata search
     * @var bool
     */
    private $search_metadata = false;
    
    /**
     * Construct a filter
     * @param string $classname Class name of the base class to operate on.
     */
    public function __construct(string $classname) {
        if (substr($classname,0,1) == '\\') $classname = substr($classname,1);
        if (! class_exists($classname)) trigger_error('Invalid classname calling filter', E_USER_ERROR);
        $this->base_classname = $classname;
        $this->base_object = new $classname();
        if (! $this->base_object instanceof Datarecord) trigger_error('Must attach Datarecord to filter', E_USER_ERROR);
    }
    
    /**
     * Add a condition to the filter. Several conditions can be added, and will
     * be AND'ed together
     * @param \Platform\Condition $condition Condition to add
     */
    public function addCondition(Condition $condition) {
        $condition->attachFilter($this);
        if ($this->base_condition == null) {
            $this->base_condition = $condition;
        } else {
            $this->base_condition = new ConditionAND($this->base_condition, $condition);
        }
    }

    /**
     * Add a condition to the filter from the provided JSON.
     * @param type $json
     */    
    public function addConditionFromJSON(string $json) {
        $array = json_decode($json, true);
        $this->addCondition(Condition::getConditionFromArray($array['base_condition']));
    }

    /**
     * Add a condition to the filter. Several conditions can be added, and will
     * be OR'ed together
     * @param \Platform\Condition $condition Condition to add
     */
    public function addConditionOR(Condition $condition) {
        $condition->attachFilter($this);
        if ($this->base_condition == null) {
            $this->base_condition = $condition;
        } else {
            $this->base_condition = new ConditionOR($this->base_condition, $condition);
        }
    }
    
    
    /**
     * Execute this filter
     * @return Collection The result of the filter.
     */
    public function execute() {
        if (! $this->isValid()) return false;
        $result = $this->base_object->getCollectionFromSQL($this->getSQL(), $this->perform_access_check);
        if (! $this->search_metadata) return $result;
        $filtered_datacollection = new Collection();
        foreach ($result as $object) {
            if ($this->base_condition->match($object)) {
                $filtered_datacollection->add($object);
            }
        }
        return $filtered_datacollection;
    }
    
    /**
     * Execute this filter and get first result
     * @return Datarecord First result or empty object of same type.
     */
    public function executeAndGetFirst() {
        $collection = $this->base_object->getCollectionFromSQL($this->getSQL(), $this->perform_access_check);
        if (! $collection->getCount()) return new $this->base_classname();
        return $collection->get(0);
    }
    
    /**
     * Get this filter as an array
     * @return array
     */
    public function getAsArray() : array {
        $result = array('base_classname' => $this->base_classname);
        if ($this->base_condition instanceof Condition) {
            $result['base_condition'] = $this->base_condition->getAsArray();
        }
        return $result;
    }
    
    /**
     * Express this filter as a JSON string
     * @return string
     */
    public function getAsJSON() : string {
        return json_encode($this->getAsArray());
    }

    /**
     * Get an array of errors in this filter.
     * @return array
     */
    public function getErrors() : array {
        return $this->errors;
    }
    
    /**
     * Get a filter from an array
     * @param string $array Array as prepared by the getAsArray function
     * @return \Platform\Filter
     */
    public static function getFilterFromArray(array $array) {
        if ($array === null) return false;
        if (! class_exists($array['base_classname'])) return false;
        $filter = new Filter($array['base_classname']);
        if ($array['base_condition']) {
            $filter->addCondition(Condition::getConditionFromArray($array['base_condition']));
        }
        return $filter;
    }

    /**
     * Get a filter from a JSON string
     * @param string $json JSON string as prepared by the toJSON function
     * @return \Platform\Filter
     */
    public static function getFilterFromJSON(string $json) : Filter {
        $array = json_decode($json, true);
        if ($array === null) return false;
        if (! class_exists($array['base_classname'])) return false;
        $filter = new Filter($array['base_classname']);
        if ($array['base_condition']) {
            $filter->addCondition(Condition::getConditionFromArray($array['base_condition']));
        }
        return $filter;
    }
    
    /**
     * Get the base class name of this filter.
     * @return string
     */
    public function getBaseClassName() : string {
        return $this->base_classname;
    }
    
    /**
     * Get the base object of this filter.
     * @return string
     */
    public function getBaseObject() : Datarecord {
        return $this->base_object;
    }
    
    /**
     * Get a SQL statement representing this filter.
     * @return string
     */
    public function getSQL() : string {
        $sql = 'SELECT * FROM '.$this->base_object->getDatabaseTable().$this->getSQLWhere();
        return $sql;
    }
    
    /**
     * Get a SQL Where string for this filter
     * @return string
     */
    public function getSQLWhere() {
        return $this->base_condition instanceof Condition ? ' WHERE '.$this->base_condition->getSQLFragment() : '';
    }
    
    /**
     * Determine if filter is valid
     * @return bool
     */
    public function isValid() : bool {
        if (! $this->base_condition) return true;
        $valid = $this->base_condition->validate();
        if (is_array($valid)) {
            $this->errors = $valid;
            return false;
        }
        return true;
    }
    
    /**
     * Set if this filter should perform an access check when running
     * @param bool $perform_access_check
     */
    public function setPerformAccessCheck(bool $perform_access_check) {
        $this->perform_access_check = $perform_access_check;
    }
    
    /**
     * Set that this filter should search metadata.
     */
    public function setSearchMetadata() {
        $this->search_metadata = true;
    }
    
    /**
     * Check if this filter will search metadata (and therefore can't rely on SQL only).
     * @return bool
     */
    public function willSearchMetadata() {
        return $this->search_metadata;
    }
}