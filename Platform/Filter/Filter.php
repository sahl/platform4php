<?php
namespace Platform\Filter;

use Platform\Datarecord\Collection;
use Platform\Datarecord\Datarecord;
use Platform\Platform;

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
     * Indicate if we should limit the number of results returned? Limit to X results
     * @var int
     */
    protected $limit_results = null;
    
    /**
     * Indicate if we should start beyond the first result. Start at result X.
     * @var type
     */
    protected $start_at_result = null;
    
    /**
     * SQL for sorting the results
     * @var string
     */
    protected $sql_sort = null;
    
    /**
     * Indicate if we should perform an access check
     * @var bool
     */
    protected $perform_access_check = false;
    
    /**
     * Indicate if this filter also needs to run without SQL.
     * @var bool
     */
    private $filter_after_sql = false;
    
    /**
     * Construct a filter
     * @param string $classname Class name of the base class to operate on.
     */
    public function __construct(string $classname) {
        Platform::normalizeClass($classname);
        if (! class_exists($classname)) trigger_error('Invalid classname \''.$classname.'\' calling filter', E_USER_ERROR);
        $this->base_classname = $classname;
        $this->base_object = new $classname();
        if (! $this->base_object instanceof Datarecord) trigger_error('Must attach Datarecord to filter', E_USER_ERROR);
    }
    
    /**
     * Add a condition to the filter. Several conditions can be added, and will
     * be AND'ed together
     * @param \Platform\Filter\Condition\Condition $condition Condition to add
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
        if ($array['base_condition']) $array = $array['base_condition'];
        $this->addCondition(Condition::getConditionFromArray($array));
    }

    /**
     * Add a condition to the filter. Several conditions can be added, and will
     * be OR'ed together
     * @param Condition $condition Condition to add
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
     * Add another Filter to this Filter
     * @param Filter $filter
     */
    public function addFilter(Filter $filter) {
        if ($filter->getBaseClassName() != $this->getBaseClassName()) trigger_error('Tried to add incompatible filter', E_USER_ERROR);
        if ($filter->getBaseCondition()) $this->addCondition($filter->getBaseCondition());
        if ($this->getResultLimit() === null && $filter->getResultLimit() !== null) $this->setResultLimit($filter->getResultLimit());
    }
    
    /**
     * Execute this filter
     * @return Collection The result of the filter.
     */
    public function execute() {
        if (! $this->isValid()) return false;
        // We prepare a collection if we need to do manual filtering
        $filtered_datacollection = new Collection();
        // We track original limits as we can change them during execution
        $original_start = $this->start_at_result;
        $original_limit = $this->limit_results;
        // If we need to do manual filtering, we need to start the SQL limit at 0, as we cannot predict the number of results not received.
        if ($this->filter_after_sql && (int) $this->start_at_result > 0) $this->start_at_result = 0;
        // We need to track discarded result (if we don't start from zero)
        $discarded_results = 0;
        while (true) {
            // Do the SQL selection
            $result = $this->base_object->getCollectionFromSQL($this->getSQL());
            // If this filter can be done purely in SQL we have a valid result
            if (! $this->filter_after_sql && ! $this->perform_access_check) return $result;
            // Loop all results
            foreach ($result as $object) {
                // First check for a manual access check
                if ($this->filter_after_sql) {
                    // Do a manual match on the object
                    if ($this->base_condition->match($object)) {
                        // If we shouldn't start from the beginning we need to throw away some results
                        if ($this->start_at_result !== null && $discarded_results++ < $original_start) continue;
                    }
                }
                // Then check for access check
                if ($this->perform_access_check) {
                    if (! $object->canAccess()) continue;
                }
                // Add it
                $filtered_datacollection->add($object);
                // Check if we have enough results (if there is a limit) and stop collecting more if we have
                if ($this->limit_results !== null && count($filtered_datacollection) == $original_limit) break;
            }
            // Check if we have enough results (if there is a limit) and break out of the "fetch more" loop
            if ($this->limit_results === null || count($filtered_datacollection) == $original_limit) break;
            // Also break out if we can see we have exhausted the database.
            if (count($result) < $original_limit) break;
            // We need to do a new SQL, so we increase the start result
            $this->start_at_result = (int)$this->start_at_result + $this->limit_results;
            // To prevent a lot of small queries, we also gradually increase the limit of returned results
            if ($this->limit_results < 1000) $this->limit_results = 1000;
            else $this->limit_results *= 2;
        }
        // Reset limits to original count
        $this->start_at_result = $original_start;
        $this->limit_results = $original_limit;
        // Return the final result
        return $filtered_datacollection;
    }
    
    /**
     * Check if a given object matches this filter
     * @param Datarecord $object
     * @return bool
     */
    public function match(Datarecord $object) : bool {
        if (! $this->base_condition) return true;
        return $this->base_condition->match($object, true);
    }
    
    /**
     * Execute this filter and get first result
     * @return Datarecord First result or empty object of same type.
     */
    public function executeAndGetFirst() {
        $this->setResultLimit(1);
        $this->setResultStart(0);
        $collection = $this->execute();
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
     * Get the base condition of this filter
     * @return type
     */
    public function getBaseCondition() {
        return $this->base_condition;
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
        return static::getFilterFromArray($array);
        /*
        if ($array === null) return false;
        if (! class_exists($array['base_classname'])) return false;
        $filter = new Filter($array['base_classname']);
        if ($array['base_condition']) {
            $filter->addCondition(Condition::getConditionFromArray($array['base_condition']));
        }
        return $filter;*/
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
        $sql = 'SELECT * FROM '.$this->base_object->getDatabaseTable().$this->getSQLWhere().$this->getSQLOrderAndLimit();
        return $sql;
    }

    /**
     * Get SQL for ORDER and LIMIT
     * @return string
     */
    public function getSQLOrderAndLimit() : string {
        $result = '';
        if ($this->sql_sort) $result .= ' ORDER BY '.$this->sql_sort;
        if ($this->limit_results !== null || $this->start_at_result !== null) {
            $start = $this->start_at_result ?: 0;
            $limit = $this->limit_results;
            $result .= ' LIMIT '.$start.','.$limit;
        }
        return $result;
    }
    
    /**
     * Get a SQL where string for this filter
     * @return string
     */
    public function getSQLWhere() : string {
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
     * Indicate which column we should sort after
     * @param string $order_column Column name
     * @param bool $ascending Should we sort ascending (with the opposite being descending)
     */
    public function setOrderColumn(string $order_column, bool $ascending = true) {
        // Check if field exists
        $type = $this->getBaseObject()->getFieldDefinition($order_column);
        if (! $type ) trigger_error('No field '.$order_column.' in object.', E_USER_ERROR);
        $sort = $type->getSQLSort(! $ascending);
        if ($sort === false) trigger_error('You cannot sort by '.$order_column, E_USER_ERROR);
        $this->sql_sort = $sort;
    }
    
    /**
     * Set if this filter should perform an access check when running
     * @param bool $perform_access_check
     */
    public function setPerformAccessCheck(bool $perform_access_check = true) {
        $this->perform_access_check = $perform_access_check;
    }
    
    /**
     * Get result limit
     * @return int Number of results or null if no limit
     */
    public function getResultLimit() {
        return $this->limit_results;
    }
    
    /**
     * Set the maximum number of results you want returned.
     * @param int $result_limit
     */
    public function setResultLimit(int $result_limit) {
        $this->limit_results = $result_limit;
    }
    
    public function setResultStart(int $result_start) {
        $this->start_at_result = $result_start;
    }
    
    /**
     * Set that this filter should also filter without SQL
     */
    public function setFilterAfterSQL() {
        $this->filter_after_sql = true;
    }
    
    /**
     * Check if this filter needs to filter without SQL
     * @return bool
     */
    public function getFilterWithoutSQL() {
        return $this->filter_after_sql;
    }
    
    /**
     * Convenience for adding an AND condition
     * @param Condition $condition1 Condition 1 for the AND
     * @param Condition $condition2 Condition 2 for the AND
     */
    public function conditionAND(Condition $condition1, Condition $condition2) {
        $this->addCondition(new ConditionAND($condition1, $condition2));
    }
    
    /**
     * Convenience for adding an NOT condition
     * @param Condition $condition Condition for the NOT
     */
    public function conditionNOT(Condition $condition) {
        $this->addCondition(new ConditionNOT($condition));
    }
    
    /**
     * Convenience for adding an OR condition
     * @param Condition $condition1 Condition 1 for the OR
     * @param Condition $condition2 Condition 2 for the OR
     */
    public function conditionOR(Condition $condition1, Condition $condition2) {
        $this->addCondition(new ConditionOR($condition1, $condition2));
    }
    
    /**
     * Convenience for adding a Match condition
     * @param string $field Field to check
     * @param mixed $value Value to check
     */
    public function conditionMatch(string $field, $value) {
        $this->addCondition(new ConditionMatch($field, $value));
    }
    
    /**
     * Convenience for adding a Match condition
     * @param string $field Field to check
     * @param mixed $value Value to check
     */
    public function conditionGreater(string $field, $value) {
        $this->addCondition(new ConditionGreater($field, $value));
    }
    
    /**
     * Convenience for adding a Match condition
     * @param string $field Field to check
     * @param mixed $value Value to check
     */
    public function conditionGreaterEqual(string $field, $value) {
        $this->addCondition(new ConditionGreaterEqual($field, $value));
    }
    
    /**
     * Convenience for adding an InCollection condition
     * @param Collection $collection
     */
    public function conditionInCollection(Collection $collection) {
        $this->addCondition(new ConditionInCollection($collection));
    }
    
    /**
     * Convenience for adding a InFilter condition
     * @param Filter $filter
     */
    public function conditionInFilter(string $field, Filter $filter) {
        $this->addCondition(new ConditionInFilter($field, $filter));
    }
    
    /**
     * Convenience for adding an IsSet condition
     * @param string $field Field to check
     */
    public function conditionIsSet(string $field) {
        $this->addCondition(new ConditionIsSet($field));
    }
    
    /**
     * Convenience for adding a Lesser condition
     * @param string $field Field to check
     * @param mixed $value Value to check
     */
    public function conditionLesser(string $field, $value) {
        $this->addCondition(new ConditionLesser($field, $value));
    }
    
    /**
     * Convenience for adding a LessorEqual condition
     * @param string $field Field to check
     * @param mixed $value Value to check
     */
    public function conditionLesserEqual(string $field, $value) {
        $this->addCondition(new ConditionLesserEqual($field, $value));
    }
    
    /**
     * Convenience for adding a OneOf condition
     * @param string $field Field to check
     * @param array $values Values to check against
     */
    public function conditionOneOf(string $field, array|Collection $values) {
        $this->addCondition(new ConditionOneOf($field, $values));
    }
    
    /**
     * Convenience for adding a Like condition
     * @param string $field Field to check
     * @param mixed $value Value to check
     */
    public function conditionLike(string $field, $value) {
        $this->addCondition(new ConditionLike($field, $value));
    }
}