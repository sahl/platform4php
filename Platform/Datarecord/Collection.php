<?php
namespace Platform;

class DatarecordCollection {

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
     * Construct a DatarecordCollection
     * @param Datarecord $datarecord Object to add to this record.
     */
    public function __construct($datarecord = null) {
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
     * Delete all contained records from the database
     */
    public function deleteAll() {
        foreach ($this->datarecords as $datarecord) {
            if ($datarecord->reloadForWrite()) $datarecord->delete();
        }
        $this->collectiontype = false;
        $this->datarecords = [];
    }
    
    /**
     * Extract a Datarecord from the collection
     * @param int $i Index to retrieve
     * @return Datarecord
     */
    public function get($i) {
        if ($i >= $this->getCount()) trigger_error('Requested OOR-index', E_USER_ERROR);
        return $this->datarecords[$i];
    }

    /**
     * Return all Datarecord's from this collection
     * @return array
     */
    public function getAll() {
        return $this->datarecords;
    }
    
    /**
     * Get the raw value from the given field from all objects in this collection.
     * @param string $field Field to read from
     * @param int $limit Max number of values to retrieve. -1 = get all
     * @return array Array of raw values
     */
    public function getAllRawValues($field, $limit = -1) {
        $result = array();
        foreach ($this->datarecords as $datarecord) {
            if ($limit > -1 && count($result) >= $limit) break;
            $result[] = $datarecord->getRawValue($field);
        }
        return $result;
    }
    
    /**
     * Get the full value from the given field from all objects in this collection.
     * @param string $field Field to read from
     * @param int $limit Max number of values to retrieve. -1 = get all
     * @return array Array of full values sorted alfabetically
     */
    public function getAllFullValues($field, $limit = -1) {
        $result = array(); $sort_array = array();
        foreach ($this->datarecords as $datarecord) {
            if ($limit > -1 && count($result) >= $limit) break;
            $value = $datarecord->getFullValue($field);
            $result[] = $value;
            // Strip HTML from the sorting array
            $sort_array[] = strip_tags($value);
        }
        array_multisort($sort_array, SORT_ASC, $result);
        return $result;
    }
    
    /**
     * Get the type of objects in this collection
     * @return string Class name
     */
    public function getCollectionType() {
        return $this->collectiontype;
    }
    
    /**
     * Get the number of entries in this collection
     * @return int Number of entries
     */
    public function getCount() {
        return count($this->datarecords);
    }

    /**
     * Sort this datacollection according to the given field
     * @param string $field Field to sort by.
     */
    public function sort($field) {
        $sort_array = array();
        foreach ($this->datarecords as $datarecord) {
            $sort_array[] = $datarecord->getTextValue($field);
        }
        array_multisort($sort_array, SORT_ASC, $this->datarecords);
    }
}