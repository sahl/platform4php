<?php
namespace Platform;

class Datarecord {

    // Column visibilities
    const COLUMN_UNSELECTABLE = 1;
    const COLUMN_DEFAULTHIDDEN = 2;
    const COLUMN_DEFAULTSHOWN = 0;
    
    const DELETE_STRATEGY_BLOCK = 0;
    const DELETE_STRATEGY_REMOVE_REFERENCES = 1;
    const DELETE_STRATEGY_PURGE_REFERERS = 2;
    
    // Object locations
    const LOCATION_GLOBAL = 0;
    const LOCATION_INSTANCE = 1;

    // Read/write mode
    const MODE_READ = 0;
    const MODE_WRITE = 1;
    
    // Display modes
    const RENDER_RAW = 0;
    const RENDER_TEXT = 1;
    const RENDER_FULL = 2;
    const RENDER_FORM = 3;
    
    // Search fields
    const SEARCH_TOPIC = 1;
    const SEARCH_ADDITIONAL = 2;

    /**
     * Indicate what mode this object is in
     * @var int
     */
    protected $access_mode = self::MODE_WRITE;
    
    /**
     * Reference to a collection, that this is a part of
     * @var DatarecordCollection 
     */
    public $collection = false;
    
    /**
     * Database table to store records of this type.
     * @var string
     */
    protected static $database_table = '';
    
    /**
     * Set a delete strategy for this object
     * @var int
     */
    protected static $delete_strategy = self::DELETE_STRATEGY_BLOCK;

    /**
     * Set the default render mode, when getting values
     * @var int 
     */
    private $default_rendermode = self::RENDER_RAW;

    /**
     * Convenience to store keyfield
     * @var boolean|string 
     */
    protected static $key_field = false;
    
    /**
     * Indicate the location of this record
     * @var type 
     */
    protected static $location = LOCATION_GLOBAL;
    
    /**
     * Name of semaphore lock to lock this object, or false if not locked
     * @var boolean|string
     */
    protected $lockname = false;
    
    /**
     * Name of this object type
     * @var string 
     */
    protected static $object_name = '';
    
    /**
     * Names of all classes referring this class
     * @var array 
     */
    protected static $referring_classes = array();

    /**
     * Is populated with the structure of the data record
     * @var array|boolean Array of structure or false if isn't loaded.
     */
    protected static $structure = false;
    
    
    protected static $requested_calculation_buffer = array();
    
    /**
     * All values of the object
     * @var array
     */
    protected $values = array();
    
    /**
     * The values of the object on load
     * @var array
     */
    protected $values_on_load = array();
    
    /**
     * Buffer for foreign references
     * @var array 
     */
    private static $foreign_reference_buffer = array();
    
    /**
     * Constructs the object and ensures that the structure is in place.
     */
    public function __construct($initialvalues = array()) {
        static::ensureStructure();
        $this->setFromArray($initialvalues);
    }
    
    /**
     * Convenience to retrieve field value
     * @param string $field Field name
     * @return mixed Value
     */
    public function __get($field) {
        return $this->getValue($field, $this->default_rendermode);
    }
    
    /**
     * Convenience to set field value
     * @param string $field Field name
     * @param mixed $value Value
     */
    public function __set($field, $value) {
        $this->setValue($field, $value);
    }

    /**
     * Add the given array to the structure of this datarecord
     * @param array $structure Array of field definitions to add
     */
    public static function addStructure($structure) {
        foreach ($structure as $field => $data) {
            if (isset($data['foreignclass']) && substr($data['foreignclass'],0,1) == '\\') $data['foreignclass'] = substr($data['foreignclass'],1);
            static::$structure[$field] = $data;
        }
    }
    
    /**
     * Override to extend the object structure
     * @return array Definition of object fields
     */
    protected static function buildStructure() {
        static::addStructure(array(
            'metadata' => array(
                'invisible' => true,
                'fieldtype' => self::FIELDTYPE_ARRAY
            ),
            'create_date' => array(
                'label' => 'Created',
                'readonly' => true,
                'table' => self::COLUMN_DEFAULTHIDDEN,
                'fieldtype' => self::FIELDTYPE_DATETIME
            ),
            'change_date' => array(
                'label' => 'Last change',
                'readonly' => true,
                'table' => self::COLUMN_DEFAULTHIDDEN,
                'fieldtype' => self::FIELDTYPE_DATETIME
            )
        ));
    }
    
    /**
     * Check if this object can be accessed.
     * @return boolean
     */
    public function canAccess() {
        return true;
    }
    
    /**
     * Check if this object can be deleted
     * @return boolean|string True or an error message
     */
    public function canDelete() {
        if (! $this->isInDatabase()) return 'Not saved yet';
        if (static::$delete_strategy == self::DELETE_STRATEGY_BLOCK) {
            $referring_titles = $this->getReferringObjectTitles();
            if (count($referring_titles)) {
                $CUT = 5;
                $total = count($referring_titles);
                $display_titles = array_slice($referring_titles, 0, $cut);
                $return = implode(', ',$display_titles);
                if ($total > $CUT) $return .= ' and '.($total-$CUT).' more.';
                return 'This is referred by: '.$return;
            }
        }
        return true;
    }
    
    /**
     * Delete this record from the database.
     * @param boolean $force_remove Force a removal of references if object is configured for blocking only.
     * @return boolean True if something was actually deleted.
     */
    public function delete($force_remove = false) {
        if ($this->access_mode != self::MODE_WRITE) trigger_error('Tried to delete object '.static::$database_table.' in read mode', E_USER_ERROR);
        if (! $this->isInDatabase()) return false;
        
        if (! $force_remove && static::$delete_strategy == self::DELETE_STRATEGY_BLOCK && count($this->getReferringObjectTitles())) return false;
        
        self::query("DELETE FROM ".static::$database_table." WHERE ".static::getKeyField()." = ".((int)$this->values[static::getKeyField()]));
        $deleted_id = $this->values[static::getKeyField()];
        unset($this->values[static::getKeyField()]);
        $this->access_mode = self::MODE_READ;
        $this->unlock();
        
        if ($force_remove || static::$delete_strategy == self::DELETE_STRATEGY_REMOVE_REFERENCES || static::$delete_strategy == self::DELETE_STRATEGY_PURGE_REFERERS) {
            // Find all objects referring this
            foreach (static::$referring_classes as $referring_class) {
                // Build a filter to find all referers
                $referer_field_found = false;
                $filter = new Filter($referring_class);
                foreach ($referring_class::getStructure() as $key => $definition) {
                    if (in_array($definition['fieldtype'], array(self::FIELDTYPE_REFERENCE_SINGLE, self::FIELDTYPE_REFERENCE_MULTIPLE)) && $definition['foreignclass'] == get_called_class()) {
                        $filter->addConditionOR(new FilterConditionMatch($key, $deleted_id));
                        $referer_field_found = true;
                    }
                }
                // Bail if remote object doesn't have fields pointing at us.
                if (! $referer_field_found) continue;
                // Get all objects referring this
                $referring_objects = $filter->execute();
                if (static::$delete_strategy == self::DELETE_STRATEGY_PURGE_REFERERS) {
                    $referring_objects->deleteAll();
                } else {
                    foreach ($referring_objects->getAll() as $referring_object) {
                        $referring_object->reloadForWrite();
                        foreach ($referring_class::getStructure() as $key => $definition) {
                            if ($definition['foreignclass'] == get_called_class()) {
                                if ($definition['fieldtype'] == self::FIELDTYPE_REFERENCE_SINGLE) {
                                    if ($referring_object->getRawValue($key) == $deleted_id) $referring_object->setValue($key, 0);
                                } elseif ($definition['fieldtype'] == self::FIELDTYPE_REFERENCE_MULTIPLE) {
                                    $values = $referring_object->getRawValue($key);
                                    array_remove($values, $deleted_id);
                                    $referring_object->setValue($key, $values);
                                }
                            }
                        }
                        $referring_object->save();
                    }
                }
            }
        }
        
        return (static::$location == self::LOCATION_GLOBAL ? Database::globalAffected() : Database::instanceAffected()) > 0;
    }
    
