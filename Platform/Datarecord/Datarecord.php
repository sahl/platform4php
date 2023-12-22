<?php
namespace Platform\Datarecord;
/**
 * Class for having objects which are saved in a database and can easily be manipulated.
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=record_class
 */

use Platform\Filter\ConditionMatch;
use Platform\Filter\Filter;
use Platform\Form\Form;
use Platform\Form\Layout;
use Platform\Security\Accesstoken;
use Platform\UI\EditComplex;
use Platform\Utilities\Database;
use Platform\Utilities\Log;
use Platform\Utilities\Semaphore;
use Platform\Utilities\Time;
use Platform\Utilities\Translation;

class Datarecord implements DatarecordReferable {

    const DELETE_MODE_DELETE = 0;
    const DELETE_MODE_EMPTY = 1;
    const DELETE_MODE_MARK = 2;
    
    // Delete strategies
    const DELETE_STRATEGY_DO_NOTHING = 0;
    const DELETE_STRATEGY_BLOCK = 1;
    const DELETE_STRATEGY_PURGE_REFERERS = 2;
    
    // Database change strategies
    const DATABASE_CHANGE_ALTER = 1;
    const DATABASE_CHANGE_DROP_CREATE = 2;
    
    // Object locations
    const LOCATION_GLOBAL = 0;
    const LOCATION_INSTANCE = 1;

    // Read/write mode
    const MODE_READ = 0;
    const MODE_WRITE = 1;

    /**
     * Reference to a collection, that this is a part of
     * @var Collection 
     */
    public $collection = null;

    /**
     * Indicate what mode this object is in
     * @var int
     */
    protected $access_mode = self::MODE_WRITE;
    
    /**
     * Indicate if elements of this type is allowed to be copied
     * @var bool
     */
    protected static $allow_copy = true;
    
    /**
     * Database table to store records of this type.
     * @var string
     */
    protected static $database_table = '';
    
    /**
     * Store the delete mode for this object
     * @var int
     */
    protected static $delete_mode = self::DELETE_MODE_DELETE;
    
    /**
     * Strategy for changing existing database fields
     * @var int
     */
    protected static $database_change_strategy = self::DATABASE_CHANGE_ALTER;
    
    /**
     * Set a delete strategy for this object
     * @var int
     */
    protected static $delete_strategy = self::DELETE_STRATEGY_BLOCK;

    /**
     * Names of all classes depending on this class
     * @var type 
     */
    protected static $depending_classes = array();
    
    /**
     * Used to indicate if this object is in the database.
     * @var type
     */
    protected $is_in_database = false;

    /**
     * Name of key field in database
     * @var string 
     */
    protected static $key_field = false;
    
    /**
     * Shorthand for form layout
     * @var array
     */
    protected static $layout = [];

    /**
     * Indicate the location of this record
     * @var type 
     */
    protected static $location = self::LOCATION_GLOBAL;

    /**
     * Name of semaphore lock to lock this object, or false if not locked
     * @var bool|string
     */
    protected $lockname = false;
    
    /**
     * Indicate if we should log all changes to this object
     * @var bool
     */
    protected static $log_changes = false;
    protected static $log_full = false;
    
    /**
     * Set to true to manually handle the primary key
     * @var bool
     */
    protected static $manual_key = false;
    
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
     * Is populated with the structure of the record
     * @var array|bool Array of structure or false if isn't loaded.
     */
    protected static $structure = false;
    
    /**
     * Point to which field contains the title for this field
     * @var bool|string 
     */
    protected static $title_field = false;
    
    /**
     * The character set to use for the database
     * @var string
     */
    protected static $database_charset = 'utf8mb4_unicode_ci';
    
    
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
     * Constructs the object and ensures that the structure is in place.
     */
    public function __construct(array $initialvalues = array()) {
        static::ensureStructure();
        $this->fillDefaultValues();
        $this->setFromArray($initialvalues);
    }
    
    /**
     * Convenience to retrieve field value
     * @param string $field Field name
     * @return mixed Value
     */
    public function __get(string $field) {
        return $this->getRawValue($field);
    }
    
    /**
     * Convenience to set field value
     * @param string $field Field name
     * @param mixed $value Value
     */
    public function __set(string $field, $value) {
        $this->setValue($field, $value);
    }

    /**
     * Add the given array to the structure of this datarecord
     * @param array $structure Array of Type objects describing individual fields
     */
    public static function addStructure(array $structure) {
        foreach ($structure as $type) {
            if (! $type instanceof Type) trigger_error('Can only add objects of Type to Record', E_USER_ERROR);
            if ($type->isTitle()) static::$title_field = $type->name;
            if ($type->isPrimaryKey()) {
                if (static::$key_field) trigger_error('There can only be one key field added to a Record', E_USER_ERROR);
                static::$key_field = $type->name;
            }
            $sub_fields = $type->addAdditionalStructure();
            if ($sub_fields) {
                $type->setStoreLocation(\Platform\Datarecord\Type::STORE_NOWHERE);
                foreach ($sub_fields as $field) {
                    $field->name = $type->name.'_'.$field->name;
                    static::addStructure([$field]);
                }
            }
            static::$structure[$type->name] = $type;
        }
    }

    /**
     * Build something to the default filter
     * @param Filter $filter
     */
    protected static function buildDefaultFilter(Filter $filter) {
        if (in_array(static::$delete_mode, [self::DELETE_MODE_EMPTY, self::DELETE_MODE_MARK])) {
            $filter->addCondition(new ConditionMatch('is_deleted', 0));
        }
    }
    
    /**
     * Override to extend the object structure
     */
    protected static function buildStructure() {
        static::addStructure(array(
            new ArrayType('metadata', '', ['is_invisible' => true]), // ARRAY
            new DateTimeType('create_date', Translation::translateForUser('Created'), ['is_required' => true, 'is_readonly' => true]),
            new DateTimeType('change_date', Translation::translateForUser('Changed'), ['is_required' => true, 'is_readonly' => true]),
        ));
        
        if (in_array(static::$delete_mode, [self::DELETE_MODE_EMPTY, self::DELETE_MODE_MARK])) {
            static::addStructure(array(
                new BoolType('is_deleted', '', ['is_invisible' => true, 'default_value' => false]), // BOOLEAN
            ));
        }
    }
    
    /**
     * Check if this object can be accessed.
     * This is a soft check, which doesn't affect the inner workings of this object.
     * @return bool
     */
    public function canAccess() : bool {
        return true;
    }
    
    /**
     * Check if this object can be copied.
     * This is a soft check, which doesn't affect the inner workings of this object.
     * @return bool
     */
    public function canCopy() : bool {
        return $this->canCreate() && static::$allow_copy;
    }
    
    /**
     * Check if a new instance of this object can be created.
     * This is a soft check, which doesn't affect the inner workings of this object.
     * @return bool
     */
    public static function canCreate() : bool {
        return true;
    }
    
