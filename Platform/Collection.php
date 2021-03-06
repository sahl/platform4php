<?php
namespace Platform;

class Collection implements \Iterator {

    /**
     * Type of data in this collection
     * @var string 
     */
    private $collectiontype = false;
    
    /**
     * Data buffer
     * @var array 
     */
    private $datarecords = array();
    
    /**
     * Internal pointer for iterator interface.
     * @var int
     */
    private $pointer = 0;
    
    /**
     * Construct a DatarecordCollection
     * @param Datarecord $datarecord Object to add to this record.
     */
    public function __construct(Datarecord $datarecord = null) {
        if ($datarecord !== null) $this->add($datarecord);
    }
    
    /**
     * Add one or more objects to this datarecord
     * @param array|Datarecord $datarecords Datarecord to add
     */
    public function add($datarecords) {
        if (! is_array($datarecords)) $datarecords = array($datarecords);
        foreach ($datarecords as $datarecord) {
            // Check if datarecord
            if (! $datarecord instanceof Datarecord) trigger_error('Passed non-Datarecord object into collection.', E_USER_ERROR);
            // Check if same type of other Datarecords in this collection
            if ($this->collectiontype !== false && $this->collectiontype != get_class($datarecord)) trigger_error('Passed '.get_class($datarecord).' into a collection of '.$this->collectiontype);
            $this->collectiontype = get_class($datarecord);
            $datarecord->collection = $this;
            $this->datarecords[] = $datarecord;
        }
    }
    
    /**
     * Delete all contained records from the database. This overrides blocking.
     */
    public function deleteAll() {
        foreach ($this->datarecords as $datarecord) {
            if ($datarecord->reloadForWrite()) $datarecord->delete(true);
        }
        $this->collectiontype = false;
        $this->datarecords = [];
    }
    
    /**
     * Extract a Datarecord from the collection
     * @param int $i Index to retrieve
     * @return Datarecord
     */
    public function get(int $i) {
        if ($i >= $this->getCount()) trigger_error('Requested OOR-index', E_USER_ERROR);
        return $this->datarecords[$i];
    }

    /**
     * Return all Datarecord's from this collection
     * @return array
     */
    public function getAll() : array {
        return $this->datarecords;
    }
    
    /**
     * Get all titles from the objects in this collection hashed by their IDs
     * @return array
     */
    public function getAllAsArray() : array {
        $result = array();
        if (! $this->getCount()) return array();
        $class = new $this->collectiontype();
        $keyfield = $class->getKeyField();
        foreach ($this->getAll() as $object) {
            $result[$object->getRawValue($keyfield)] = $object->getTitle();
        }
        return $result;
    }
    
    /**
     * Get all Datarecords from this collection in an array hashed by the object
     * id.
     * @return array
     */
    public function getAllWithKeys() : array {
        if (! count($this->datarecords)) return array();
        $result = array();
        foreach ($this->datarecords as $object) {
            $result[$object->getRawValue($object->getKeyField())] = $object;
        }
        return $result;
    }
    
    /**
     * Get the raw value from the given field from all objects in this collection.
     * @param string $field Field to read from
     * @param int $limit Max number of values to retrieve. -1 = get all
     * @return array Array of raw values hashed by object id.
     */
    public function getAllRawValues(string $field, int $limit = -1) : array {
        $result = array();
        foreach ($this->datarecords as $datarecord) {
            if ($limit > -1 && count($result) >= $limit) break;
            $result[$datarecord->getRawValue($datarecord->getKeyField())] = $datarecord->getRawValue($field);
        }
        return $result;
    }
    
    /**
     * Get the full value from the given field from all objects in this collection.
     * @param string $field Field to read from
     * @param int $limit Max number of values to retrieve. -1 = get all
     * @return array Array of full values sorted alfabetically hashed by object id
     */
    public function getAllFullValues(string $field, int $limit = -1) : array {
        $keys = array(); $result = array(); $sort_array = array();
        foreach ($this->datarecords as $datarecord) {
            if ($limit > -1 && count($result) >= $limit) break;
            $value = $datarecord->getFullValue($field);
            $keys[] = $datarecord->getRawValue($datarecord->getKeyField());
            $result[] = $value;
            // Strip HTML from the sorting array
            $sort_array[] = strip_tags($value);
        }
        array_multisort($sort_array, SORT_ASC, $result, $keys);
        $result = array_combine($keys, $result);
        return $result;
    }

    /**
     * Get all associated objects which are object pointed to, by a relation field
     * in this collection
     * @param string $field Field name to consider
     * @return Datacollection All associated objects
     */
    public function getAssociatedObjects(string $field) : Collection {
        $foreign_ids = array();
        foreach ($this->getAllRawValues($field) as $value) {
            if (is_array($value)) {
                foreach ($value as $v) if (! in_array($v, $foreign_ids) && $v) $foreign_ids[] = $v;
            } else {
                if (! in_array($value, $foreign_ids) && $value) $foreign_ids[] = $value;
            }
        }
        if (! count($foreign_ids)) return new Collection();
        $structure = $this->collectiontype::getStructure();
        $foreign_class = $structure[$field]['foreign_class'];
        if (!class_exists($foreign_class)) return new Collection();
        $filter = new Filter($foreign_class);
        $filter->addCondition(new ConditionOneOf($foreign_class::getKeyfield(), $foreign_ids));
        return $filter->execute();
    }
    
    /**
     * Get the type of objects in this collection
     * @return string Class name
     */
    public function getCollectionType() : string {
        return $this->collectiontype;
    }
    
    /**
     * Get the number of entries in this collection
     * @return int Number of entries
     */
    public function getCount() : int {
        return count($this->datarecords);
    }

    /**
     * Sort this datacollection according to the given field
     * @param string $fields Fields to sort by. If several fields are given use comma as a separator.
     * @return Collection Return itself so it can be chained to another command
     */
    public function sort(string $fields) {
        $sort_array = array();
        foreach ($this->datarecords as $datarecord) {
            $finalvalue = '';
            foreach (explode(',',$fields) as $field) {
                $finalvalue .= $datarecord->getTextValue($field);
            }
            $sort_array[] = $finalvalue;
        }
        array_multisort($sort_array, SORT_ASC, $this->datarecords);
        return $this;
    }
    
    // Iterator interface
    public function current() {return $this->get($this->pointer);}
    public function key() { $object = $this->get($this->pointer); return $object->getRawValue($object->getKeyField());}
    public function next() {$this->pointer++;}
    public function rewind() {$this->pointer = 0;}
    public function valid() {return $this->pointer < $this->getCount();}
    
}