    /**
     * Delete one or more objects by ID.
     * @param array|int $ids One or more IDs
     */
    public static function deleteByID($ids) {
        if (! is_array($ids)) $ids = array($ids);
        // First lock all objects
        foreach ($ids as $id) {
            $lockfile = self::getLockFileNameByTableAndID(static::$database_table, $id);
            if (!Semaphore::wait($lockfile)) trigger_error('Could not lock '.static::$database_table.' with id '.$id.' within a timely manner, when attempting to delete it!', E_USER_ERROR);
        }
        // Delete it all
        self::query("DELETE FROM ".static::$database_table." WHERE ".static::getKeyField()." IN (".implode(',',$ids).")");
        // Unlock again
        foreach ($ids as $id) {
            $lockfile = self::getLockFileNameByTableAndID(static::$database_table, $id);
            Semaphore::release($lockfile);
        }
    }
    
    /**
     * Do a calculation on this specific object
     * @param string $calculation Calculation to do
     */
    public function doCalculation($calculation) {
    }

    /**
     * Do a calculation on all objects with the given ids.
     * @param string $calculation Calculations to do
     * @param array $ids Object IDs
     */
    public static function doCalculationOnObjects($calculation, $ids) {
        $class = get_called_class();
        foreach ($ids as $id) {
            $object = new $class();
            $object->loadForWrite($id);
            if ($object->isInDatabase()) {
                $object->doCalculation($calculation);
                $object->save();
            }
        }
    }
    
    /**
     * The job to handle calculations
     * @param Job $job
     */
    public static function calculationJob($job) {
        global $platform_configuration;
        
        $MAX_LINES = 250000; // Max number of lines to read at once.
        $MAX_RUNTIME = ini_get('max_execution_time') ? min(ini_get("max_execution_time")-10, 60*10) : 60*10; // Max time to spend calculating in each run.
        
        $run_again = false;
        
        if (! Semaphore::wait('platform_calculation')) trigger_error('Could not obtain recalculation semaphore', E_USER_ERROR);
        if (! Semaphore::wait('platform_calculation_file')) trigger_error('Could not obtain recalculation file semaphore', E_USER_ERROR);

        $starttime = time();
        
        // Read lines from file
        $queue = array(); $lineno = 0;
        $fh_in = fopen($platform_configuration['dir_temp'].'recalculation.current', 'r');
        $fh_out = false;
        while ($line = fgets($fh_in)) {
            // If we exceed max number of lines, write the rest of the lines to a new file
            if ($lineno == $MAX_LINES) $fh_out = fopen($platform_configuration['dir_temp'].'recalculation.new', 'w');
            if ($lineno >= $MAX_LINES) fwrite($fh_out, $line);
            else {
                // Read line into array
                $line_elements = explode(' ',trim($line));
                if (count($line_elements) <> 3) continue;
                foreach (explode(',',$line_elements[2]) as $id)
                    $queue[$line_elements[0]][$line_elements[1]][$id] = true;
            }
            $lineno++;
        }
        fclose($fh_in);
        // Log
        $text = $lineno.' calculations in file.';
        if ($lineno > $MAX_LINES) $text .= ' '.($lineno-$MAX_LINES).' saved until next time as limit of '.$MAX_LINES.' was exceeded.';
        $job->log('info', $text, $job);
        // Remove read file
        unlink($platform_configuration['dir_temp'].'recalculation.current');
        if ($fh_out !== false) {
            $run_again = true;
            fclose($fh_out);
            // If we exceeded the number of lines, then move the rest of the lines back.
            rename($platform_configuration['dir_temp'].'recalculation.new', $platform_configuration['dir_temp'].'recalculation.current');
        }
        Semaphore::release('platform_calculation_file');
        
        include_once '/var/www/platform/application/test/classes.php';
        
        // Now start on some recalculation
        $flush_rest = false; $flush_count = 0;
        foreach ($queue as $class => $calculations) {
            foreach ($calculations as $calculation => $ids) {
                $ids = array_keys($ids);
                if (! $flush_rest && time() > $starttime + $MAX_RUNTIME) {
                    $fh_out = fopen($platform_configuration['dir_temp'].'recalculation.new', 'w');
                    $flush_rest = true;
                }
                if ($flush_rest) {
                    fwrite($fh_out, $class.' '.$calculation.' '.implode(',',$ids)."\n");
                    $flush_count ++;
                } else {
                    if (! class_exists($class)) continue;
                    $job->log('info', 'Doing calculation \''.$calculation.'\' in class '.$class.' on ('.implode(',', $ids).')', $job);
                    $class::doCalculationOnObjects($calculation, $ids);
                }
            }
        }
        if ($flush_rest) {
            // We need to add the entries we didn't made to the file
            if (! Semaphore::wait('platform_calculation_file')) trigger_error('Could not obtain recalculation file semaphore', E_USER_ERROR);
            if (file_exists($platform_configuration['dir_temp'].'recalculation.current')) {
                $fh_in = fopen($platform_configuration['dir_temp'].'recalculation.current', 'r');
                while ($line = fgets($fh_in)) {
                    fwrite($fh_out, $line);
                }
                fclose($fh_in);
                unlink($platform_configuration['dir_temp'].'recalculation.current');
            }
            fclose($fh_out);
            // Move new file in place of old file
            rename($platform_configuration['dir_temp'].'recalculation.new', $platform_configuration['dir_temp'].'recalculation.current');
            Semaphore::release('platform_calculation_file');
            $job->log('info', 'We flushed '.$flush_count.' lines back in the file, as we didn\'t have time to process them!', $job);
            $run_again = true;
        }
        Semaphore::release('platform_calculation');
        if ($run_again) {
            // Reschedule to run again
            $job = Job::getJob('Platform\\Datarecord', 'calculationJob', Job::FREQUENCY_ONCE);
            $job->save();
        }
    }
    
    
    