    /**
     * Check if this object can be deleted
     * @return bool|string True or an error message
     */
    public function canDelete() {
        if (! $this->isInDatabase()) return Translation::translateForUser ('Object isn\'t saved yet');
        if (! $this->canAccess()) return Translation::translateForUser('Cannot access object');
        if (static::$delete_strategy == self::DELETE_STRATEGY_BLOCK) {
            $referring_titles = $this->getReferringObjectTitles();
            if (count($referring_titles)) {
                $CUT = 5;
                $total = count($referring_titles);
                $display_titles = array_slice($referring_titles, 0, $CUT);
                $return = implode(', ',$display_titles);
                if ($total > $CUT) $return .= Translation::translateForUser(' and %1 more', $total-$CUT);
                return Translation::translateForUser('This is referred by: %1', $return);
            }
        }
        return true;
    }
    
    /**
     * Check if this object can be edited
     * This is a soft check, which doesn't affect the inner workings of this object.
     * @return bool
     */
    public function canEdit() : bool {
        return true;
    }
    
    /**
     * Make a copy of this object
     * @return Datarecord New copied and saved object (in read mode)
     */
    /*public function copy(array $related_objects_to_copy = array()) : Datarecord {
        $copy = $this->getCopy(true);
        // Rename object
        $copy->save(false, true);
        // Check if there also are related objects to copy
        if (count($related_objects_to_copy)) {
            $remap = array(); $new_objects = array();
            // Add a remapping from the old ID to the new ID
            $remap[get_called_class()][$this->getValue($this->getKeyField())] = $copy->getValue($this->getKeyField());
            $new_objects[get_called_class()][] = $copy;
            // Loop all objects
            foreach ($related_objects_to_copy as $class) {
                if (substr($class,0,1) == '\\') $class = substr($class,1);
                if (! class_exists($class)) trigger_error('Class '.$class.' does not exist.', E_USER_ERROR);
                // Find all fields in remote object pointing to this object type
                $referring_fields = $class::getFieldsRelatingTo(get_called_class());
                if (! count($referring_fields)) continue;
                // Build a filter to retrieve relevant objects
                $filter = new Filter($class);
                foreach ($referring_fields as $referring_field)
                    $filter->addConditionOR(new ConditionMatch($referring_field, $this));
                // Now get all relevant objects and copy them
                $relevant_objects = $filter->execute()->getAll();
                foreach ($relevant_objects as $relevant_object) {
                    $new_object = $relevant_object->getCopy();
                    $new_object->save(false, true);
                    $new_objects[$class][] = $new_object;
                    // Add a remapping from the old ID to the new ID
                    $remap[$class][$relevant_object->getValue($relevant_object->getKeyField())] = $new_object->getValue($relevant_object->getKeyField());
                }
            }
            // Now we need to loop again to fix relations
            foreach ($new_objects as $class => $objects) {
                foreach ($objects as $object) {
                    // Loop to find all relevant fields
                    foreach ($class::$structure as $fieldname => $definition) {
                        // If this is a single reference pointing to a remapped object...
                        if ($definition['fieldtype'] == self::FIELDTYPE_REFERENCE_SINGLE && isset($remap[$definition['foreign_class']])) {
                            // If we have a remap from an old to a new id, use it.
                            if (isset($remap[$definition['foreign_class']][$object->getValue($fieldname)]))
                                $object->setValue($fieldname, $remap[$definition['foreign_class']][$object->getValue($fieldname)]);
                        }
                        // If this is a multi reference pointing to a remapped object...
                        if ($definition['fieldtype'] == self::FIELDTYPE_REFERENCE_MULTIPLE && isset($remap[$definition['foreign_class']])) {
                            $new_values = array();
                            // Loop all values...
                            foreach ($object->getValue($fieldname) as $value) {
                                // If we have a remap from an old id to a new id, use it. Otherwise keep the existing value.
                                $new_values[] = $remap[$definition['foreign_class']][$value] ?: $value;
                            }
                            // Write back values.
                            $object->setValue($fieldname, $new_values);
                        }
                    }
                    // Save the finalized object.
                    $object->save();
                }
            }
        }
        $copy->unlock();
        return $copy;
    }*/
    
    public function copyFrom(Datarecord $otherobject) {
        if (! is_a($this, get_class($otherobject))) trigger_error('Incompatible objects', E_USER_ERROR);
        $values = $otherobject->getAsArray();
        $this->setFromArray($values);
        // Handle keys
        $this->values[$this->getKeyField()] = $otherobject->getKeyValue();
        $this->values_on_load = $values;
        $this->is_in_database = $otherobject->isInDatabase();
    }
    
    /**
     * Delete this record from the database.
     * @param bool $force_purge Force a purge of references even if object is configured for blocking only.
     * @return bool True if something was actually deleted.
     */
    public function delete(bool $force_purge = false) : bool {
        if ($this->access_mode != self::MODE_WRITE) trigger_error('Tried to delete object '.static::$database_table.' in read mode', E_USER_ERROR);
        if (! $this->isInDatabase()) return false;
        
        if (static::$delete_strategy == self::DELETE_STRATEGY_DO_NOTHING) {
            if (! in_array(static::$delete_mode,[self::DELETE_MODE_EMPTY, self::DELETE_MODE_MARK])) trigger_error('You can only use DELETE_STRATEGY_DO_NOTHING along with DELETE_MODE_MARK or DELETE_MODE_EMPTY', E_USER_ERROR);
        }
        
        if (! $force_purge && static::$delete_strategy == self::DELETE_STRATEGY_BLOCK && count($this->getReferringObjectTitles())) return false;
        
        if (! $this->onDelete()) return false;
        
        // Terminate all files
        if (static::$delete_strategy != self::DELETE_STRATEGY_DO_NOTHING) {
            foreach (static::getStructure() as $field => $type) {
                $type->onDelete($this->getRawValue($field));
            }
        }
        if (static::$delete_mode == self::DELETE_MODE_DELETE) {
            self::query("DELETE FROM ".static::$database_table." WHERE ".static::getKeyField()." = ".((int)$this->values[static::getKeyField()]));
            $number_of_items_deleted = static::getLocation() == self::LOCATION_GLOBAL ? Database::globalAffected() : Database::instanceAffected();
            $this->access_mode = self::MODE_READ;
            $this->unlock();
        } else {
            if (static::$delete_mode == self::DELETE_MODE_EMPTY) $this->reset();
            if ($this->isInDatabase() && $this->is_deleted == 0) $number_of_items_deleted = 1;
            $this->is_deleted = 1;
            $this->save();
        }
        
        // This is no longer in the database
        $this->is_in_database = false;
        
        if ($number_of_items_deleted > 0) $this->onAfterDelete();

        // Stop here if we are configured to do nothing
        if (static::$delete_strategy == self::DELETE_STRATEGY_DO_NOTHING) return $number_of_items_deleted > 0;
        
        // Find all objects referring this
        foreach (static::$referring_classes as $referring_class) {
            // Build a filter to find all referers
            $referer_field_found = false;
            $filter = new Filter($referring_class);
            foreach ($referring_class::getStructure() as $field => $type) {
                if ($type->isReference()) {
                    $filter->addConditionOR(new ConditionMatch($field, $this));
                    $referer_field_found = true;
                }
            }
            // Bail if remote object doesn't have fields pointing at us.
            if (! $referer_field_found) continue;
            // Get all objects referring this
            $referring_objects = $filter->execute();
            foreach ($referring_objects->getAll() as $referring_object) {
                $referring_object->reloadForWrite();
                foreach ($referring_class::getStructure() as $field => $type) {
                    if ($type->isReference()) $referring_object->setValue($field, $type->removeReferenceToObject($referring_object->getRawValue($field), $this));
                }
                $referring_object->save();
            }
        }
        foreach (static::$depending_classes as $depending_class) {
            // Build a filter to find all referers
            $referer_field_found = false;
            $filter = new Filter($depending_class);
            foreach ($depending_class::getStructure() as $field => $type) {
                if ($type->isReference()) {
                    $filter->addConditionOR(new ConditionMatch($field, $this));
                    $referer_field_found = true;
                }
            }
            // Bail if remote object doesn't have fields pointing at us.
            if (! $referer_field_found) continue;
            // Get all objects referring this
            $depending_objects = $filter->execute();
            $depending_objects->deleteAll();
        }
        
        return $number_of_items_deleted > 0;
    }
    
    /**
     * Delete one or more objects by ID. This will do a hard delete and not check
     * relations nor triggers
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
     * Ensure that the database can store this object
     * @return bool True if changes were made to the database
     */
    public static function ensureInDatabase() : bool {
        static::ensureStructure();
        
        $changed = false;
        
        // Check if we have a primary key
        if (! static::$manual_key) {
            $key_found = false;
            foreach (static::getStructure() as $field => $type) {
                if ($type->isPrimaryKey()) {
                    $key_found = true;
                    break;
                }
            }
            if (! $key_found) trigger_error('Class '.get_called_class().' is missing a primary key!', E_USER_ERROR);
        }
        
        // Check if table exists
        $resultset = self::query("DESCRIBE ".static::$database_table, false);
        
        if (! $resultset) {
            // Build field definitions
            $fielddefinitions = array();
            foreach (static::$structure as $name => $type) {
                // Don't create fields for items we want to store in metadata or
                // which shouldn't be stored in DB
                if ($type->getStoreLocation() != Type::STORE_DATABASE) continue;
                $fielddefinition = '`'.$name.'` '.$type->getSQLFieldType();
                if ($type->isPrimaryKey()) {
                    $fielddefinition .= ' PRIMARY KEY';
                    if (! static::$manual_key) $fielddefinition .= ' AUTO_INCREMENT';
                }
                $fielddefinitions[] = $fielddefinition;
            }
            self::query("CREATE TABLE ".static::$database_table." (".implode(',',$fielddefinitions).") COLLATE='".static::$database_charset."'");
            $changed = true;
        } else {
            $fields_in_database = array();
            while ($row = Database::getRow($resultset)) {
                $fields_in_database[$row['Field']] = $row;
            }
            
            // Check for primary key change
            $keyindatabase = false; $database_got_auto_increment = false;
            foreach ($fields_in_database as $field_in_database) {
                if ($field_in_database['Key'] == 'PRI') {
                    $keyindatabase = $field_in_database['Field'];
                    // Check for auto_increment
                    $database_got_auto_increment = strpos($field_in_database['Extra'], 'auto_increment') !== false;
                }
            }
            if ($keyindatabase && $keyindatabase <> static::getKeyField() || $database_got_auto_increment == static::$manual_key) {
                //echo 'Primary key change from '.$keyindatabase.' to '.static::getKeyField().' in '. get_called_class();
                // When the primary key changes, we need to rebuild the table.
                self::query('DROP TABLE '.static::$database_table);
                return static::ensureInDatabase();
            }

            // Check for new fields
            foreach (static::$structure as $name => $type) {
                if (! isset($fields_in_database[$name]) && $type->getStoreLocation() == Type::STORE_DATABASE) {
                    // Create it
                    $sql_type = $type->getSQLFieldType();
                    if ($type->isPrimaryKey()) {
                        $fielddefinition .= ' PRIMARY KEY';
                        if (! static::$manual_key) $fielddefinition .= ' AUTO_INCREMENT';
                    }
                    $default = $type->getDefaultValue() ? ' DEFAULT '.$type->getFieldForDatabase($type->getDefaultValue()) : '';
                    self::query('ALTER TABLE '.static::$database_table.' ADD `'.$name.'` '.$sql_type.$default);
                    $changed = true;
                    
                    // As this field could have been represented in the metadata
                    // we'll try to copy it from the metadata.
                    $resultset = self::query("SELECT ".static::getKeyField().", ".$name.", metadata FROM ".static::$database_table);
                    while ($row = Database::getRow($resultset)) {
                        $metadata = $row['metadata'] ? json_decode($row['metadata'], true) : array();
                        if (isset($metadata[$name])) {
                            // There was something. Write it and unset metadata.
                            $value = $metadata[$name];
                            unset($metadata[$name]);
                            self::query("UPDATE ".static::$database_table." SET metadata = '".Database::escape(json_encode($metadata))."', `$name`=".$type->getFieldForDatabase($value)." WHERE ".static::getKeyField()." = ".$row[static::getKeyField()]);
                        }
                    }
                    
                }
            }
            // Check for changed and removed fields
            foreach ($fields_in_database as $field_in_database) {
                $type = static::getFieldDefinition($field_in_database['Field']);
                if (! $type || $type->getStoreLocation() != Type::STORE_DATABASE) {
                    if ($type && $type->getStoreLocation() == Type::STORE_METADATA) {
                        // We asked to store this field in the metadata instead
                        // so copy it.
                        $resultset = self::query("SELECT ".static::getKeyField().", ".$field_in_database['Field'].", metadata FROM ".static::$database_table);
                        while ($row = Database::getRow($resultset)) {
                            $metadata = $row['metadata'] ? json_decode($row['metadata'], true) : array();
                            $metadata[$field_in_database['Field']] = $row[$field_in_database['Field']];
                            self::query("UPDATE ".static::$database_table." SET metadata = '".Database::escape(json_encode($metadata))."' WHERE ".static::getKeyField()." = ".$row[static::getKeyField()]);
                        }
                    }
                    // Field was removed from structure.
                    self::query('ALTER TABLE '.static::$database_table.' DROP `'.$field_in_database['Field'].'`');
                    $changed = true;
                    continue;
                }
                $type = static::getFieldDefinition($field_in_database['Field']);
                if ($field_in_database['Type'] != mb_substr(mb_strtolower($type->getSQLFieldType()),0, mb_strlen($field_in_database['Type']))) {
                    if (static::$database_change_strategy == self::DATABASE_CHANGE_ALTER) {
                        self::query('ALTER TABLE '.static::$database_table.' CHANGE COLUMN '.$field_in_database['Field'].' '.$field_in_database['Field'].' '.$type->getSQLFieldType());
                    } else {
                        self::query('ALTER TABLE '.static::$database_table.' DROP `'.$field_in_database['Field'].'`');
                        $default = $type->getDefaultValue() ? ' DEFAULT '.$type->getFieldForDatabase($type->getDefaultValue()) : '';
                        self::query('ALTER TABLE '.static::$database_table.' ADD `'.$field_in_database['Field'].'` '.$type->getSQLFieldType().$default);
                    }
                    $changed = true;
                }
            }
        }
        
        // Gather keys
        $existing_keys = array();
        $resultset = self::query("SHOW INDEXES FROM ".static::$database_table);
        while ($row = Database::getRow($resultset)) {
            if ($row['Key_name'] == 'PRIMARY') continue;
            $existing_keys[$row['Key_name']][] = $row['Column_name'];
        }
        // Check for new keys
        foreach (static::$structure as $name => $type) {
            if ($type->getIndexes()) {
                $key_name = $name.'_key';
                if ($type->getIndexes() === true) {
                    $key_fields = array($name);
                } else {
                    $key_fields = array_unique(array_merge([$name], $type->getIndexes()));
                }
                // Check if we have such a key
                if (isset($existing_keys[$key_name])) {
                    // Check if keys are identical
                    if (count(array_diff($key_fields, $existing_keys[$key_name])) || count(array_diff($existing_keys[$key_name], $key_fields))) {
                        // Changed, so we drop the key and rebuild it
                        self::query('ALTER TABLE '.static::$database_table.' DROP KEY '.$key_name);
                        self::query('ALTER TABLE '.static::$database_table.' ADD KEY '.$key_name.' ('.implode(',',$key_fields).')');
                        $changed = true;
                    }
                } else {
                    // We don't have it, so we build it.
                    self::query('ALTER TABLE '.static::$database_table.' ADD KEY '.$key_name.' ('.implode(',',$key_fields).')');
                    $changed = true;
                }
            }
        }
        // Check for expired keys
        foreach ($existing_keys as $key_name => $key_fields) {
            $first_field = current($key_fields);
            if (! isset(static::$structure[$first_field]) || ! static::$structure[$first_field]->getIndexes()) {
                // We found a key that does not exist anymore.
                self::query('ALTER TABLE '.static::$database_table.' DROP KEY '.$key_name);
                $changed = true;
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
     * Fill this object with the default values
     */
    public function fillDefaultValues() {
        foreach ($this->getStructure() as $name => $type) {
            $this->setValue($name, $type->getDefaultValue());
        }
    }

    /**
     * Find records by keywords
     * @param string $keywords Keywords to search for
     * @param string $output Output format. Either "Collection" (default)
     * "array" or "autocomplete"
     * @return type
     */
    public static function findByKeywords(string $keywords, string $output = 'Collection', $additional_filter = null) {
        // Backward compatibility
        if (! in_array($output, array('Collection', 'array', 'autocomplete'))) trigger_error('Invalid output format', E_USER_ERROR);
        $search_fields = array();
        // Locate search fields
        foreach (static::getStructure() as $name => $type) {
            if ($type->isSearchable() || $type->isTitle()) {
                $search_fields[] = $name;
            }
        }
        if (! count($search_fields)) {
            if ($output == 'Collection') return new Collection();
            return array();
        }
        $filter = static::getDefaultFilter();
        if ($additional_filter) $filter->addFilter($additional_filter);
        $parsed_keywords = self::parseKeywords($keywords);
        foreach ($parsed_keywords as $keyword) {
            $previouscondition = false;
            foreach ($search_fields as $fieldname) {
                $condition = new \Platform\Filter\ConditionLike($fieldname, $keyword);
                if ($previouscondition) $condition = new \Platform\Filter\ConditionOR($condition, $previouscondition);
                $previouscondition = $condition;
            }
            $filter->addCondition($condition);
        }
        $filter->setPerformAccessCheck(true);
        $results = $filter->execute();
        if ($results === false) return $results = new Collection(get_called_class());
        if ($output == 'autocomplete') {
            $final_results = array(); $sort_array = array();
            foreach ($results->getAll() as $result) {
                $title = strip_tags(html_entity_decode($result->getTitle()));
                $sort_array[] = $title;
                $final_results[] = array('value' => $title, 'label' => $title, 'real_id' => $result->getRawValue(static::getKeyField()));
            }
            array_multisort($sort_array, SORT_ASC, $final_results);
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
     * @param bool $no_lock If this is true, we won't lock this object. This
     * means that another process can modify the object while we are modifying it.
     */
    public function forceWritemode(bool $no_lock = false) {
        if (! $no_lock) $this->lock();
        $this->access_mode = self::MODE_WRITE;
    }
    
    /**
     * Get all objects of this type as a Collection
     * @param bool $perform_access_check Indicate if we should perform an access check
     * @return Collection
     */
    public static function getAll(bool $perform_access_check = false) : Collection {
        $filter = new Filter(get_called_class());
        $filter->setPerformAccessCheck($perform_access_check);
        if (in_array(static::$delete_mode, [self::DELETE_MODE_EMPTY, self::DELETE_MODE_MARK])) $filter->equal('is_deleted', 0);
        return $filter->execute();
    }

    /**
     * Get all objects of this type as an array hashed by key and sorted by title
     * @param bool $perform_access_check Indicate if we should perform an access check
     * @return array
     */
    public static function getAllAsTitleArray(bool $perform_access_check = false) : array {
        $result = []; $sort_area = []; $ids = [];
        $filter = new \Platform\Filter\Filter(get_called_class());
        $filter->setPerformAccessCheck($perform_access_check);
        if (in_array(static::$delete_mode, [self::DELETE_MODE_EMPTY, self::DELETE_MODE_MARK])) $filter->equal('is_deleted', 0);
        $datacollection = $filter->execute();
        foreach ($datacollection->getAll() as $element) {
            $id = $element->getRawValue(static::getKeyField());
            $result[$id] = $element->getTitle();
            $sort_area[] = strip_tags($element->getTitle());
            $ids[] = $id;
        }
        array_multisort($sort_area, SORT_ASC, $result, $ids);
        $result = array_combine($ids, $result);
        return $result;
    }
        
    /**
     * Get object fields as an array
     * @param array $fields Fields to include (or empty array for all fields)
     * @return array
     */
    public function getAsArray(array $fields = array()) : array {
        if (! count($fields)) $fields = array_keys(static::$structure);
        $result = array();
        foreach ($fields as $field) {
            $result[$field] = $this->getRawValue($field);
        }
        return $result;
    }
    
    /**
     * Get object fields as an array
     * @param array $fields Fields to include (or empty array for all fields)
     * @return array
     */
    public function getFullValuesAsArray(array $fields = array()) : array {
        if (! count($fields)) $fields = array_keys(static::$structure);
        $result = array();
        foreach ($fields as $field) {
            $result[$field] = $this->getFullValue($field);
        }
        return $result;
    }
    
    
    /**
     * Return all fields suitable for insertion into a form. They must also be JSON compatible
     * @return array
     */
    public function getAsArrayForForm() : array {
        $result = array();
        foreach (static::$structure as $name => $type) {
            if ($type->isSubfield()) continue;
            $field = $type->getFormField();
            if ($field === null) continue;
            $result[$name] = $this->getFormValue($name);
        }
        return $result;
    }
    
    /**
     * Get a SQL assignment for the given field.
     * @param string $field Field name
     * @param string $value Field value
     * @return string The SQL assignment statement
     */
    private static function getAssignmentForDatabase(string $field, $value) : string {
        $type = static::$structure[$field];
        if (! $type) trigger_error('No such field: '.$field, E_USER_ERROR);
        return "`$field`=".$type->getFieldForDatabase($value);
    }
    
    /**
     * Get all fields that were changed since this object was loaded. This only
     * returns fields which are saved in the database.
     */
    public function getChangedFields() : array {
        $result = array();
        foreach (static::$structure as $name => $type) {
            if ($type->getStoreLocation() == Type::STORE_DATABASE && $this->values[$name] != $this->values_on_load[$name]) {
                $result[] = $name;
            }
        }
        return $result;
    }
    
    /**
     * Get a collection containing the objects gathered by the given SQL
     * @param string $sql
     * @param bool $perform_access_check If true, then only return objects which
     * we can access
     * @return Collection
     */
    public static function getCollectionFromSQL(string $sql, bool $perform_access_check = false) : Collection {
        $collection = new Collection();
        $qh = self::query($sql);
        while ($qr = Database::getRow($qh)) {
            $object = new static();
            $object->loadFromDatabaseRow($qr);
            if ($perform_access_check && ! $object->canAccess()) continue;
            $collection->add($object);
        }
        return $collection;
    }

    /**
     * Get a copy of this object
     * @return Datarecord
     */
    public function getCopy(bool $name_as_copy = false) : Datarecord {
        $class = get_called_class();
        $new_object = new $class(); 
        $new_object->setFromArray($this->getAsArray(array()));
        if (static::$title_field && $name_as_copy) $new_object->setValue(static::$title_field, Translation::translateForUser('Copy of %1',$this->getRawValue(static::$title_field)));
        return $new_object;
    }

    /**
     * Get the full definition array of this Datarecord
     * @return array
     */
    public static function getFullDefinition() : array {
        static::ensureStructure();
        return static::$structure;
    }
    
    /**
     * Get the field definition for a particular field
     * @param string $field Field name
     * @return array
     */
    public static function getFieldDefinition(string $field) : ?Type {
        static::ensureStructure();
        if (! isset(static::$structure[$field])) return null;
        return static::$structure[$field];
    }
    
    /**
     * Get field names of all fields referring to the given class
     * @param string $class Class name
     * @return array Field names
     */
    public static function getFieldsRelatingTo(string $class) : array {
        $result = array();
        foreach (static::getStructure() as $name => $type) {
            if ($type->matchesForeignClass($class)) $result[] = $name;
        }
        return $result;
    }
    
    /**
     * Get class name in lowercase and without leading namespace
     * @return type
     */
    public static function getBaseClassName() {
        return strtolower(strpos(get_called_class(), '\\') !== false ? substr(get_called_class(), strrpos(get_called_class(), '\\')+1) : get_called_class());
    }
    
    /**
     * Get a form suitable for editing this object
     * @return Form
     */
    public static function getForm() : Form {
        static::ensureStructure();
        $baseclass = static::getBaseClassName();
        // Build form
        $form = Form::Form($baseclass.'_form');
        $form->setEvent('save_'.$baseclass);
        foreach (static::$structure as $name => $type) {
            if ($type->isReadonly() || $name == 'metadata' || $type->isSubfield()) continue;
            $field = $type->getFormField();
            if ($field === null) continue;
            $form->addField($field);
        }
        // End row in progress
        // Add custom form validator
        $form->addValidationFunction(get_called_class().'::validateForm');
        
        // Add layout if present
        if (count(static::$layout)) $form->setLayout(Layout::getLayoutFromArray (static::$layout));
        return $form;
    }
    
    /**
     * Get a value suitable for a form from this object
     * @param string $field Field name
     * @return mixed 
     */
    public function getFormValue(string $field) {
        $type = static::getFieldDefinition($field);
        if (! $type) trigger_error('Unknown field '.$field.' in object '.__CLASS__, E_USER_ERROR);
        return $type->getFormValue($this->getRawValue($field));
    }

    /**
     * Get a fully formatted value from this object
     * @param string $field Field name
     * @return string Formatted string, this may be HTML
     */
    public function getFullValue(string $field) {
        $type = static::getFieldDefinition($field);
        if (! $type) trigger_error('Unknown field '.$field.' in object '.__CLASS__, E_USER_ERROR);
        return $type->getFullValue($this->getRawValue($field), $this->collection);
    }
    
    /**
     * Return the object pointed to by this field
     * @param string $field Field name
     * @return Object referenced
     */
    public function getForeignObject(string $field) {
        $type = static::getFieldDefinition($field);
        if (! $type) trigger_error('Unknown field '.$field.' in object '.__CLASS__, E_USER_ERROR);
        return $type->getForeignObject($this->getRawValue($field));
    }
    
    /**
     * Get the key field of this datarecord.
     * @return bool|string Name of key field or false if no key field was
     * detected.
     */
    public static function getKeyField() {
        if (static::$key_field === false) {
            static::ensureStructure();
            foreach (static::$structure as $name => $type) {
                if ($type->isPrimaryKey()) {
                    static::$key_field = $name;
                    break;
                }
            }
        }
        return static::$key_field;
    }
    
    /**
     * Get the key value of this field
     * @return int
     */
    public function getKeyValue() : int {
        return (int)$this->getRawValue($this->getKeyField());
    }
    
    /**
     * Get the layout for this datarecord.
     * @return Layout
     */
    public function getLayout() : Layout {
        return Layout::getLayoutFromArray(static::$layout);
    }
    
    /**
     * Get the location (global or instance) of this object type
     * @return int
     */
    public static function getLocation() : int {
        return static::$location;
    }
    
    /**
     * Get the name of a lockfile appropriate for this object.
     * @return string Lock file name
     */
    protected function getLockFileName() : string {
        if (! static::$database_table) trigger_error('Cannot determine lock file name without table name.', E_USER_ERROR);
        return self::getLockFileNameByTableAndID(static::$database_table, (int)$this->values[static::getKeyField()]);
    }
    
    /**
     * Get a lock file name for an object in the specific table, with the given ID
     * @param string $database_table Table name
     * @param int $id Key
     * @return string Lock file name
     */
    protected static function getLockFileNameByTableAndID(string $database_table, int $id) {
        return $database_table.((int)$id);
    }
    
    /**
     * Get the value of a field to be used as a log field
     * @param string $field Field name
     * @param bool $use_value_on_load If true then use the values as they were when loaded
     * @return type
     */
    public function getLogValue(string $field, bool $use_value_on_load = false) {
        $type = static::getFieldDefinition($field);
        if (! $type) trigger_error('Unknown field '.$field.' in object '.__CLASS__, E_USER_ERROR);
        $result = $type->getLogValue($this->getRawValue($field, $use_value_on_load));
        return $result;
    }

    /**
     * Get the readable name of this object type. Defaults to class name if
     * no name is set.
     * @return string
     */
    public static function getObjectName() : string {
        return static::$object_name ?: static::getBaseClassName();
    }
    
    /**
     * Get titles of all objects referring this object
     * @return array
     */
    public function getReferringObjectTitles() : array {
        // Find all objects referring this
        $referring_titles = array();
        foreach (static::$referring_classes as $referring_class) {
            // Build a filter to find all referers
            $referer_found = false;
            $filter = new Filter($referring_class);
            foreach ($referring_class::getStructure() as $field => $type) {
                if ($type->matchesForeignClass(get_called_class())) {
                    $filter->addConditionOR(new \Platform\Filter\ConditionMatch($field, $this));
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
    public function getTitle() : string {
        return static::$title_field ? (string)$this->getFullValue(static::$title_field) : get_called_class().' (#'.$this->getRawValue(static::getKeyField()).')';
    }
    
    /**
     * Get the field containing the title (if any)
     * @return string
     */
    public static function getTitleField() : string {
        return static::$title_field;
    }
    
    /**
     * Get the title of several objects of this type by ID.
     * @param array $ids Object ids
     * @return array Titles hashed by ID's
     */
    public static function getTitlesByIds(array $ids) : array {
        return TitleBuffer::getTitlesByClassAndIds(get_called_class(), $ids);
    }
    
    /**
     * Get the title of an object of this type by ID.
     * @param int $id Object id
     * @return string Title
     */
    public static function getTitleById(int $id) : string {
        if (! $id) return '';
        return TitleBuffer::getTitleByClassAndId(get_called_class(), $id);
    }
    
    /**
     * Get the structure of this object
     * @return array
     */
    public static function getStructure() : array {
        static::ensureStructure();
        return static::$structure;
    }

    /**
     * Read a raw value from the object
     * @param string $field Field name
     * @param bool $use_values_on_load If true, then use values on load
     * @return mixed Value
     */
    public function getRawValue(string $field, bool $use_values_on_load = false) {
        $type = static::getFieldDefinition($field);
        if (! $type) trigger_error('Unknown field '.$field.' in object '.__CLASS__, E_USER_ERROR);
        // Check if we have subfields
        if (count($type->getSubfieldNames())) {
            $value_to_pass = [];
            foreach ($type->getSubfieldNames() as $subfield_name) {
                $value_to_pass[$subfield_name] = $this->getRawValue($field.'_'.$subfield_name, $use_values_on_load);
            }
        } else {
            $value_to_pass = $use_values_on_load ? $this->values_on_load[$field] : $this->values[$field];
        }
        return $type->getRawValue($value_to_pass);
    }
    
    /**
     * Get database table name
     * @return string
     */
    public static function getDatabaseTable() : string {
        return static::$database_table;
    }
    
    /**
     * Get a default filter for this class to use for current user. This can
     * be used to improve performance, if the user is only allowed to see a 
     * subset of the data.
     * @return Filter
     */
    public static final function getDefaultFilter() : Filter {
        $filter = new Filter(get_called_class());
        static::buildDefaultFilter($filter);
        return $filter;
    }
    
    /**
     * Get a edit complex for this Datarecord
     * @return EditComplex
     */
    public static function getEditComplex(array $parameters = array()) : EditComplex {
        return EditComplex::EditComplex(get_called_class(), $parameters);        
    }
    
    /**
     * Get a readable text value from this object
     * @param string $field Field name
     * @return string Text string
     */
    public function getTextValue(string $field) : string {
        $type = static::getFieldDefinition($field);
        if (! $type) trigger_error('Unknown field '.$field.' in object '.__CLASS__, E_USER_ERROR);
        return $type->getTextValue($this->getRawValue($field), $this->collection);
    }
    
    /**
     * Get fields to use in a Table
     * @return array
     */
    public static function getTableFields() : array {
        static::ensureStructure();
        $result = array();
        foreach (static::$structure as $name => $type) {
            if ($type->isInvisible() || $type->getListLocation() == Type::LIST_NEVER) continue;
            $result[] = $name;
        }
        return $result;
    }
    
    /**
     * Check if this object have changed since it was loaded from the database.
     * @return bool
     */
    public function haveChanged() : bool {
        foreach (static::$structure as $name => $type) {
            if (! $type->filterMatch($this->values[$name], $this->values_on_load[$name])) return true;
        }
        return false;
    }
    
    /**
     * Check if this object have changed since it was loaded (in regards to fields
     * that are actually saved in the database).
     * @return bool
     */
    public function isChanged() : bool {
        foreach (static::$structure as $field => $type) {
            if ($type->getStoreLocation() == Type::STORE_DATABASE && ! $type->filterMatch($this->values[$field], $this->values_on_load[$field])) return true;
        }
        return false;
    }
    
    /**
     * Check if copy is generally allowed of this object type
     * @return bool
     */
    public static function isCopyAllowed() : bool {
        return static::$allow_copy;
    }
    
    /**
     * Indicate if this object is deleted (which is only possible with certain
     * delete strategies).
     * @return bool
     */
    public function isDeleted() : bool {
        return $this->getRawValue('is_deleted') ? true : false;
    }
    
    /**
     * Determines if this is stored in the database.
     * @return bool True if stored in database
     */
    public function isInDatabase() : bool {
        return $this->is_in_database;
    }
    
    /**
     * Load an object from the database for reading.
     * @param int $id Object ID
     * @param bool $fail_on_not_found Indicate if the call should fail if the record isn't found.
     */
    public function loadForRead(int $id, bool $fail_on_not_found = true) {
        // Spoof id field
        $this->values[static::getKeyField()] = $id;
        // Switch to read mode if in write mode and something was loaded
        if ($this->loadFromDatabase($id, $fail_on_not_found) && $this->access_mode == self::MODE_WRITE) {
            // Unlock
            $this->unlock();
        }
    }
    
    /**
     * Load an object from the database for writing.
     * @param int $id Object ID
     * @param bool $fail_on_not_found Indicate if the call should fail if the record isn't found.
     */
    public function loadForWrite(int $id, bool $fail_on_not_found = true) {
        // Spoof id field
        $this->values[static::getKeyField()] = $id;
        $this->lock();
        $this->access_mode = self::MODE_WRITE;
        if (! $this->loadFromDatabase($id, $fail_on_not_found)) {
            // Unlock if we couldn't read
            $this->unlock();
        }
    }
    
    /**
     * Load an object from the database.
     * @param int $id Object ID
     * @param bool $fail_on_not_found Indicate if the call should fail if the record isn't found.
     * @return bool True if an object was loaded
     */
    private function loadFromDatabase(int $id, bool $fail_on_not_found = true) : bool {
        $result = self::query("SELECT * FROM ".static::$database_table." WHERE ".static::getKeyField()." = ".((int)$id));
        $row = Database::getRow($result);
        if ($row) {
            $this->parseFromDatabaseRow($row);
            $this->unpackMetadata();
            $this->values_on_load = $this->values;
            $this->is_in_database = true;
            return true;
        }
        if ($fail_on_not_found) trigger_error('Record in table '.static::$database_table.' with id = '.$id.' not found!', E_USER_ERROR);
        // When using manual keys we populate the object with the key even though the record wasn't found
        return false;
    }

    /**
     * "Load" an object from passed database data. It will always be in readonly
     * mode
     * @param array $databaserow Database result from database
     */
    public function loadFromDatabaseRow($databaserow) {
        if ($databaserow === false) return;
        // Check if already in write mode
        if ($this->access_mode == self::MODE_WRITE) {
            // Unlock
            $this->unlock();
        }
        $this->access_mode = self::MODE_READ;
        $this->parseFromDatabaseRow($databaserow);
        $this->unpackMetadata();
        $this->values_on_load = $this->values;
        $this->is_in_database = true;
    }    
    
    /**
     * Lock this object
     */
    public function lock() {
        if (!Semaphore::wait($this->getLockFileName())) {
            trigger_error('Failed to lock '.get_called_class().' ('.$this->getValue($this->getKeyField()).') within reasonable time / '.Semaphore::getCallStack($this->getLockFileName()), E_USER_ERROR);
        }
    }
    
    public function logChange() {
        $log = new Log('datarecord', ['6r']);
        $text = '';
        if ($this->isInDatabase()) $text = 'CH '.$this->getClassName ().'('.$this->getKeyValue().') - ';
        else $text = 'CR '.$this->getClassName().'('.$this->getKeyValue().') - ';
        $first = true;
        foreach ($this->getChangedFields() as $fieldname) {
            // Skip metadata
            if ($fieldname == 'metadata') continue;
            if ($first) $first = false;
            else $text .= ',';
            $text .= $fieldname.':';
            $text .= static::getLogValue($fieldname, true);
            $text .= '=>';
            $text .= static::getLogValue($fieldname);
        }
        $log->log(Accesstoken::getCurrentUserID(), $text);
    }
    
    /**
     * Called after object is saved.
     * @param array $changed_fields Array of fields which were changed
     */
    public function onAfterSave(array $changed_fields) {
    }
    
    /**
     * Called after an object is created.
     */
    public function onAfterCreate() {
        
    }
    
    /**
     * Called after an object is deleted.
     */
    public function onAfterDelete() {
        
    }
    
    /**
     * Called just before an object is created. If this function returns false
     * object creation is hindered.
     * @return bool Should we continue
     */
    public function onCreate() : bool {
        return true;
    }
    
    /**
     * Called just before an object is deleted. If this function returns false
     * object deletion is hindered.
     * @return bool Should we continue
     */
    public function onDelete() : bool {
        return true;
    }

    /**
     * Called when object is saved. If this function returns false, the object
     * isn't saved.
     * @param array $changed_fields Array of fields which were changed
     * @return bool Should we continue.
     */
    public function onSave(array $changed_fields) : bool {
        return true;
    }
    
    /**
     * Pack metadata according to structure definition
     */
    private function packMetadata() {
        $metadata = array();
        foreach (static::$structure as $field => $type) {
            if ($type->getStoreLocation() != Type::STORE_METADATA) continue;
            $metadata[$field] = $this->getJSONValue($this->getRawValue($field));
        }
        $this->setValue('metadata', $metadata);
    }
    
    /**
     * Parse data fields from a database row
     * @param array $databaserow The database row
     */
    private function parseFromDatabaseRow(array $databaserow) {
        $this->values = array();
        if (! is_array($databaserow)) return;
        foreach ($databaserow as $field => $value) {
            // When reading a database row, we can encounter an extended field structure, so we 
            // skip fields we don't know about.
            $type = static::getFieldDefinition($field);
            if (! $type) continue;
            $this->values[$field] = $type->parseDatabaseValue($value);
        }
    }
    
    /**
     * Parse keywords by splitting into arrays at space, but "preserving phrases"
     * @param string $keywords String to split
     * @return array Individual words and phrases.
     */
    private static function parseKeywords(string $keywords) : array {
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
     * Purge all objects from the database and resets IDs. Please note that this is a 
     * hard delete that doesn't process referers
     */
    public static function purge() {
        static::query("DELETE FROM ".static::$database_table);
        static::query("ALTER TABLE ".static::$database_table." AUTO_INCREMENT = 1");
    }
    
    /**
     * Make a suitable database query for this objects location
     * @param string $query Query
     * @param bool $failonerror 
     * @return array
     */
    public static function query(string $query, bool $failonerror = true) {
        if (static::getLocation() == self::LOCATION_GLOBAL) return Database::globalQuery ($query, $failonerror);
        else return Database::instanceQuery ($query,$failonerror);
    }
    
    /**
     * Reloads a current object for writing.
     */
    public function reloadForWrite() : bool {
        if ($this->access_mode == self::MODE_WRITE) return true;
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
    public static function renderEditComplex(array $parameters = array()) {
        $edit_complex = static::getEditComplex($parameters);
        $edit_complex->render();
    }
    
    /**
     * Render an integrity check of this class.
     */
    public static function renderIntegrityCheck() {
        echo '<h3 style="margin-bottom: 2px;">'.get_called_class().'</h3>';
        $errors = array();
        // Ensure newest version and test that we don't upgrade the database in excess.
        static::ensureInDatabase();
        $changed = static::ensureInDatabase();
        if ($changed) $errors[] = 'Database was changed even though there should be no changes. This is probably a problem with Platform.';
        
        foreach (static::getStructure() as $name => $type) {
            $errors = array_merge($errors, $type->integrityCheck());
        }
        // Check referring classes
        foreach (static::$referring_classes as $foreign_class) {
            if (! class_exists($foreign_class)) $errors[] = 'Have <i>'.$foreign_class.'</i> as a referring class, but the class doesn\'t exist.';
            else {
                $hit = false;
                foreach ($foreign_class::getStructure() as $name => $type) {
                    if ($type->isReference() && $type->matchesForeignClass(get_called_class())) {
                        $hit = true;
                        break;
                    }
                }
                if (! $hit) $errors[] = 'Have <i>'.$foreign_class.'</i> as a referring class, but that class doesn\'t refer this class.';
            }
        }
        // Check depending classes
        foreach (static::$depending_classes as $foreign_class) {
            if (! class_exists($foreign_class)) $errors[] = 'Have <i>'.$foreign_class.'</i> as a depending class, but the class doesn\'t exist.';
            else {
                $hit = false;
                foreach ($foreign_class::getStructure() as $name => $type) {
                    if ($type->isReference() && $type->matchesForeignClass(get_called_class())) {
                        $hit = true;
                        break;
                    }
                }
                if (! $hit) $errors[] = 'Have <i>'.$foreign_class.'</i> as a depending class, but that class doesn\'t refer this class.';
            }
        }        
        echo '<ul style="margin-top: 3px; margin-bottom: 5px; font-size: 0.8em;">';
        if (! count($errors) ) echo '<li><span style="color: green;">All OK</span>';
        foreach ($errors as $error) echo '<li><span style="color: red;">'.$error.'</span>';
        echo '</ul>';
    }

    /**
     * Reset all fields in object except the key, create date and change date
     */
    public function reset() {
        $fields_to_keep = array('create_date', 'change_date');
        foreach (static::$structure as $field => $type) {
            if (! $type->isPrimaryKey() && ! in_array($field, $fields_to_keep)) $this->setValue($field, null);
        }
    }
    
    /**
     * Save the object to the database, if it have changed.
     * @param bool $force_save Set true to always save object
     * @param bool $keep_open_for_write Set to true to keep object open for write after saving
     * @return bool True if we actually saved the object
     */
    public function save(bool $force_save = false, bool $keep_open_for_write = false) : bool {
        if ($this->access_mode != self::MODE_WRITE) trigger_error('Tried to save object '.static::$database_table.' in read mode', E_USER_ERROR);
        
        $is_new_object = ! $this->isInDatabase();
        
        $changed_fields = $this->getChangedFields();
        
        // Event handlers
        if ($is_new_object && ! $this->onCreate()) return false;
        if (!$this->onSave($changed_fields)) return false;
        
        $manual_key_semaphore = 'platform_manual_key_'.static::$database_table;

        // Handle manual keys if setup
        if (static::$manual_key) {
            // We need to lock this entire object when handling manual keys to prevent race conditions
            if (! Semaphore::wait($manual_key_semaphore, 5, 10)) trigger_error('Couldn\'t lock manual key semaphore within reasonable time.', E_USER_ERROR);
            if (! $this->getKeyValue() || ! is_int($this->getKeyValue())) trigger_error('Invalid key value when saving '.static::$database_table, E_USER_ERROR );
            if (! $is_new_object && $this->getKeyValue() != $this->values_on_load[$this->getKeyField()]) trigger_error('You aren\'t allowed to modify the primary key.', E_USER_ERROR);
            // If we are saving a new object or have changed the key value...
            if ($is_new_object) {
                // Check that key isn't already in use.
                $result = static::query("SELECT * FROM ".static::$database_table." WHERE ".$this->getKeyField()." = ".$this->getKeyValue());
                $row = Database::getRow($result);
                if ($row !== null) trigger_error('Key '.$this->getKeyValue().' is already in use, when saving '.static::$database_table, E_USER_ERROR);
            }
        }
        
        if (! $force_save && $this->isInDatabase()) {
            // We don't save if nothing is changed?
            $change = count($changed_fields) > 0;
            if (! $change) {
                if (! $keep_open_for_write) $this->unlock();
                return false;
            }
        }
        
        $this->packMetadata();
        $this->setValue('change_date', new Time('now'));
        if ($this->isInDatabase()) {
            // Prepare update.
            $fielddefinitions = array();
            foreach ($this->getChangedFields() as $field) {
                $type = $this->getFieldDefinition($field);
                if ($type->getStoreLocation() == Type::STORE_METADATA) continue;
                $fielddefinitions[] = static::getAssignmentForDatabase($field, $this->values[$field]);
            }
            // In rare cases where you force save and do it twice within the same second, then there is nothing to update
            if (count($fielddefinitions)) {
                $sql = 'UPDATE '.static::$database_table.' SET '.implode(',',$fielddefinitions).' WHERE '.static::getKeyField().' = '.$this->values[static::getKeyField()];
                self::query($sql);
            }
            if (! $keep_open_for_write) $this->unlock();
        } else {
            $this->setValue('create_date', new Time('now'));
            $fieldlist = array(); $fieldvalues = array();
            foreach (static::getStructure() as $field => $type) {
                if ($type->getStoreLocation() == Type::STORE_DATABASE) {
                    $fieldlist[] = "`$field`"; 
                    $fieldvalues[] = ($type->isPrimaryKey() && ! static::$manual_key) ? 'NULL' : $type->getFieldForDatabase($this->values[$field]);
                }
            }
            $sql = 'INSERT INTO '.static::$database_table.' ('.implode(',',$fieldlist).') VALUES ('.implode(',',$fieldvalues).')';
            self::query($sql);
            $this->unlock();
            $this->values[static::getKeyField()] = static::getLocation() == self::LOCATION_GLOBAL ? Database::globalGetInsertedKey() : Database::instanceGetInsertedKey();
            if ($keep_open_for_write) {
                // Lock the new object
                $this->lock();
                $this->forceWritemode();
            }
        }
        if (static::$log_changes) $this->logChange();
        $this->values_on_load = $this->values;
        
        // This is now in the database
        $this->is_in_database = true;
        
        // Update reference buffer
        TitleBuffer::updateBuffer(get_called_class(), $this->getKeyValue(), $this->getTitle());
        
        // Unlock a manual key semaphore (if any)
        Semaphore::release($manual_key_semaphore);
        
        if ($is_new_object) $this->onAfterCreate();
        $this->onAfterSave($changed_fields);
        
        return true;
    }
    
    /**
     * Set values to object as defined in an array. Invalid array elements will be
     * ignored
     * @param array $array Field values hashed by field names
     */
    public function setFromArray(array $array) {
        foreach ($array as $key => $value) if (isset(static::$structure[$key])) $this->setValue ($key, $value);
    }
    
    /**
     * Set a value in the object. An error will trigger if trying to set invalid field
     * @param string $field Field name
     * @param mixed $value Field value
     */
    public function setValue(string $field, $value) {
        $type = static::getFieldDefinition($field);
        if (! $type) trigger_error('Tried setting invalid field: '.$field.' in class '. get_called_class(), E_USER_ERROR);
        if (count($type->getSubfieldNames())) {
            $values = $type->parseValue($value);
            foreach ($values as $subfield_name => $value) {
                $this->setValue($field.'_'.$subfield_name, $value);
            }
        } else {
            $this->values[$field] = $type->parseValue($value, $this->values[$field]);
        }
    }

    /**
     * Unlocks this object (if it is locked)
     */
    public function unlock() {
        Semaphore::release($this->getLockFileName());
        $this->access_mode = $this->isInDatabase() ? self::MODE_READ : self::MODE_WRITE;
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
    
    /**
     * Perform additional validation of the edit form
     * @return bool
     */
    public static function validateForm(Form $form) : bool {
        return true;
    }
    
    /**
     * Validate if the object is valid in it's current state.
     * @return bool|array true if valid otherwise array of problems
     */
    public function validateObject() {
        return true;
    }
}