    /**
     * Ensure that the database can store this object
     * @param string $type 'global' for global database, otherwise instance database
     * @return boolean True if changes were made to the database
     */
    public static function ensureInDatabase() {
        static::ensureStructure();
        
        $changed = false;
        
        // Check if table exists
        $resultset = self::query("DESCRIBE ".static::$database_table, false);
        
        if (! $resultset) {
            // Build field definitions
            $fielddefinitions = array();
            foreach (static::$structure as $key => $element) {
                // Don't create fields for items we want to store in metadata or
                // which shouldn't be stored in DB
                if ($element['store_in_metadata'] || $element['store_in_database'] === false) continue;
                $fielddefinition = $key.' '.self::getSQLFieldType($element['fieldtype']);
                if ($element['fieldtype'] == self::FIELDTYPE_KEY) $fielddefinition .= ' PRIMARY KEY AUTO_INCREMENT';
                $fielddefinitions[] = $fielddefinition;
            }
            self::query("CREATE TABLE ".static::$database_table." (".implode(',',$fielddefinitions).")");
            $changed = true;
        } else {
            $fields_in_database = array();
            while ($row = fr($resultset)) {
                $fields_in_database[$row['Field']] = $row;
            }
            
            // Check for primary key change
            $keyindatabase = false;
            foreach ($fields_in_database as $field_in_database) {
                if ($field_in_database['Key'] == 'PRI') $keyindatabase = $field_in_database['Field'];
            }
            if ($keyindatabase && $keyindatabase <> static::getKeyField()) {
                //echo 'Primary key change from '.$keyindatabase.' to '.static::getKeyField().' in '. get_called_class();
                // When the primary key changes, we need to rebuild the table.
                self::query('DROP TABLE '.static::$database_table);
                return static::ensureInDatabase($type);
            }

            // Check for new fields
            foreach (static::$structure as $key => $element) {
                if (! isset($fields_in_database[$key]) && ! $element['store_in_metadata'] && $element['store_in_database'] !== false) {
                    // Create it
                    $definition = self::getSQLFieldType($element['fieldtype']);
                    if ($element['fieldtype'] == self::FIELDTYPE_KEY) $definition .= ' PRIMARY KEY AUTO_INCREMENT';
                    self::query('ALTER TABLE '.static::$database_table.' ADD '.$key.' '.$definition);
                    $changed = true;
                    
                    // As this field could have been represented in the metadata
                    // we'll try to copy it from the metadata.
                    $resultset = self::query("SELECT ".static::getKeyField().", ".$key.", metadata FROM ".static::$database_table);
                    while ($row = fr($resultset)) {
                        $metadata = $row['metadata'] ? json_decode($row['metadata'], true) : array();
                        if (isset($metadata[$key])) {
                            // There was something. Write it and unset metadata.
                            $value = $metadata[$key];
                            unset($metadata[$key]);
                            self::query("UPDATE ".static::$database_table." SET metadata = '".esc(json_encode($metadata))."', ".self::getAssignmentForDatabase($key, $value)." WHERE ".static::getKeyField()." = ".$row[static::getKeyField()]);
                        }
                    }
                    
                }
            }
            // Check for changed and removed fields
            foreach ($fields_in_database as $field_in_database) {
                if (! isset(static::$structure[$field_in_database['Field']]) || static::$structure[$field_in_database['Field']]['store_in_metadata'] || static::$structure[$field_in_database['Field']]['store_in_database'] === false) {
                    if (static::$structure[$field_in_database['Field']]['store_in_metadata']) {
                        // We asked to store this field in the metadata instead
                        // so copy it.
                        $resultset = self::query("SELECT ".static::getKeyField().", ".$field_in_database['Field'].", metadata FROM ".static::$database_table);
                        while ($row = fr($resultset)) {
                            $metadata = $row['metadata'] ? json_decode($row['metadata'], true) : array();
                            $metadata[$field_in_database['Field']] = $row[$field_in_database['Field']];
                            self::query("UPDATE ".static::$database_table." SET metadata = '".esc(json_encode($metadata))."' WHERE ".static::getKeyField()." = ".$row[static::getKeyField()]);
                        }
                    }
                    // Field was removed from structure.
                    self::query('ALTER TABLE '.static::$database_table.' DROP '.$field_in_database['Field']);
                    $changed = true;
                    continue;
                }
                $element = static::$structure[$field_in_database['Field']];
                if ($field_in_database['Type'] != mb_substr(mb_strtolower(static::getSQLFieldType($element['fieldtype'])),0, mb_strlen($field_in_database['Type']))) {
                    //echo 'Type '.$fieldindatabase['Type'].' isnt '.mb_strtolower(self::getSQLFieldType($element['fieldtype']));
                    //self::query('ALTER TABLE '.static::$database_table.' CHANGE COLUMN '.$field_in_database['Field'].' '.$field_in_database['Field'].' '.static::getSQLFieldType($element['fieldtype']));
                    self::query('ALTER TABLE '.static::$database_table.' DROP '.$field_in_database['Field']);
                    self::query('ALTER TABLE '.static::$database_table.' ADD '.$field_in_database['Field'].' '.static::getSQLFieldType($element['fieldtype']));
                    $changed = true;
                }
            }
        }
        return $changed;
    }

    /**
     * Ensure that the structure variable is populated.
     */
    protected static function ensureStructure() {
        if (is_array(static::$structure)) return;
        static::buildStructure();
    }

    /**
     * 
     * @param string $keywords Keywords to search for
     * @param string $output Output format. Either "DatarecordCollection" (default)
     * "array" or "autocomplete"
     * @return type
     */
    public static function findByKeywords($keywords, $output = 'DatarecordCollection') {
        $main_field = false; $search_fields = array();
        // Locate search fields
        foreach (static::getStructure() as $field => $definition) {
            if ($definition['searchable']) {
                $search_fields[] = $field;
            }
        }
        if (! count($search_fields)) {
            if ($output == 'DatarecordCollection') return new DatarecordCollection();
            return array();
        }
        $filter = new Filter(get_called_class());
        $parsed_keywords = self::parseKeywords($keywords);
        foreach ($parsed_keywords as $keyword) {
            $previouscondition = false;
            foreach ($search_fields as $fieldname) {
                $condition = new FilterConditionLike($fieldname, $keyword);
                if ($previouscondition) $condition = new FilterConditionOR($condition, $previouscondition);
                $previouscondition = $condition;
            }
            $filter->addCondition($condition);
        }
        $results = $filter->execute();
        if ($output == 'autocomplete') {
            $final_results = array();
            foreach ($results->getAll() as $result) {
                $final_results[] = array('value' => $result->getTitle(), 'label' => $result->getTitle(), 'real_id' => $result->getRawValue(static::getKeyField()));
            }
            return $final_results;
        } elseif ($output == 'array') {
            $final_results = array();
            foreach ($results->getAll() as $result) {
                $final_results[$result->getRawValue(static::getKeyField())] = $result->getTitle();
            }
            return $final_results;
        }
        return $results;
    }
    
    
    /**
     * Force write mode. As another process can have modified the database, this
     * can result in data being overwritten
     */
    public function forceWritemode() {
        $this->lock();
        $this->access_mode = self::MODE_WRITE;
    }
    
    /**
     * Get all objects of this type as an array of title hashed by key
     * @return array
     */
    public static function getAllAsArray() {
        $filter = new Filter(get_called_class());
        $datacollection = $filter->execute();
        foreach ($datacollection->getAll() as $element) {
            $id = $element->getRawValue(static::getKeyField());
            $title = strip_tags($element->getTitle());
            self::$foreign_reference_buffer[get_called_class()][$id] = $title;
            $fieldoptions[$id] = strip_tags($title);
        }
        asort($fieldoptions);
        return $fieldoptions;
    }
    
    /**
     * Get object fields as an array
     * @param array $fields Fields to include (or empty array for all fields)
     * @param int $render_mode Way to render fields
     * @return array
     */
    public function getAsArray($fields = array(), $render_mode = -999) {
        if ($render_mode == -999) $render_mode = $this->default_rendermode;
        if (! count($fields)) $fields = array_keys(static::$structure);
        $result = array();
        foreach ($fields as $field) {
            $result[$field] = $this->getValue($field, $render_mode);
        }
        return $result;
    }
    
    /**
     * Return all fields suitable for insertion into a form
     * @return array
     */
    public function getAsArrayForForm() {
        $result = array();
        foreach (static::$structure as $fieldname => $data) {
            if (($data['invisible'] || $data['readonly']) && $data['fieldtype'] != self::FIELDTYPE_KEY) continue;
            $result[$fieldname] = $this->getValue($fieldname, self::RENDER_FORM);
        }
        return $result;
    }
    
    /**
     * Get a SQL assignment for the given field.
     * @param string $field Field name
     * @param string $value Field value
     * @return string The SQL assignment statement
     */
    private static function getAssignmentForDatabase($field, $value) {
        return $field.'='.self::getFieldForDatabase($field, $value);
    }
    
    /**
     * Get a collection containing the objects gathered by the given SQL
     * @param string $sql
     * @return DatarecordCollection
     */
    public static function getCollectionFromSQL($sql) {
        $collection = new DatarecordCollection();
        $qh = self::query($sql);
        while ($qr = fr($qh)) {
            $object = new static();
            $object->loadFromDatabaseRow($qr);
            $collection->add($object);
        }
        return $collection;
    }
    
    /**
     * Get the field definition for a particular field
     * @param string $field Field name
     * @return array
     */
    public static function getFieldDefinition($field) {
        static::ensureStructure();
        return static::$structure[$field];
    }
    
    /**
     * Get a field coded for the database with proper escaping and encapsulation
     * @param string $field Field name
     * @param string $value Field value
     * @return string The encoded field
     */
    public static function getFieldForDatabase($field, $value) {
        switch (static::$structure[$field]['fieldtype']) {
            case self::FIELDTYPE_INTEGER:
            case self::FIELDTYPE_ENUMERATION:
            case self::FIELDTYPE_FILE:
            case self::FIELDTYPE_REFERENCE_SINGLE:
                return $value === null ? 'NULL' : (int)$value;
            case self::FIELDTYPE_BOOLEAN:
                return $value ? 1 : 0;
            case self::FIELDTYPE_FLOAT:
                return $value === null ? 'NULL' : (double)$value;
            case self::FIELDTYPE_ARRAY:
            case self::FIELDTYPE_OBJECT:
            case self::FIELDTYPE_REFERENCE_MULTIPLE:
                return '\''.esc(json_encode($value)).'\'';
            case self::FIELDTYPE_DATETIME:
            case self::FIELDTYPE_DATE:
                $datetime = new Timestamp($value);
                return $datetime->getTimestamp() !== null ? '\''.$datetime->getTime().'\'' : 'NULL';
            default:
                return '\''.esc($value).'\'';
        }
    }
    
    /**
     * Get a form suitable for editing this object
     * @return \Platform\Form
     */
    public static function getForm() {
        static::ensureStructure();
        $baseclass = strtolower(strpos(get_called_class(), '\\') !== false ? substr(get_called_class(), strrpos(get_called_class(), '\\')+1) : get_called_class());
        // Build form
        $form = new Form($baseclass.'_form');
        $form->setAction('save_'.$baseclass);
        $form->addField(new FieldHidden('', static::getKeyField()));
        $percentleft = 100;
        foreach (static::$structure as $key => $definition) {
            if ($definition['readonly'] || $definition['invisible']) continue;
            $field = static::getFormFieldFromDefinition($key, $definition);
            if ($field === null) continue;
            // Check for additional rendering
            if ($definition['form_size']) $size = (int)$definition['form_size'];
            else $size = 100;
            if ($size < 1 || $size > 100) $size = 100;
            // Check if we need to end a row in progress
            if ($percentleft < $size) {
                $form->addHTML('</div>');
                $percentleft = 100;
            }
            // Check if we need to start a new row
            if ($percentleft == 100) {
                $form->addHTML('<div class="w3-row-padding w3-margin-top">');
            }
            $field->addContainerClass('w3-col');
            $field->addContainerStyle('width: '.$size.'%');
            $percentleft -= $size;

            $form->addField($field);
        }
        // End row in progress
        $form->addHTML('</div>');
        return $form;
    }
    
    /**
     * Return an input field suitable for handling the given field type
     * @param string $name Field name
     * @param array $definition Field definition
     * @return \Platform\Field
     */
    public static function getFormFieldFromDefinition($name, $definition) {
        $options = array();
        if ($definition['required']) $options['required'] = true;
        switch ($definition['fieldtype']) {
            case self::FIELDTYPE_TEXT:
            case self::FIELDTYPE_BIGTEXT:
                return new FieldText($definition['label'], $name, $options);
            case self::FIELDTYPE_PASSWORD:
                return new FieldPassword($definition['label'], $name, $options);
            case self::FIELDTYPE_INTEGER:
                return new FieldNumber($definition['label'], $name, $options);
            case self::FIELDTYPE_FLOAT:
                $options['step'] = 'any';
                return new FieldNumber($definition['label'], $name, $options);
            case self::FIELDTYPE_BOOLEAN:
                return new FieldCheckbox($definition['label'], $name, $options);
            case self::FIELDTYPE_DATETIME:
                return new FieldDatetime($definition['label'], $name, $options);
            case self::FIELDTYPE_DATE:
                return new FieldDate($definition['label'], $name, $options);
            case self::FIELDTYPE_FILE:
                return new FieldFile($definition['label'], $name, $options);
            case self::FIELDTYPE_REFERENCE_SINGLE:
                return new FieldDatarecordcombobox($definition['label'], $name, array('class' => $definition['foreignclass']));
                /*
                $fieldoptions = array();
                // Get possibilities
                $filter = new Filter($definition['foreignclass']);
                $datacollection = $filter->execute();
                foreach ($datacollection->getAll() as $element) {
                    $fieldoptions[$element->getRawValue($definition['foreignclass']::getKeyField())] = strip_tags($element->getTitle());
                }
                asort($fieldoptions);
                $options['options'] = $fieldoptions;
                return new FieldSelect($definition['label'], $name, $options);
                 * /
                 */
            case self::FIELDTYPE_ENUMERATION:
                $options['options'] = $definition['enumeration'];
                return new FieldSelect($definition['label'], $name, $options);
            case self::FIELDTYPE_REFERENCE_MULTIPLE:
                return new FieldMultidatarecordcombobox($definition['label'], $name, array('class' => $definition['foreignclass']));
                /*
                $fieldoptions = array();
                // Get possibilities
                $filter = new Filter($definition['foreignclass']);
                $datacollection = $filter->execute();
                foreach ($datacollection->getAll() as $element) {
                    $fieldoptions[$element->getRawValue($definition['foreignclass']::getKeyField())] = strip_tags($element->getTitle());
                }
                asort($fieldoptions);
                $options['options'] = $fieldoptions;
                return new FieldMulticheckbox($definition['label'], $name, $options);
                 */
        }
        return null;
    }
    
    /**
     * Get a value suitable for a form from this object
     * @param string $field Field name
     * @return string Text string
     */
    public function getFormValue($field) {
        if (! isset(static::$structure[$field])) return null;
        switch (static::$structure[$field]['fieldtype']) {
            case self::FIELDTYPE_PASSWORD:
                return $this->getRawValue($field) ? 'XXXXXX' : '';
            case self::FIELDTYPE_REFERENCE_SINGLE:
                return array('id' => $this->getRawValue($field), 'visual' => $this->getTextValue($field));
            case self::FIELDTYPE_FILE:
            case self::FIELDTYPE_ENUMERATION:
                return $this->getRawValue($field);
            case self::FIELDTYPE_REFERENCE_MULTIPLE:
                // Bail of no values
                if (! count($this->getRawValue($field))) return array();
                // We need to retrieve all the referred values
                $class = static::$structure[$field]['foreignclass'];
                $filter = new Filter($class);
                $filter->addCondition(new FilterConditionOneOf($class::getKeyField(), $this->getRawValue($field)));
                $values = array();
                foreach ($filter->execute()->getAll() as $foreignobject) {
                    $values[] = array('id' => $foreignobject->getRawValue($class::getKeyField()), 'visual' => $foreignobject->getTitle());
                }
                return $values;
            case self::FIELDTYPE_DATETIME:
                return str_replace(' ', 'T', $this->getRawValue($field)->getReadable('Y-m-d H:i'));
            case self::FIELDTYPE_DATE:
                return $this->getRawValue($field)->getReadable('Y-m-d');
            default:
                return $this->getTextValue($field);
        }
    }
    
    /**
     * Get a fully formatted value from this object
     * @param string $field Field name
     * @return string Formatted string
     */
    public function getFullValue($field) {
        if (! isset(static::$structure[$field])) return null;
        switch (static::$structure[$field]['fieldtype']) {
            case self::FIELDTYPE_ARRAY:
                return implode(', ', $this->getRawValue($field));
            case self::FIELDTYPE_REFERENCE_SINGLE:
            case self::FIELDTYPE_REFERENCE_MULTIPLE:
            case self::FIELDTYPE_FILE:
                $result = $this->resolveForeignReferences($field);
                sort($result);
                return implode(', ', $result);
            case self::FIELDTYPE_ENUMERATION:
                return $this->getRawValue($field) ? static::$structure[$field]['enumeration'][$this->getRawValue($field)] : '';
            case self::FIELDTYPE_DATETIME:
                return $this->getRawValue($field)->getReadable();
            case self::FIELDTYPE_DATE:
                return $this->getRawValue($field)->getReadable('d-m-Y');
            case self::FIELDTYPE_PASSWORD:
                return $this->getRawValue($field) ? '---' : '';
            case self::FIELDTYPE_TEXT:
            case self::FIELDTYPE_BIGTEXT:
                return str_replace('\\n', '<br>', htmlentities($this->getRawValue($field)));
            default:
                return $this->getRawValue($field);
        }
    }
    
    /**
     * Get the key field of this datarecord.
     * @return boolean|string Name of key field or false if no key field was
     * detected.
     */
    public static function getKeyField() {
        if (static::$key_field === false) {
            static::ensureStructure();
        
            foreach (static::$structure as $key => $element) {
                if ($element['fieldtype'] == self::FIELDTYPE_KEY) {
                    static::$key_field = $key;
                    break;
                }
            }
        }
        return static::$key_field;
    }
    
    /**
     * Get the location (global or instance) of this object type
     * @return int
     */
    public static function getLocation() {
        return static::$location;
    }
    
    /**
     * Get the name of a lockfile appropriate for this object.
     * @return string Lock file name
     */
    protected function getLockFileName() {
        if (! static::$database_table) trigger_error('Cannot determine lock file name without table name.', E_USER_ERROR);
        return self::getLockFileNameByTableAndID(static::$database_table, $this->values[static::getKeyField()]);
    }
    
    /**
     * Get a lock file name for an object in the specific table, with the given ID
     * @param string $database_table Table name
     * @param int $id Key
     * @return string Lock file name
     */
    protected static function getLockFileNameByTableAndID($database_table, $id) {
        return $database_table.((int)$id);
    }
    
    /**
     * Get a simple class name (without namespace and in lowercase) for this class
     * @return string Simple name
     */
    public static function getClassName() {
        $class = strtolower(get_called_class());
        if (strpos($class, '\\')) $class = substr($class,strrpos($class,'\\')+1);
        return $class;
    }

    /**
     * Get the readable name of this object type. Defaults to class name if
     * no name is set.
     * @return string
     */
    public static function getObjectName() {
        return static::$object_name ?: static::getClassName();
    }
    
    /**
     * Get titles of all objects referring this object
     * @return array
     */
    public function getReferringObjectTitles() {
        // Find all objects referring this
        $referring_titles = array();
        foreach (static::$referring_classes as $referring_class) {
            // Build a filter to find all referers
            $referer_found = false;
            $filter = new Filter($referring_class);
            foreach ($referring_class::getStructure() as $key => $definition) {
                if (in_array($definition['fieldtype'], array(self::FIELDTYPE_REFERENCE_SINGLE, self::FIELDTYPE_REFERENCE_MULTIPLE)) && $definition['foreignclass'] == get_called_class()) {
                    $filter->addConditionOR(new FilterConditionMatch($key, $this->getRawValue($this->getKeyField())));
                    $referer_found = true;
                }
            }
            if (! $referer_found) continue;
            // Get all objects referring this
            $referring_objects = $filter->execute();
            foreach ($referring_objects->getAll() as $referring_object) $referring_titles[] = $referring_object->getTitle();
        }
        return $referring_titles;
    }
    
    /**
     * Override to return a full formatted title of this object
     * @return string
     */
    public function getTitle() {
        return get_called_class().' (#'.$this->getValue(static::getKeyField(), self::RENDER_RAW).')';
    }
    
    /**
     * Get the title of an object of this type by ID.
     * @param int $id Object id
     * @return string Title
     */
    public static function getTitleById($id) {
        $class = get_called_class();
        // Try the buffer
        if (! isset(self::$foreign_reference_buffer[$class][$id])) {
            // Resolve (and add to buffer)
            $object = new $class();
            $object->loadForRead($id);
            self::$foreign_reference_buffer[$class][$id] = $object->getTitle();
        }
        return self::$foreign_reference_buffer[$class][$id];        
    }
    
    /**
     * Convert an internal field type to a MySQL field type
     * @param int $fieldtype Internal field type
     * @return string MySQL field type
     */
    private static function getSQLFieldType($fieldtype) {
        switch ($fieldtype) {
            case self::FIELDTYPE_DATE:
            case self::FIELDTYPE_DATETIME:
                return 'DATETIME';
            case self::FIELDTYPE_ARRAY:
            case self::FIELDTYPE_OBJECT:
            case self::FIELDTYPE_REFERENCE_MULTIPLE:
            case self::FIELDTYPE_BIGTEXT:
                return 'MEDIUMTEXT';
            case self::FIELDTYPE_INTEGER:
            case self::FIELDTYPE_KEY:
            case self::FIELDTYPE_FILE:
            case self::FIELDTYPE_REFERENCE_SINGLE:
            case self::FIELDTYPE_ENUMERATION:
                return 'INT(11)';
            case self::FIELDTYPE_BOOLEAN:
                return 'INT(1)';
            case self::FIELDTYPE_FLOAT:
                return 'DOUBLE';
            default:
                return 'VARCHAR(255) NOT NULL';
        }
    }
    
    /**
     * Get the structure of this object
     * @return array
     */
    public static function getStructure() {
        static::ensureStructure();
        return static::$structure;
    }

    /**
     * Read a raw value from the object
     * @param string $field Field name
     * @return mixed Value
     */
    public function getRawValue($field) {
        if (! isset(static::$structure[$field])) return null;
        switch (static::$structure[$field]['fieldtype']) {
            case self::FIELDTYPE_ARRAY:
                return is_array($this->values[$field]) ? $this->values[$field] : array();
            case self::FIELDTYPE_DATETIME:
            case self::FIELDTYPE_DATE:
                return $this->values[$field] instanceof Timestamp ? $this->values[$field] : new Timestamp();
            default:
                return $this->values[$field];
        }
    }
    
    /**
     * Get database table name
     * @return string
     */
    public static function getDatabaseTable() {
        return static::$database_table;
    }
    
    /**
     * Get a readable text value from this object
     * @param string $field Field name
     * @return string Text string
     */
    public function getTextValue($field) {
        if (! isset(static::$structure[$field])) return null;
        switch (static::$structure[$field]['fieldtype']) {
            default:
                return strip_tags(html_entity_decode($this->getFullValue($field)));
        }
    }

    /**
     * Get a value from the object
     * @param string $field Field name
     * @param int $rendermode Render mode to use when returning value
     * @return mixed Value
     */
    public function getValue($field, $rendermode = -999) {
        if ($rendermode == -999) $rendermode = $this->default_rendermode;
        switch ($rendermode) {
            case self::RENDER_RAW:
                return $this->getRawValue($field);
            case self::RENDER_TEXT:
                return $this->getTextValue($field);
            case self::RENDER_FULL:
                return $this->getFullValue($field);
            case self::RENDER_FORM:
                return $this->getFormValue($field);
        }
    }
    
    /**
     * Get fields to use in a Table
     * @return array
     */
    public static function getTableFields() {
        static::ensureStructure();
        $result = array();
        foreach (static::$structure as $key => $element) {
            if ($element['invisible'] || $element['table'] == self::COLUMN_UNSELECTABLE) continue;
            $result[] = $key;
        }
        return $result;
    }
    
    /**
     * Check if this object have changed since it was loaded from the database.
     * @return boolean
     */
    public function haveChanged() {
        foreach (static::$structure as $key => $definition) {
            switch ($definition['fieldtype']) {
                case self::FIELDTYPE_ARRAY:
                case self::FIELDTYPE_REFERENCE_MULTIPLE:
                    if (array_diff($this->values[$key], $this->values_on_load[$key]) || array_diff($this->values_on_load[$key], $this->values[$key])) return true;
                    break;
                case self::FIELDTYPE_DATETIME:
                    if (! $this->values[$key]->equalTo($this->values_on_load[$key])) return true;
                    break;
                default:
                    if ($this->values[$key] !== $this->values_on_load[$key]) return true;
                    break;
            }
        }
        return false;
    }
    
    
    /**
     * Determines if this is stored in the database.
     * @return boolean True if stored in database
     */
    public function isInDatabase() {
        return $this->values[static::getKeyField()] > 0;
    }
    
    /**
     * Load an object from the database for reading.
     * @param int $id Object ID
     */
    public function loadForRead($id) {
        // Check if already in write mode
        if ($this->access_mode == self::MODE_WRITE) {
            // Unlock
            $this->unlock();
        }
        $this->access_mode = self::MODE_READ;
        $this->loadFromDatabase($id);
    }
    
    /**
     * Load an object from the database for writing.
     * @param int $id Object ID
     */
    public function loadForWrite($id) {
        // Spoof id field
        $this->values[static::getKeyField()] = $id;
        $this->lock();
        $this->access_mode = self::MODE_WRITE;
        if (! $this->loadFromDatabase($id)) {
            // Unlock if we couldn't read
            $this->values[static::getKeyField()] = 0;
            $this->unlock();
        }
    }
    
    /**
     * Load an object from the database.
     * @param int $id Object ID
     * @return boolean True if an object was loaded
     */
    private function loadFromDatabase($id) {
        $result = self::query("SELECT * FROM ".static::$database_table." WHERE ".static::getKeyField()." = ".((int)$id));
        $row = fr($result);
        if ($row !== false) {
            $this->parseFromDatabaseRow($row);
            $this->unpackMetadata();
            $this->values_on_load = $this->values;
            return true;
        }
        return false;
    }

    /**
     * "Load" an object from passed database data. It will always be in readonly
     * mode
     * @param array $databaserow Database result from database
     */
    public function loadFromDatabaseRow($databaserow) {
        // Check if already in write mode
        if ($this->access_mode == self::MODE_WRITE) {
            // Unlock
            $this->unlock();
        }
        $this->access_mode = self::MODE_READ;
        $this->parseFromDatabaseRow($databaserow);
        $this->unpackMetadata();
        $this->values_on_load = $this->values;
    }    
    
    /**
     * Lock this object
     */
    public function lock() {
        if (!Semaphore::wait($this->getLockFileName())) {
            trigger_error('Failed to lock object '.__CLASS__.' ('.$this->getValue($this->getKeyField()).') within reasonable time', E_USER_ERROR);
        }
    }    
    
    /**
     * Pack metadata according to structure definition
     */
    private function packMetadata() {
        $metadata = array();
        foreach (static::$structure as $key => $definition) {
            if (! $definition['store_in_metadata']) continue;
            $metadata[$key] = $this->values[$key];
        }
        $this->setValue('metadata', $metadata);
    }
    
    /**
     * Parse data fields from a database row
     * @param array $databaserow The database row
     */
    private function parseFromDatabaseRow($databaserow) {
        $this->values = array();
        if (! is_array($databaserow)) return;
        foreach ($databaserow as $key => $value) {
            switch (static::$structure[$key]['fieldtype']) {
                case self::FIELDTYPE_KEY:
                case self::FIELDTYPE_PASSWORD:
                    $this->values[$key] = $value;
                    break;
                case self::FIELDTYPE_ARRAY:
                case self::FIELDTYPE_OBJECT:
                case self::FIELDTYPE_REFERENCE_MULTIPLE:
                    $this->setValue($key, json_decode($value, true));
                    break;
                default:
                    $this->setValue($key, $value);
            }
        }
    }
    
    /**
     * Parse keywords by splitting into arrays at space, but "preserving phrases"
     * @param string $keywords String to split
     * @return array Individual words and phrases.
     */
    private static function parseKeywords($keywords) {
        $parsed_keywords = array(); $inside = false; $wordbuffer = '';
        for ($i = 0; $i < strlen($keywords); $i++) {
            $character = substr($keywords,$i,1);
            if ($character == '"') $inside = ! $inside;
            elseif ($character == ' ' && ! $inside) {
                $word = trim($wordbuffer);
                if ($word) $parsed_keywords[] = $word;
                $wordbuffer = '';
            } else $wordbuffer .= $character;
        }
        $word = trim($wordbuffer);
        if ($word) $parsed_keywords[] = $word;
        return $parsed_keywords;
    }

    /**
     * This function will populate the foreign reference buffer trying to find
     * all relevant data in a single pass.
     * @param string $class Class of the foreign object
     */
    private function populateForeignReferenceBuffer($class) {
        // Get attached collection or create a collection of just this
        $datarecords = $this->collection === false ? array($this) : $this->collection->getAll();
        
        // Locate all interesting ids
        $ids = array();
        foreach (static::$structure as $key => $definition) {
            if ($definition['foreignclass'] != $class && !($definition['fieldtype'] == self::FIELDTYPE_FILE && $class == 'Platform\\File')) continue;
            foreach ($datarecords as $datarecord) {
                $values = $datarecord->getRawValue($key);
                if (! is_array($values)) $values = array($values);
                foreach ($values as $value) {
                    if ($value && ! in_array($value, $ids)) $ids[] = $value;
                }
            }
        }
        $missing = array();
        foreach ($ids as $id) {
            if (! isset(self::$foreign_reference_buffer[$class][$id])) $missing[] = $id;
        }
        // Fill buffer with all missing instances
        if (count($missing)) {
            $qh = self::query("SELECT * FROM ".$class::$database_table." WHERE ".$class::getKeyField()." IN (".implode(',',$missing).")");
            while ($qr = fr($qh)) {
                $foreign_datarecord = new $class();
                $foreign_datarecord->loadFromDatabaseRow($qr);
                self::$foreign_reference_buffer[$class][$qr[$class::getKeyField()]] = $foreign_datarecord->getTitle();
            }
        }
    }
    
    /**
     * Purge all objects from the database. Please note that this doesn't
     * obtain locks for the objects
     */
    public static function purge() {
        self::query("DELETE FROM ".static::$database_table);
        self::query("ALTER TABLE ".static::$database_table." AUTO_INCREMENT = 1");
    }
    
    /**
     * Make a suitable database query for this objects location
     * @param string $query Query
     * @param boolean $failonerror 
     * @return array
     */
    public static function query($query, $failonerror = true) {
        if (static::$location == self::LOCATION_GLOBAL) return Database::globalQuery ($query, $failonerror);
        else return Database::instanceQuery ($query,$failonerror);
    }
    
    /**
     * Reloads a current object for writing.
     */
    public function reloadForWrite() {
        if ($this->access_mode == self::MODE_WRITE) return;
        // Lock
        $this->lock();
        $this->access_mode = self::MODE_WRITE;
        if (! $this->loadFromDatabase($this->getRawValue($this->getKeyField()))) {
            // Unlock if we couldn't read
            $this->values[static::getKeyField()] = 0;
            $this->unlock();
            return false;
        }
        return true;
    }
    
    /**
     * Render an edit complex for this object type showing a table with all objects,
     * and an option to create, edit, delete them.
     * @param array $parameters Additional params to the table
     */
    public static function renderEditComplex($parameters = array()) {
        // Get base class name
        $class = static::getClassName();
        // Get object name
        $name = strtolower(static::getObjectName());
        
        // Create table
        $datarecord_table = new Table($class.'_table');
        $datarecord_table->setDefinitionFromDatarecord(get_called_class());
        $datarecord_table->setOption('ajaxURL', '/Platform/Datarecord/php/table_datarecord.php?class='.get_called_class());
        $datarecord_table->setOption('placeholder', 'No '.$name);
        
        if ($parameters['filter']) $parameters['table']['filter'] = $parameters['filter'];
        
        if (is_array($parameters['table']))
            foreach ($parameters['table'] as $key => $parameter) {
                $datarecord_table->setOption($key, $parameter);
            }
        
        // Get form
        $form = static::getForm();
        
        if (is_callable($parameters['form_function'])) call_user_func($parameters['form_function'], $form);
        
        echo '<div class="'.Design::getClass('datarecord_editcomplex', 'platform_render_edit_complex').'" data-name="'.$name.'" data-shortclass="'.$class.'" data-class="'.get_called_class().'">';
        
        $menu = array(
            $class.'_new_button' => 'Create new '.$name,
            $class.'_edit_button' => 'Edit selected '.$name,
            $class.'_delete_button' => 'Delete selected '.$name,
            $class.'_column_select_button' => 'Select columns'
        );
        $datarecord_menu = new Menu($menu);
        
        $datarecord_menu->renderAsMenubutton();
        
        $datarecord_table->renderTable();
        
        echo '</div>';

        echo '<div id="'.$class.'_edit_dialog" title="Edit '.$name.'" class="platform_invisible">';
        $form->render();
        echo '</div>';
        
        $fields = static::getTableFields();

        $table_configuration = UserProperty::getPropertyForCurrentUser('table_configuration', $class.'_table');
        if (! is_array($table_configuration)) {
            // Build default set
            $table_configuration = array();
            foreach ($fields as $field) {
                if (static::$structure[$field]['table'] == Datarecord::COLUMN_DEFAULTSHOWN) $table_configuration[$field] = array('width' => 0);
            }
        }
        
        echo '<div id="'.$class.'_column_dialog" title="'.$name.' columns" class="platform_invisible">';
        $datarecord_table->renderColumnSelector();
        echo '</div>';
        
    }
    
    /**
     * Render an integrity check of this class.
     */
    public static function renderIntegrityCheck() {
        echo '<h1>'.get_called_class().'</h1>';
        $errors = array();
        $warnings = array();
        // Ensure newest version and test that we don't upgrade the database in excess.
        static::ensureInDatabase();
        $changed = static::ensureInDatabase();
        if ($changed) $errors[] = 'Database was changed even though there should be no changes. This is probably a problem with Platform.';
        
        // Check references
        foreach (static::getStructure() as $field => $definition) {
            switch ($definition['fieldtype']) {
                case self::FIELDTYPE_REFERENCE_SINGLE:
                case self::FIELDTYPE_REFERENCE_MULTIPLE:
                    if (! $definition['foreignclass']) $errors[] = $field.': Reference without foreign class';
                    elseif (!class_exists($definition['foreignclass'])) $errors[] = $field.': Reference to class which doesn\'t exists.';
                    elseif (! in_array(get_called_class(), $definition['foreignclass']::$referring_classes)) $errors[] = 'Remote class '.$definition['foreignclass'].' doesn\'t list this as a referer, even though we refer in field: <i>'.$field.'</i>';
                    break;
            }
        }
        // Check foreign classes
        foreach (static::$referring_classes as $foreign_class) {
            if (! class_exists($foreign_class)) $errors[] = 'Have <i>'.$foreign_class.'</i> as a foreign class, but the class doesn\'t exist.';
            else {
                $hit = false;
                foreach ($foreign_class::getStructure() as $field => $definition) {
                    if ($definition['foreignclass'] == get_called_class()) {
                        $hit = true;
                        break;
                    }
                }
                if (! $hit) $errors[] = 'Have <i>'.$foreign_class.'</i> as a foreign class, but that class doesn\'t refer this class.';
            }
        }
        echo '<ul>';
        if (! count($errors) && ! count($warnings)) echo '<li><span style="color: green;">All OK</span>';
        foreach ($errors as $error) echo '<li><span style="color: red;">'.$error.'</span>';
        foreach ($warnings as $warning) echo '<li><span style="color: orange;">'.$error.'</span>';
        echo '</ul>';
    }

    /**
     * Request a calculation
     * @param string $calculation Calculation to request
     * @return boolean True if calculation was requested.
     */
    public function requestCalculation($calculation) {
        if (! $this->isInDatabase()) return false;
        self::$requested_calculation_buffer[get_called_class()][$calculation][$this->getRawValue($this->getKeyField())] = true;
        return true;
    }
    
    /**
     * Resolve several foreign references (using caching)
     * @param string $field Name of field refering the foreign class
     * @return array Foreign object titles hashed by ids
     */
    public function resolveForeignReferences($field) {
        if (! in_array(static::$structure[$field]['fieldtype'], array(self::FIELDTYPE_REFERENCE_SINGLE, self::FIELDTYPE_REFERENCE_MULTIPLE, self::FIELDTYPE_FILE))) trigger_error('Tried to resolve a foreign reference on an incompatible field.', E_USER_ERROR);
        if (static::$structure[$field]['fieldtype'] == self::FIELDTYPE_FILE) {
            $class = 'Platform\\File';
        } else {
            $class = static::$structure[$field]['foreignclass'];
        }
        $ids = $this->getRawValue($field);
        if (! is_array($ids)) $ids = array($ids);
        $missing = array();
        foreach ($ids as $id) {
            if (! isset(self::$foreign_reference_buffer[$class][$id])) $missing[] = $id;
        }
        // Fill buffer with all missing instances
        if (count($missing)) $this->populateForeignReferenceBuffer ($class);
        // Now prepare result
        $result = array();
        foreach ($ids as $id) {
            $result[$id] = self::$foreign_reference_buffer[$class][$id];
        }
        return $result;
    }
    
    /**
     * Save the object to the database, if it have changed.
     * @param boolean $force_save Set true to always save object
     * @param boolean $keep_open_for_write Set to true to keep object open for write after saving
     * @return boolean True if we actually saved the object
     */
    public function save($force_save = false, $keep_open_for_write = false) {
        if ($this->access_mode != self::MODE_WRITE) trigger_error('Tried to save object '.static::$database_table.' in read mode', E_USER_ERROR);
        
        $change = true;
        if (! $force_save && $this->isInDatabase()) {
            // Check if anything have changed?
            $change = false;
            foreach (static::$structure as $key => $definition) {
                if ($definition['store_in_database'] !== false && $this->values[$key] != $this->values_on_load[$key]) {
                    $change = true;
                    break;
                }
            }
            if (! $change) {
                if (! $keep_open_for_write) $this->unlock();
                return false;
            }
        }
        // See if we should calculate anything
        if ($change) {
            foreach(static::$structure as $key => $definition) {
                if ($definition['calculations']) {
                    if (! is_array($definition['calculations'])) $definition['calculations'] = array($definition['calculations']);
                    $foreign_ids = array();
                    if ($definition['fieldtype'] == self::FIELDTYPE_REFERENCE_SINGLE) {
                        $foreign_ids[] = $this->values[$key];
                        $foreign_ids[] = $this->values_on_load[$key];
                    } elseif ($definition['fieldtype'] == self::FIELDTYPE_REFERENCE_MULTIPLE) {
                        if (is_array($this->values[$key])) foreach ($this->values[$key] as $v) $foreign_ids[] = $v;
                        if (is_array($this->values_on_load[$key])) foreach ($this->values_on_load[$key] as $v) $foreign_ids[] = $v;
                    }
                    $foreign_ids = array_unique($foreign_ids);
                    foreach ($definition['calculations'] as $calculation) {
                        foreach ($foreign_ids as $foreign_id) {
                            if (! $foreign_id) continue;
                            self::$requested_calculation_buffer[$definition['foreignclass']][$calculation][$foreign_id] = true;
                        }
                    }
                }
            }
        }
        
        $this->packMetadata();
        $this->setValue('change_date', new Timestamp('now'));
        if ($this->isInDatabase()) {
            // Prepare update.
            $fielddefinitions = array();
            foreach (static::$structure as $key => $definition) {
                if (! $definition['store_in_metadata'] && $definition['store_in_database'] !== false) {
                    $fielddefinitions[] = self::getAssignmentForDatabase($key, $this->values[$key]);
                }
            }
            $sql = 'UPDATE '.static::$database_table.' SET '.implode(',',$fielddefinitions).' WHERE '.static::getKeyField().' = '.$this->values[static::getKeyField()];
            self::query($sql);
            $this->values_on_load = $this->values;
            if (! $keep_open_for_write) $this->unlock();
        } else {
            $this->setValue('create_date', new Timestamp('now'));
            $fieldlist = array(); $fieldvalues = array();
            foreach (static::$structure as $key => $definition) {
                if (! $definition['store_in_metadata'] && $definition['store_in_database'] !== false) {
                    $fieldlist[] = $key; 
                    $fieldvalues[] = ($definition['fieldtype'] == self::FIELDTYPE_KEY) ? 'NULL' : self::getFieldForDatabase($key, $this->values[$key]);
                }
            }
            $sql = 'INSERT INTO '.static::$database_table.' ('.implode(',',$fieldlist).') VALUES ('.implode(',',$fieldvalues).')';
            self::query($sql);
            $this->values_on_load = $this->values;
            $this->unlock();
            $this->values[static::getKeyField()] = static::$location == self::LOCATION_GLOBAL ? Database::globalGetInsertedKey() : Database::instanceGetInsertedKey();
            if ($keep_open_for_write) {
                // Lock the new object
                $this->lock();
                $this->forceWritemode();
            }
        }
        // Update reference buffer
        self::$foreign_reference_buffer[get_called_class()][$this->values[static::getKeyField()]] = $this->getTitle();
        return true;
    }
    
    /**
     * Save all requested calculations
     */
    public static function saveRequestedCalculations() {
        global $platform_configuration;
        if (! count(self::$requested_calculation_buffer)) return;
        if (! Semaphore::wait('platform_calculation_file')) trigger_error('Could not obtain recalculation file semaphore', E_USER_ERROR);
        $fh = fopen($platform_configuration['dir_temp'].'recalculation.current', 'a');
        foreach (self::$requested_calculation_buffer as $class => $calculations) {
            foreach ($calculations as $calculation => $ids) {
                fwrite($fh, $class.' '.$calculation.' '.implode(',',array_keys($ids))."\n");
            }
        }
        fclose($fh);
        Semaphore::release('platform_calculation_file');
        $job = Job::getJob('Platform\\Datarecord', 'calculationJob', Job::FREQUENCY_ONCE, false, 10, 15);
        $job->save();
    }
    
    /**
     * Set default render mode, when returning values for this object
     * @param int $rendermode
     */
    public function setDefaultRenderMode($rendermode) {
        if (in_array($rendermode, array(self::RENDER_RAW, self::RENDER_TEXT, self::RENDER_FULL, self::RENDER_FORM))) $this->default_rendermode = $rendermode;
    }
    
    /**
     * Set values to object as defined in an array
     * @param array $array Field values hashed by field names
     */
    public function setFromArray($array) {
        if (! is_array($array)) return;
        foreach ($array as $key => $value) $this->setValue ($key, $value);
    }
    
    /**
     * Set a value in the object
     * @param string $field Field name
     * @param mixed $value Field value
     */
    public function setValue($field, $value) {
        global $platform_configuration;
        if (! isset(static::$structure[$field])) trigger_error('Tried setting invalid field: '.$field, E_USER_ERROR);
        switch (static::$structure[$field]['fieldtype']) {
            case self::FIELDTYPE_PASSWORD:
                $this->values[$field] = $value ? md5($value.$platform_configuration['password_salt']) : '';
                break;
            case self::FIELDTYPE_TEXT:
            case self::FIELDTYPE_OBJECT:
            case self::FIELDTYPE_BIGTEXT:
                $this->values[$field] = $value;
                break;
            case self::FIELDTYPE_ENUMERATION:
                // Fail if trying to set invalid value.
                if ($value && ! isset(static::$structure[$field]['enumeration'][$value])) trigger_error('Tried to set invalid ENUMERATION value '.$value.' in field: '.$field, E_USER_ERROR);
                $this->values[$field] = (int)$value;
                break;
            case self::FIELDTYPE_INTEGER:
                $this->values[$field] = is_numeric($value) ? (int)$value : null;
                break;
            case self::FIELDTYPE_BOOLEAN:
                $this->values[$field] = $value == 1;
                break;
            case self::FIELDTYPE_FLOAT:
                $this->values[$field] = is_numeric($value) ? (double)$value : null;
                break;
            case self::FIELDTYPE_REFERENCE_SINGLE:
                if (is_object($value) && get_class($value) == static::$structure[$field]['foreignclass']) {
                    // An object of the desired class was passed. Extract ID and set it.
                    $this->values[$field] = $value->getValue($value->getKeyField());
                } else {
                    if (is_object($value) && $value instanceof Datarecord) trigger_error('Expected value of type '.static::$structure[$field]['foreignclass'].' but got '.get_class($value), E_USER_ERROR);
                    // We expect an ID
                    $this->values[$field] = is_numeric($value) ? (int)$value : null;
                }
                break;
            case self::FIELDTYPE_REFERENCE_MULTIPLE:
                if (! is_array($value)) $value = array($value);
                $final = array();
                foreach ($value as $v) {
                    if (is_numeric($v)) $final[] = $v;
                    elseif (get_class($v) == static::$structure[$field]['foreignclass']) $final[] = $v->getValue($value->getKeyField());
                    elseif ($v instanceof Datarecord) trigger_error('Expected value of type '.static::$structure[$field]['foreignclass'].' but got '.get_class($v), E_USER_ERROR);
                }
                $this->values[$field] = $final;
                break;
            case self::FIELDTYPE_ARRAY:
                $this->values[$field] = is_array($value) ? $value : array();
                break;
            case self::FIELDTYPE_DATETIME:
            case self::FIELDTYPE_DATE:
                $this->values[$field] = new Timestamp($value);
                break;
            case self::FIELDTYPE_FILE:
                if (is_array($value)) {
                    // Form style input
                    // If nothing is changed, then keep status quo
                    if (! $value['status']) break;
                    if ($value['status'] == 'removed') {
                        // The file was removed.
                        $file = new File();
                        $file->loadForWrite($this->values[$field]);
                        if ($file->isInDatabase()) {
                            $file->delete();
                        }
                        $this->values[$field] = null;
                        break;
                    }
                    // status="changed" assumed

                    // Check if we have an attached file object
                    if ($this->values[$field]) {
                        // Get object
                        $file = new File();
                        $file->loadForWrite($this->values[$field]);
                        $file->filename = $value['original_file'];
                        $file->folder = static::$structure[$field]['folder'];
                        $file->mimetype = $value['mimetype'];
                        $folder = File::getFullFolderPath('temp');
                        $file->attachFile($folder.$value['temp_file']);
                        $file->save();
                    } else {
                        // We need to create a new file
                        $file = new File();
                        $file->filename = $value['original_file'];
                        $file->folder = static::$structure[$field]['folder'];
                        $file->mimetype = $value['mimetype'];
                        $folder = File::getFullFolderPath('temp');
                        $file->attachFile($folder.$value['temp_file']);
                        $file->save();
                        $this->values[$field] = $file->file_id;
                    }
                } elseif ($value instanceof File) {
                    $this->values[$field] = $value->file_id;
                } else {
                    $this->values[$field] = $value;
                }
                break;
        }
    }

    /**
     * Unlocks this object (if it is locked)
     */
    public function unlock() {
        Semaphore::release($this->getLockFileName());
        $this->access_mode = self::MODE_READ;
    }
    
    /**
     * Unpack metadata
     */
    private function unpackMetadata() {
        if (is_array($this->values['metadata'])) {
            foreach ($this->values['metadata'] as $key => $value) {
                $this->setValue($key, $value);
            }
        }
    }
    

    const FIELDTYPE_TEXT = 1;
    const FIELDTYPE_INTEGER = 2;
    const FIELDTYPE_FLOAT = 3;
    const FIELDTYPE_BOOLEAN = 4;
    const FIELDTYPE_BIGTEXT = 5;
    
    const FIELDTYPE_DATETIME = 10;
    const FIELDTYPE_DATE = 11;
    
    const FIELDTYPE_ARRAY = 100;
    const FIELDTYPE_OBJECT = 103;
    const FIELDTYPE_ENUMERATION = 101;
    
    const FIELDTYPE_PASSWORD = 300;
    
    const FIELDTYPE_FILE = 400;
    
    const FIELDTYPE_REFERENCE_SINGLE = 500;
    const FIELDTYPE_REFERENCE_MULTIPLE = 501;
    
    const FIELDTYPE_KEY = 9999;
}