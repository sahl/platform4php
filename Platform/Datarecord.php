<?php
namespace Platform;
/**
 * Class for having objects which are saved in a database and can easily be manipulated.
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=datarecord_class
 */

use Platform\Form\CheckboxField;
use Platform\Form\CurrencyField;
use Platform\Form\AddressField;
use Platform\Form\DatarecordcomboboxField;
use Platform\Form\DateField;
use Platform\Form\DatetimeField;
use Platform\Form\EmailField;
use Platform\Form\Field;
use Platform\Form\FileField;
use Platform\Form\Form;
use Platform\Form\HiddenField;
use Platform\Form\Layout;
use Platform\Form\MulticheckboxField;
use Platform\Form\MultidatarecordcomboboxField;
use Platform\Form\MultiField;
use Platform\Form\MultiplierSection;
use Platform\Form\NumberField;
use Platform\Form\PasswordField;
use Platform\Form\RepetitionField;
use Platform\Form\SelectField;
use Platform\Form\TextareaField;
use Platform\Form\TexteditorField;
use Platform\Form\TextField;
use Platform\Security\Accesstoken;
use Platform\Server\Job;
use Platform\UI\EditComplex;
use Platform\Utilities\Database;
use Platform\Utilities\Errorhandler;
use Platform\Utilities\Image;
use Platform\Utilities\Log;
use Platform\Utilities\Repetition;
use Platform\Utilities\Semaphore;
use Platform\Utilities\Time;
use Platform\Utilities\Translation;
use Platform\Utilities\Utilities;

class Datarecord implements DatarecordReferable {

    // Column visibilities
    const COLUMN_INVISIBLE = 1;
    const COLUMN_HIDDEN = 2;
    const COLUMN_VISIBLE = 0;
    
    const DELETE_MODE_DELETE = 0;
    const DELETE_MODE_EMPTY = 1;
    const DELETE_MODE_MARK = 2;
    
    // Delete strategies
    const DELETE_STRATEGY_DO_NOTHING = 0;
    const DELETE_STRATEGY_BLOCK = 1;
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
     * Reference to a collection, that this is a part of
     * @var Collection 
     */
    public $collection = false;

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
     * Names of all classes depending on this class
     * @var type 
     */
    protected static $depending_classes = array();
    
    /**
     * Name of javascript file to include when editing object
     * @var string 
     */
    protected static $edit_script = false;
    
    /**
     * Used to indicate if this object is in the database.
     * @var type
     */
    protected $is_in_database = false;

    /**
     * Convenience to store keyfield
     * @var bool|string 
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
    protected static $log_me = false;
    
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
     * Buffer to store all requested calculations
     * @var array
     */
    protected static $requested_calculation_buffer = array();

    /**
     * Is populated with the structure of the data record
     * @var array|bool Array of structure or false if isn't loaded.
     */
    protected static $structure = false;
    
    /**
     * Point to which field contains the title for this field
     * @var bool|string 
     */
    protected static $title_field = false;
    
    
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
     * Used to indicate if a find by keyword search is in progress, to prevent going to deep in reference fields
     * @var bool
     */
    private static $find_by_keyword_in_progress = false;
    
    /**
     * Which subfields are included in an address field
     */
    const ADDRESS_FIELDS = ['address', 'address2', 'city', 'zip', 'countrycode'];
    
    /**
     * Constructs the object and ensures that the structure is in place.
     */
    public function __construct(array $initialvalues = array()) {
        if (Platform::getConfiguration('debug_mode')) static::$log_me = true;
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
        return $this->getValue($field, $this->default_rendermode);
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
     * @param array $structure Array of field definitions to add
     */
    public static function addStructure(array $structure) {
        foreach ($structure as $field => $data) {
            if ($data['is_title']) static::$title_field = $field;
            if (isset($data['foreign_class']) && substr($data['foreign_class'],0,1) == '\\') $data['foreign_class'] = substr($data['foreign_class'],1);
            switch($data['fieldtype']) {
                case self::FIELDTYPE_CURRENCY:
                    $data['store_in_database'] = false;
                    static::addStructure(array(
                        $field.'_localvalue' => array(
                            'invisible' => true,
                            'subfield' => true,
                            'fieldtype' => self::FIELDTYPE_FLOAT,
                            'store_in_metadata' => $data['store_in_metadata']
                        ),
                        $field.'_currency' => array(
                            'invisible' => true,
                            'subfield' => true,
                            'fieldtype' => self::FIELDTYPE_TEXT,
                            'store_in_metadata' => $data['store_in_metadata']
                        ),
                        $field.'_foreignvalue' => array(
                            'invisible' => true,
                            'subfield' => true,
                            'fieldtype' => self::FIELDTYPE_FLOAT,
                            'store_in_metadata' => $data['store_in_metadata']
                        )
                    ));
                break;
                case self::FIELDTYPE_ADDRESS:
                    $data['store_in_database'] = false;
                    $addressfields = [];
                    foreach (self::ADDRESS_FIELDS as $subfield)
                        $addressfields[$field.'_'.$subfield] = [
                            'invisible' => true,
                            'subfield' => true,
                            'fieldtype' => self::FIELDTYPE_TEXT,
                            'store_in_metadata' => $data['store_in_metadata']
                        ];
                    static::addStructure($addressfields);
                break;
                case self::FIELDTYPE_REFERENCE_HYPER:
                    $data['store_in_database'] = false;
                    static::addStructure(array(
                        $field.'_foreign_class' => array(
                            'invisible' => true,
                            'subfield' => true,
                            'fieldtype' => self::FIELDTYPE_TEXT,
                            'store_in_metadata' => $data['store_in_metadata']
                        ),
                        $field.'_reference' => array(
                            'invisible' => true,
                            'subfield' => true,
                            'fieldtype' => self::FIELDTYPE_INTEGER,
                            'store_in_metadata' => $data['store_in_metadata']
                        )
                    ));
                break;
            }
            static::$structure[$field] = $data;
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
            'metadata' => array(
                'invisible' => true,
                'fieldtype' => self::FIELDTYPE_ARRAY
            ),
            'create_date' => array(
                'label' => 'Created',
                'readonly' => true,
                'columnvisibility' => self::COLUMN_HIDDEN,
                'fieldtype' => self::FIELDTYPE_DATETIME
            ),
            'change_date' => array(
                'label' => 'Last change',
                'readonly' => true,
                'columnvisibility' => self::COLUMN_HIDDEN,
                'fieldtype' => self::FIELDTYPE_DATETIME
            )
        ));
        
        if (in_array(static::$delete_mode, [self::DELETE_MODE_EMPTY, self::DELETE_MODE_MARK])) {
            static::addStructure(array(
                'is_deleted' => array(
                    'invisible' => true,
                    'fieldtype' => self::FIELDTYPE_BOOLEAN,
                    'default_value' => false
                )
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
     * This is a hard check, where a value of true will prevent the object from
     * deleting (except when overriding this behaviour)
     * @return bool|string True or an error message
     */
    public function canDelete() {
        if (! $this->isInDatabase()) return 'Not saved yet';
        if (! $this->canAccess()) return 'Cannot access';
        if (static::$delete_strategy == self::DELETE_STRATEGY_BLOCK) {
            $referring_titles = $this->getReferringObjectTitles();
            if (count($referring_titles)) {
                $CUT = 5;
                $total = count($referring_titles);
                $display_titles = array_slice($referring_titles, 0, $CUT);
                $return = implode(', ',$display_titles);
                if ($total > $CUT) $return .= ' and '.($total-$CUT).' more.';
                return 'This is referred by: '.$return;
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
     * Check if the given render mode is a valid render mode.
     * @param int $render_mode
     * @return bool
     */
    private static function checkRenderMode(int $render_mode) {
        return in_array($render_mode, array(self::RENDER_RAW, self::RENDER_TEXT, self::RENDER_FULL, self::RENDER_FORM));
    }
    
    /**
     * Make a copy of this object
     * @return Datarecord New copied and saved object (in read mode)
     */
    public function copy(array $related_objects_to_copy = array()) : Datarecord {
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
    }
    
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
            foreach (static::getStructure() as $key => $definition) {
                if (in_array($definition['fieldtype'], array(self::FIELDTYPE_FILE, self::FIELDTYPE_IMAGE)) && $this->getRawValue($key)) {
                    $file = new File();
                    $file->loadForWrite($this->getRawValue($key));
                    $file->delete();
                }
            }
        }
        $deleted_id = $this->values[static::getKeyField()];
        if (static::$delete_mode == self::DELETE_MODE_DELETE) {
            self::query("DELETE FROM ".static::$database_table." WHERE ".static::getKeyField()." = ".((int)$this->values[static::getKeyField()]));
            $number_of_items_deleted = static::getLocation() == self::LOCATION_GLOBAL ? Database::globalAffected() : Database::instanceAffected();
            $this->access_mode = self::MODE_READ;
            $this->unlock();
            unset($this->values[static::getKeyField()]);
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
        foreach (static::$referring_classes as $depending_class) {
            // Build a filter to find all referers
            $referer_field_found = false;
            $filter = new Filter($depending_class);
            foreach ($depending_class::getStructure() as $key => $definition) {
                if (in_array($definition['fieldtype'], array(self::FIELDTYPE_REFERENCE_SINGLE, self::FIELDTYPE_REFERENCE_MULTIPLE)) && $definition['foreign_class'] == get_called_class() && $definition['store_in_database'] !== false) {
                    $filter->addConditionOR(new ConditionMatch($key, $deleted_id));
                    $referer_field_found = true;
                }
            }
            // Bail if remote object doesn't have fields pointing at us.
            if (! $referer_field_found) continue;
            // Get all objects referring this
            $depending_objects = $filter->execute();
            foreach ($depending_objects->getAll() as $referring_object) {
                $referring_object->reloadForWrite();
                foreach ($depending_class::getStructure() as $key => $definition) {
                    if ($definition['foreign_class'] == get_called_class()) {
                        if ($definition['fieldtype'] == self::FIELDTYPE_REFERENCE_SINGLE) {
                            if ($referring_object->getRawValue($key) == $deleted_id) $referring_object->setValue($key, 0);
                        } elseif ($definition['fieldtype'] == self::FIELDTYPE_REFERENCE_MULTIPLE) {
                            $values = $referring_object->getRawValue($key);
                            Utilities::arrayRemove($values, $deleted_id);
                            $referring_object->setValue($key, $values);
                        }
                    }
                }
                $referring_object->save();
            }
        }
        foreach (static::$depending_classes as $depending_class) {
            // Build a filter to find all referers
            $referer_field_found = false;
            $filter = new Filter($depending_class);
            foreach ($depending_class::getStructure() as $key => $definition) {
                if (in_array($definition['fieldtype'], array(self::FIELDTYPE_REFERENCE_SINGLE, self::FIELDTYPE_REFERENCE_MULTIPLE)) && $definition['foreign_class'] == get_called_class()) {
                    $filter->addConditionOR(new ConditionMatch($key, $deleted_id));
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
     * Do a calculation on this specific object
     * @param string $calculation Calculation to do
     */
    public function doCalculation(string $calculation) {
    }

    /**
     * Do a calculation on all objects with the given ids.
     * @param string $calculation Calculations to do
     * @param array $ids Object IDs
     */
    public static function doCalculationOnObjects(string $calculation, array $ids) {
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
    public static function calculationJob(Job $job) {
        
        $MAX_LINES = 250000; // Max number of lines to read at once.
        $MAX_RUNTIME = ini_get('max_execution_time') ? min(ini_get("max_execution_time")-10, 60*10) : 60*10; // Max time to spend calculating in each run.
        
        $run_again = false;
        
        if (! Semaphore::wait('platform_calculation')) trigger_error('Could not obtain recalculation semaphore', E_USER_ERROR);
        if (! Semaphore::wait('platform_calculation_file')) trigger_error('Could not obtain recalculation file semaphore', E_USER_ERROR);

        $starttime = time();
        
        // Read lines from file
        $queue = array(); $lineno = 0;
        $fh_in = fopen(Platform::getConfiguration('dir_temp').'recalculation.current', 'r');
        $fh_out = false;
        while ($line = fgets($fh_in)) {
            // If we exceed max number of lines, write the rest of the lines to a new file
            if ($lineno == $MAX_LINES) $fh_out = fopen(Platform::getConfiguration('dir_temp').'recalculation.new', 'w');
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
        unlink(Platform::getConfiguration('dir_temp').'recalculation.current');
        if ($fh_out !== false) {
            $run_again = true;
            fclose($fh_out);
            // If we exceeded the number of lines, then move the rest of the lines back.
            rename(Platform::getConfiguration('dir_temp').'recalculation.new', Platform::getConfiguration('dir_temp').'recalculation.current');
        }
        Semaphore::release('platform_calculation_file');
        
        // Now start on some recalculation
        $flush_rest = false; $flush_count = 0;
        foreach ($queue as $class => $calculations) {
            foreach ($calculations as $calculation => $ids) {
                $ids = array_keys($ids);
                if (! $flush_rest && time() > $starttime + $MAX_RUNTIME) {
                    $fh_out = fopen(Platform::getConfiguration('dir_temp').'recalculation.new', 'w');
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
            if (file_exists(Platform::getConfiguration('dir_temp').'recalculation.current')) {
                $fh_in = fopen(Platform::getConfiguration('dir_temp').'recalculation.current', 'r');
                while ($line = fgets($fh_in)) {
                    fwrite($fh_out, $line);
                }
                fclose($fh_in);
                unlink(Platform::getConfiguration('dir_temp').'recalculation.current');
            }
            fclose($fh_out);
            // Move new file in place of old file
            rename(Platform::getConfiguration('dir_temp').'recalculation.new', Platform::getConfiguration('dir_temp').'recalculation.current');
            Semaphore::release('platform_calculation_file');
            $job->log('info', 'We flushed '.$flush_count.' lines back in the file, as we didn\'t have time to process them!', $job);
            $run_again = true;
        }
        Semaphore::release('platform_calculation');
        if ($run_again) {
            // Reschedule to run again
            $job = Job::getJob('Platform\\Data\\Datarecord', 'calculationJob', Job::FREQUENCY_ONCE);
            $job->save();
        }
    }
    
    /**
     * Ensure that the database can store this object
     * @return bool True if changes were made to the database
     */
    public static function ensureInDatabase() : bool {
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
                $fielddefinition = '`'.$key.'` '.self::getSQLFieldType($element['fieldtype']);
                if ($element['fieldtype'] == self::FIELDTYPE_KEY) {
                    $fielddefinition .= ' PRIMARY KEY';
                    if (! static::$manual_key) $fielddefinition .= ' AUTO_INCREMENT';
                }
                $fielddefinitions[] = $fielddefinition;
            }
            self::query("CREATE TABLE ".static::$database_table." (".implode(',',$fielddefinitions).") COLLATE='utf8mb4_unicode_ci'");
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
            foreach (static::$structure as $key => $element) {
                if (! isset($fields_in_database[$key]) && ! $element['store_in_metadata'] && $element['store_in_database'] !== false) {
                    // Create it
                    $definition = self::getSQLFieldType($element['fieldtype']);
                    if ($element['fieldtype'] == self::FIELDTYPE_KEY) {
                        $fielddefinition .= ' PRIMARY KEY';
                        if (! static::$manual_key) $fielddefinition .= ' AUTO_INCREMENT';
                    }
                    $default = isset($element['default_value']) ? ' DEFAULT '.self::getFieldForDatabase($key, $element['default_value']) : '';
                    self::query('ALTER TABLE '.static::$database_table.' ADD `'.$key.'` '.$definition.$default);
                    $changed = true;
                    
                    // As this field could have been represented in the metadata
                    // we'll try to copy it from the metadata.
                    $resultset = self::query("SELECT ".static::getKeyField().", ".$key.", metadata FROM ".static::$database_table);
                    while ($row = Database::getRow($resultset)) {
                        $metadata = $row['metadata'] ? json_decode($row['metadata'], true) : array();
                        if (isset($metadata[$key])) {
                            // There was something. Write it and unset metadata.
                            $value = $metadata[$key];
                            unset($metadata[$key]);
                            self::query("UPDATE ".static::$database_table." SET metadata = '".Database::escape(json_encode($metadata))."', ".self::getAssignmentForDatabase($key, $value)." WHERE ".static::getKeyField()." = ".$row[static::getKeyField()]);
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
                $element = static::$structure[$field_in_database['Field']];
                if ($field_in_database['Type'] != mb_substr(mb_strtolower(static::getSQLFieldType($element['fieldtype'])),0, mb_strlen($field_in_database['Type']))) {
                    //echo 'Type '.$fieldindatabase['Type'].' isnt '.mb_strtolower(self::getSQLFieldType($element['fieldtype']));
                    //self::query('ALTER TABLE '.static::$database_table.' CHANGE COLUMN '.$field_in_database['Field'].' '.$field_in_database['Field'].' '.static::getSQLFieldType($element['fieldtype']));
                    self::query('ALTER TABLE '.static::$database_table.' DROP `'.$field_in_database['Field'].'`');
                    $default = $element['default_value'] ? ' DEFAULT '.self::getFieldForDatabase($key, $element['default_value']) : '';
                    self::query('ALTER TABLE '.static::$database_table.' ADD `'.$field_in_database['Field'].'` '.static::getSQLFieldType($element['fieldtype']).$default);
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
        foreach (static::$structure as $fieldname => $definition) {
            if ($definition['key']) {
                $key_name = $fieldname.'_key';
                if ($definition['key'] === true) {
                    $key_fields = array($fieldname);
                } else {
                    $key_fields = array($fieldname);
                    foreach (explode(',',$definition['key']) as $key_fieldname) {
                        $key_fields[] = trim($key_fieldname);
                    }
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
            if (! static::$structure[$first_field]['key']) {
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
        foreach ($this->getStructure() as $key => $definition) {
            if (isset($definition['default_value'])) $this->setValue($key, $definition['default_value']);
        }
    }

    /**
     * 
     * @param string $keywords Keywords to search for
     * @param string $output Output format. Either "DatarecordCollection" (default)
     * "array" or "autocomplete"
     * @return type
     */
    public static function findByKeywords(string $keywords, string $output = 'DatarecordCollection', $additional_filter = null) {
        // Backward compatibility
        if ($output == 'DatarecordCollection') $output = 'Collection';
        if (! in_array($output, array('Collection', 'array', 'autocomplete'))) trigger_error('Invalid output format', E_USER_ERROR);
        $search_fields = array();
        $numeric_fields = [];
        // Locate search fields
        $structure = static::getStructure();
        foreach ($structure as $field => $definition) {
            if ($definition['searchable'] || $definition['is_title']) {
                $search_fields[] = $field;
            }
            if (in_array($definition['fieldtype'], [static::FIELDTYPE_KEY, static::FIELDTYPE_INTEGER, static::FIELDTYPE_FLOAT])) $numeric_fields[] = $field;
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
                if (in_array($structure[$fieldname]['fieldtype'], [static::FIELDTYPE_REFERENCE_SINGLE, static::FIELDTYPE_REFERENCE_MULTIPLE])) {
                    if (! self::$find_by_keyword_in_progress) {
                        self::$find_by_keyword_in_progress = true;
                        // Reference-search
                        $foreign_class = $structure[$fieldname]['foreign_class'];
                        $foreign_matches = $foreign_class::findByKeywords($keywords);
                        $condition = new ConditionOneOf($fieldname, $foreign_matches->getAllIDs());
                        if ($previouscondition) $condition = new ConditionOR($condition, $previouscondition);
                        else $previouscondition = $condition;
                        self::$find_by_keyword_in_progress = false;
                    }
                } else {
                    if (in_array($fieldname, $numeric_fields)) $condition = new ConditionMatch($fieldname, $keyword);
                    else $condition = new ConditionLike($fieldname, $keyword);
                    if ($previouscondition) $condition = new ConditionOR($condition, $previouscondition);
                    $previouscondition = $condition;
                }
            }
            $filter->addCondition($condition);
        }
        $filter->setPerformAccessCheck(true);
        $results = $filter->execute();
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
     * @return Collection
     */
    public static function getAll() : Collection {
        $filter = new Filter(get_called_class());
        if (in_array(static::$delete_mode, [self::DELETE_MODE_EMPTY, self::DELETE_MODE_MARK])) $filter->addCondition(new ConditionMatch('is_deleted', 0));
        return $filter->execute();
    }

    /**
     * Get all objects of this type as an array hashed by key
     * @return array
     */
    public static function getAllAsArray() : array {
        $result = []; $sort_area = []; $ids = [];
        $filter = new Filter(get_called_class());
        if (in_array(static::$delete_mode, [self::DELETE_MODE_EMPTY, self::DELETE_MODE_MARK])) $filter->addCondition(new ConditionMatch('is_deleted', 0));
        $datacollection = $filter->execute();
        foreach ($datacollection->getAll() as $element) {
            $id = $element->getRawValue(static::getKeyField());
            $result[$id] = $element;
            $sort_area[] = strip_tags($element->getTitle());
            $ids[] = $id;
        }
        array_multisort($sort_area, SORT_ASC, $result, $ids);
        $result = array_combine($ids, $result);
        return $result;
    }
        
    /**
     * Get title of all objects as an array hashed by key
     * @return array
     */
    public static function getTitleAsArray() : array {
        $result = array();
        $filter = new Filter(get_called_class());
        if (in_array(static::$delete_mode, [self::DELETE_MODE_EMPTY, self::DELETE_MODE_MARK])) $filter->addCondition(new ConditionMatch('is_deleted', 0));
        $datacollection = $filter->execute();
        foreach ($datacollection->getAll() as $element) {
            $id = $element->getRawValue(static::getKeyField());
            $title = strip_tags($element->getTitle());
            self::$foreign_reference_buffer[get_called_class()][$id] = $title;
            $result[$id] = strip_tags($title);
        }
        asort($result);
        return $result;
    }
    
    /**
     * Get object fields as an array
     * @param array $fields Fields to include (or empty array for all fields)
     * @param int $render_mode Way to render fields
     * @return array
     */
    public function getAsArray(array $fields = array(), int $render_mode = -999) : array {
        if ($render_mode == -999) $render_mode = $this->default_rendermode;
        if (! self::checkRenderMode($render_mode)) trigger_error('Invalid render mode', E_USER_ERROR);
        if (! count($fields)) $fields = array_keys(static::$structure);
        $result = array();
        foreach ($fields as $field) {
            $result[$field] = $this->getValue($field, $render_mode);
        }
        return $result;
    }
    
    /**
     * Return all fields suitable for insertion into a form
     * @param $for_json Indicate if we should return values for JSON
     * @return array
     */
    public function getAsArrayForForm($for_json = false) : array {
        $result = array();
        foreach (static::$structure as $fieldname => $data) {
            if ($data['subfield']) continue;
            //if (($data['invisible'] || $data['readonly']) && $data['fieldtype'] != self::FIELDTYPE_KEY) continue;
            $field = static::getFormFieldForField($fieldname);
            if ($field === null) continue;
            $result[$fieldname] = $this->getFormValue($fieldname, $for_json);
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
        return "`$field`=".self::getFieldForDatabase($field, $value);
    }
    
    /**
     * Get all fields that were changed since this object was loaded. This only
     * returns fields which are saved in the database.
     */
    public function getChangedFields() : array {
        $result = array();
        foreach (static::$structure as $key => $definition) {
            if ($definition['store_in_database'] !== false && $this->values[$key] != $this->values_on_load[$key]) {
                $result[] = $key;
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
        $new_object->setFromArray($this->getAsArray(array(),self::RENDER_RAW));
        if (static::$title_field && $name_as_copy) $new_object->setValue(static::$title_field, 'Copy of '.$this->getRawValue(static::$title_field));
        return $new_object;
    }

    /**
     * Get the script to use with the edit form from this object
     * @return string
     */
    public static function getEditScript() : string {
        return static::$edit_script;
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
    public static function getFieldDefinition(string $field) : array {
        static::ensureStructure();
        if (! isset(static::$structure[$field])) trigger_error('Unknown field '.$field.' in object '.__CLASS__, E_USER_ERROR);
        return static::$structure[$field];
    }
    
    /**
     * Get field names of all fields referring to the given class
     * @param string $class Class name
     * @return array Field names
     */
    public static function getFieldsRelatingTo(string $class) : array {
        static::ensureStructure();
        $result = array();
        foreach (static::$structure as $fieldname => $definition) {
            if (in_array($definition['fieldtype'], array(self::FIELDTYPE_REFERENCE_SINGLE, self::FIELDTYPE_REFERENCE_MULTIPLE)) && $definition['foreign_class'] == $class)
                $result[] = $fieldname;
        }
        return $result;
    }
    
    /**
     * Get a field coded for the database with proper escaping and encapsulation
     * @param string $field Field name
     * @param mixed $value Field value
     * @return string The encoded field
     */
    public static function getFieldForDatabase(string $field, $value) {
        switch (static::$structure[$field]['fieldtype']) {
            case self::FIELDTYPE_INTEGER:
            case self::FIELDTYPE_ENUMERATION:
            case self::FIELDTYPE_FILE:
            case self::FIELDTYPE_IMAGE:
            case self::FIELDTYPE_REFERENCE_SINGLE:
            case self::FIELDTYPE_KEY:
                return $value === null ? 'NULL' : (int)$value;
            case self::FIELDTYPE_BOOLEAN:
                return $value ? 1 : 0;
            case self::FIELDTYPE_FLOAT:
                return $value === null ? 'NULL' : (double)$value;
            case self::FIELDTYPE_ARRAY:
                if (is_array($value)) {
                    $finalvalue = array();
                    foreach ($value as $k => $v) $finalvalue[$k] = $v;
                } else {
                    $finalvalue = [];
                }
                return '\''.Database::escape(json_encode($finalvalue)).'\'';
            case self::FIELDTYPE_REFERENCE_MULTIPLE:
            case self::FIELDTYPE_ENUMERATION_MULTI:
                // Force string encoding
                if (is_array($value)) {
                    $finalvalue = array();
                    foreach ($value as $k => $v) $finalvalue[$k] = (string)$v;
                } else {
                    $finalvalue = [];
                }
                return '\''.Database::escape(json_encode($finalvalue)).'\'';
            case self::FIELDTYPE_OBJECT:
                return '\''.Database::escape(serialize($value)).'\'';
            case self::FIELDTYPE_REPETITION:
                return $value === null ? 'NULL' : '\''.Database::escape(json_encode($value->getAsArray())).'\'';
            case self::FIELDTYPE_DATETIME:
            case self::FIELDTYPE_DATE:
                $datetime = new Time($value);
                return $datetime->getTimestamp() !== null ? '\''.$datetime->get().'\'' : 'NULL';
            default:
                return '\''.Database::escape((string)$value).'\'';
        }
    }
    
    /**
     * Get a form suitable for editing this object
     * @return Form
     */
    public static function getForm() : Form {
        static::ensureStructure();
        $baseclass = strtolower(strpos(get_called_class(), '\\') !== false ? substr(get_called_class(), strrpos(get_called_class(), '\\')+1) : get_called_class());
        // Build form
        $form = Form::Form($baseclass.'_form');
        $form->setEvent('save_'.$baseclass);
        $script = self::getEditScript();
        if ($script) $form->setScript($script);
        foreach (static::$structure as $key => $definition) {
            if ($definition['readonly'] || $key == 'metadata' || $definition['subfield']) continue;
            $field = static::getFormFieldForField($key);
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
     * Get a proper form field for the specific field
     * @param string $field The field
     * @return Field A form field
     */
    public static function getFormFieldForField(string $field) {
        if (! isset(static::$structure[$field])) trigger_error('Unknown field '.$field, E_USER_ERROR);
        return static::getFormFieldFromDefinition($field, static::$structure[$field]);
    }
    
    /**
     * Return an input field suitable for handling the given field type
     * @param string $name Field name
     * @param array $definition Field definition
     * @return Field
     */
    public static function getFormFieldFromDefinition(string $name, array $definition) {
        $options = array();
        if ($definition['required']) $options['required'] = true;
        
        // Some fields never yields a form field
        if (in_array($definition['fieldtype'], [self::FIELDTYPE_OBJECT])) return null;
        
        if ($definition['invisible']) {
            // Some elements cannot be represented as hidden fields
            if (in_array($definition['fieldtype'], [self::FIELDTYPE_ARRAY, self::FIELDTYPE_REFERENCE_MULTIPLE, self::FIELDTYPE_REFERENCE_HYPER])) return null;
            return HiddenField::Field('', $name);
        }
        
        if ($definition['layout_group']) {
            $options['group'] = $definition['layout_group'];
        }
        
        switch ($definition['fieldtype']) {
            case self::FIELDTYPE_KEY:
                return HiddenField::Field('', $name);
            case self::FIELDTYPE_TEXT:
                return TextField::Field($definition['label'], $name, $options);
            case self::FIELDTYPE_BIGTEXT:
                return TextareaField::Field($definition['label'], $name, $options);
            case self::FIELDTYPE_HTMLTEXT:
                return TexteditorField::Field($definition['label'], $name, $options);
            case self::FIELDTYPE_EMAIL:
                return EmailField::Field($definition['label'], $name, $options);
            case self::FIELDTYPE_PASSWORD:
                return PasswordField::Field($definition['label'], $name, $options);
            case self::FIELDTYPE_INTEGER:
                return NumberField::Field($definition['label'], $name, $options);
            case self::FIELDTYPE_FLOAT:
                $options['step'] = 'any';
                return NumberField::Field($definition['label'], $name, $options);
            case self::FIELDTYPE_BOOLEAN:
                return CheckboxField::Field($definition['label'], $name, $options);
            case self::FIELDTYPE_DATETIME:
                return DatetimeField::Field($definition['label'], $name, $options);
            case self::FIELDTYPE_DATE:
                return DateField::Field($definition['label'], $name, $options);
            case self::FIELDTYPE_FILE:
                return FileField::Field($definition['label'], $name, $options);
            case self::FIELDTYPE_IMAGE:
                $options['images_only'] = true;
                return FileField::Field($definition['label'], $name, $options);
            case self::FIELDTYPE_CURRENCY:
                return CurrencyField::Field($definition['label'], $name, $options);
            case self::FIELDTYPE_ADDRESS:
                return AddressField::Field($definition['label'], $name, $options);
            case self::FIELDTYPE_REPETITION:
                return RepetitionField::Field($definition['label'], $name, $options);
            case self::FIELDTYPE_REFERENCE_SINGLE:
                $options['datarecord_class'] = $definition['foreign_class'];
                return DatarecordcomboboxField::Field($definition['label'], $name, $options);
            case self::FIELDTYPE_ENUMERATION:
                $options['options'] = $definition['enumeration'];
                return SelectField::Field($definition['label'], $name, $options);
            case self::FIELDTYPE_ENUMERATION_MULTI:
                $options['options'] = $definition['enumeration'];
                return MulticheckboxField::Field($definition['label'], $name, $options);
            case self::FIELDTYPE_REFERENCE_MULTIPLE:
                $options['datarecord_class'] = $definition['foreign_class'];
                return MultidatarecordcomboboxField::Field($definition['label'], $name, $options);
            case self::FIELDTYPE_ARRAY:
                if (is_array($definition['substructure'])) {
                    $multiplier_section = MultiplierSection::Field($definition['label'], $name, $options);
                    foreach ($definition['substructure'] as $key => $substructure_element) {
                        if (! is_array($substructure_element)) trigger_error('Could not parse substructure element', E_USER_ERROR);
                        $multiplier_section->addFields(static::getFormFieldFromDefinition($key, $substructure_element));
                    }
                    return $multiplier_section;
                } else {
                    $multi_field = MultiField::MultiField(TextField::Field($definition['label'], $name, $options));
                    return $multi_field;
                }
        }
        return null;
    }
    
    /**
     * Get a value suitable for a form from this object
     * @param string $field Field name
     * @param bool $for_json Indicate if the returned value should be JSON compatible
     * @return mixed 
     */
    public function getFormValue(string $field, bool $for_json = false) {
        if (! isset(static::$structure[$field])) return null;
        return $this->getFormValueByDefinition($this->getRawValue($field), static::$structure[$field], $for_json);
    }

    /**
     * Get a form value directly from a definition
     * @param mixed $value Raw value
     * @param array $definition Definition
     * @param bool $for_json Indicate if the returned value should be JSON compatible
     * @return mixed The value as appropriate for the form
     */
    public function getFormValueByDefinition($value, array $definition, bool $for_json = false) {
        if ($definition['invisible']) return $value;
        switch ($definition['fieldtype']) {
            case self::FIELDTYPE_PASSWORD:
                return $value ? 'XXXXXX' : '';
            case self::FIELDTYPE_REFERENCE_SINGLE:
                $visual = '';
                if ($value) {
                    // Resolve foreign class 
                    $object = new $definition['foreign_class']();
                    $object->loadForRead($value);
                    $visual = $object->getTitle();
                }
                return array('id' => $value, 'visual' => strip_tags($visual));
            case self::FIELDTYPE_BOOLEAN:
                return $value ? 1 : 0;
            case self::FIELDTYPE_REFERENCE_MULTIPLE:
                // Bail of no values
                if (! count($value)) return array();
                // We need to retrieve all the referred values
                $class = $definition['foreign_class'];
                $filter = new Filter($class);
                $filter->addCondition(new ConditionOneOf($class::getKeyField(), $value));
                $values = array();
                foreach ($filter->execute()->getAll() as $foreignobject) {
                    $values[] = array('id' => $foreignobject->getRawValue($class::getKeyField()), 'visual' => strip_tags($foreignobject->getTitle()));
                }
                return $values;
            case self::FIELDTYPE_DATETIME:
                return $for_json ? str_replace(' ', 'T', $value->getReadable('Y-m-d H:i')) : $value;
            case self::FIELDTYPE_DATE:
                return $for_json ? $value->get('Y-m-d') : $value;
            case self::FIELDTYPE_ENUMERATION:
            case self::FIELDTYPE_ENUMERATION_MULTI:
            case self::FIELDTYPE_HTMLTEXT:
            case self::FIELDTYPE_CURRENCY:
            case self::FIELDTYPE_ADDRESS:
                return $value;
            case self::FIELDTYPE_REPETITION:
                return $value === null ? null : $value->getAsArray();
            case self::FIELDTYPE_FILE:
            case self::FIELDTYPE_IMAGE:
                return (int)$value;
            case self::FIELDTYPE_ARRAY:
                if ($definition['substructure']) {
                    $result = [];
                    foreach ($value as $v) {
                        $subresult = [];
                        foreach ($definition['substructure'] as $key => $subdefinition) {
                            $subresult[$key] = $this->getFormValueByDefinition($v[$key], $subdefinition, $for_json);
                        }
                        $result[] = $subresult;
                    }
                    return $result;
                } else {
                    return $value;
                }
            case self::FIELDTYPE_OBJECT:
                return $value;
            default:
                return $value;
        }        
    }
    
    /**
     * Get a fully formatted value from this object
     * @param string $field Field name
     * @return string Formatted string, this may be HTML
     */
    public function getFullValue(string $field) {
        if (! isset(static::$structure[$field])) return null;
        switch (static::$structure[$field]['fieldtype']) {
            case self::FIELDTYPE_ARRAY:
                return implode(', ', $this->getRawValue($field));
            case self::FIELDTYPE_REFERENCE_SINGLE:
            case self::FIELDTYPE_REFERENCE_MULTIPLE:
            case self::FIELDTYPE_REFERENCE_HYPER:
            case self::FIELDTYPE_FILE:
            case self::FIELDTYPE_IMAGE:
                $result = $this->resolveForeignReferences($field);
                sort($result);
                return implode(', ', $result);
            case self::FIELDTYPE_EMAIL:
                return $this->getRawValue($field) ? '<a href="mailto:'.$this->getRawValue($field).'">'.$this->getRawValue($field).'</a>' : '';
            case self::FIELDTYPE_ENUMERATION:
                return $this->getRawValue($field) ? static::$structure[$field]['enumeration'][$this->getRawValue($field)] : '';
            case self::FIELDTYPE_ADDRESS:
                return AddressField::formatAddress($this, $field);
            case self::FIELDTYPE_CURRENCY:
                return $this->getRawValue($field.'_currency') ? $this->getRawValue($field.'_foreignvalue').' '.$this->getRawValue($field.'_currency') : $this->getRawValue($field.'_localvalue');
            case self::FIELDTYPE_REPETITION:
                return $this->getRawValue($field) !== null ? $this->getRawValue($field)->getDescription() : Translation::translateForUser('None');
            case self::FIELDTYPE_ENUMERATION_MULTI:
                $result = array();
                foreach ($this->getRawValue($field) as $item) {
                    $result[] = static::$structure[$field]['enumeration'][$item];
                }
                sort($result);
                return implode(', ',$result);
            case self::FIELDTYPE_DATETIME:
                return $this->getRawValue($field)->getReadable();
            case self::FIELDTYPE_DATE:
                return $this->getRawValue($field)->getReadableDate();
            case self::FIELDTYPE_PASSWORD:
                return $this->getRawValue($field) ? '---' : '';
            case self::FIELDTYPE_TEXT:
            case self::FIELDTYPE_BIGTEXT:
                return str_replace("\n", '<br>', $this->getRawValue($field));
            case self::FIELDTYPE_BOOLEAN:
                return $this->getRawValue($field) ? 'Yes' : 'No';
            case self::FIELDTYPE_OBJECT:
                return '';
            default:
                return $this->getRawValue($field);
        }
    }
    
    /**
     * Return the object pointed to by this field
     * @param string $field Field name
     * @return Object referenced
     */
    public function getForeignObject(string $field) {
        $raw = $this->getRawValue($field);
        if (! $raw) $raw = 0;
        switch (static::$structure[$field]['fieldtype']) {
            case self::FIELDTYPE_REFERENCE_HYPER:
                $class = $raw['foreign_class'];
                $object = new $class();
                $object->loadForRead($raw['reference']);
                return $object;
            case self::FIELDTYPE_REFERENCE_SINGLE:
                $class = static::$structure[$field]['foreign_class'];
                $object = new $class();
                $object->loadForRead($raw, false);
                return $object;
            case self::FIELDTYPE_FILE:
            case self::FIELDTYPE_IMAGE:
                $object = new File();
                $object->loadForRead($raw, false);
                $object->folder = static::$structure[$field]['folder'];
                return $object;
            case self::FIELDTYPE_REFERENCE_MULTIPLE:
                $result = [];
                $class = static::$structure[$field]['foreign_class'];
                foreach ($raw as $value) {
                    $object = new $class();
                    $object->loadForRead($value, false);
                    $result[] = $object;
                }
                return $result;
            default:
                return null;
        }
    }
    
    /**
     * Get the key field of this datarecord.
     * @return bool|string Name of key field or false if no key field was
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
     * Get a simple class name (without namespace and in lowercase) for this class
     * @return string Simple name
     */
    public static function getClassName() : string {
        $class = strtolower(get_called_class());
        if (strpos($class, '\\')) $class = substr($class,strrpos($class,'\\')+1);
        return $class;
    }
    
    /**
     * Get the value of a field to be used as a log field
     * @param string $field Field name
     * @param bool $use_value_on_load If true then use the values as they were when loaded
     * @return type
     */
    public function getLogValue(string $field, bool $use_value_on_load = false) {
        if (! isset(static::$structure[$field])) return null;

        // Check if we should switch the value on load in place
        if ($use_value_on_load) {
            $stored_values = $this->values;
            $this->values = $this->values_on_load;
        }
        switch (static::$structure[$field]['fieldtype']) {
            case self::FIELDTYPE_ARRAY:
            case self::FIELDTYPE_FILE:
                $result = $this->getTextValue($field);
                break;
            case self::FIELDTYPE_DATE:
            case self::FIELDTYPE_DATETIME:
                $result = $this->getRawValue($field)->get();
                break;
            case self::FIELDTYPE_REFERENCE_MULTIPLE:
            case self::FIELDTYPE_OBJECT:
            case self::FIELDTYPE_ENUMERATION_MULTI:
            case self::FIELDTYPE_REPETITION:
                $result = json_encode($this->getRawValue($field));
                break;
            default:
                $result = $this->getRawValue($field);
                break;
        }
        
        // Check if we should switch back
        if ($use_value_on_load) {
            $this->values = $stored_values;
        }
        if ($result === null) $result = '[NULL]';
        if (strlen($result) > 200) $result = substr($result,0, 200).'...(+'.(strlen($result)-200).' bytes)';
        $result = '"'.str_replace('"', '\\"', $result).'"';
        return $result;
    }

    /**
     * Get the readable name of this object type. Defaults to class name if
     * no name is set.
     * @return string
     */
    public static function getObjectName() : string {
        return static::$object_name ?: static::getClassName();
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
            foreach ($referring_class::getStructure() as $key => $definition) {
                if (in_array($definition['fieldtype'], array(self::FIELDTYPE_REFERENCE_SINGLE, self::FIELDTYPE_REFERENCE_MULTIPLE)) && $definition['foreign_class'] == get_called_class()) {
                    $filter->addConditionOR(new ConditionMatch($key, $this->getRawValue($this->getKeyField())));
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
        return static::$title_field ? (string)$this->getFullValue(static::$title_field) : get_called_class().' (#'.$this->getValue(static::getKeyField(), self::RENDER_RAW).')';
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
        $class = get_called_class();
        $result = [];
        $missing_ids = [];
        // Try the buffer
        foreach ($ids as $id) {
            if (isset(self::$foreign_reference_buffer[$class][$id])) $result[$id] = self::$foreign_reference_buffer[$class][$id];
            else $missing_ids[] = $id;
        }
        // Populate missing items
        if (count($missing_ids)) {
            $filter = new Filter($class);
            $filter->addCondition(new ConditionOneOf($class::getKeyField(), $missing_ids));
            foreach ($filter->execute() as $object) {
                self::$foreign_reference_buffer[$class][$object->getKeyValue()] = $object->getTitle();
                $result[$object->getKeyValue()] = self::$foreign_reference_buffer[$class][$object->getKeyValue()];
            }
        }
        // Sort
        $sort_array = []; $ids = [];
        foreach ($result as $key => $value) {
            $ids[] = $key;
            $sort_array[] = strip_tags($value);
        }
        array_multisort($sort_array, SORT_ASC, $result, $ids);
        $result = array_combine($ids, $result);
        return $result;
    }
    
    /**
     * Get the title of an object of this type by ID.
     * @param int $id Object id
     * @return string Title
     */
    public static function getTitleById(int $id) : string {
        $class = get_called_class();
        // Try the buffer
        if (! isset(self::$foreign_reference_buffer[$class][$id])) {
            // Resolve (and add to buffer)
            $object = new $class();
            $object->loadForRead($id, false);
            self::$foreign_reference_buffer[$class][$id] = $object->getTitle();
        }
        return self::$foreign_reference_buffer[$class][$id];        
    }
    
    
    /**
     * Convert an internal field type to a MySQL field type
     * @param int $fieldtype Internal field type
     * @return string MySQL field type
     */
    private static function getSQLFieldType(int $fieldtype) : string {
        switch ($fieldtype) {
            case self::FIELDTYPE_DATE:
            case self::FIELDTYPE_DATETIME:
                return 'DATETIME';
            case self::FIELDTYPE_ARRAY:
            case self::FIELDTYPE_ENUMERATION_MULTI:
            case self::FIELDTYPE_OBJECT:
            case self::FIELDTYPE_REFERENCE_MULTIPLE:
            case self::FIELDTYPE_BIGTEXT:
            case self::FIELDTYPE_HTMLTEXT:
                return 'MEDIUMTEXT';
            case self::FIELDTYPE_INTEGER:
            case self::FIELDTYPE_KEY:
            case self::FIELDTYPE_FILE:
            case self::FIELDTYPE_IMAGE:
            case self::FIELDTYPE_REFERENCE_SINGLE:
            case self::FIELDTYPE_ENUMERATION:
                return 'INT(11)';
            case self::FIELDTYPE_BOOLEAN:
                return 'INT(1)';
            case self::FIELDTYPE_FLOAT:
                return 'DOUBLE';
            case self::FIELDTYPE_REPETITION:
                return 'VARCHAR(255)';
            default:
                return 'VARCHAR(255) NOT NULL';
        }
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
     * @return mixed Value
     */
    public function getRawValue(string $field) {
        if (! isset(static::$structure[$field])) return null;
        switch (static::$structure[$field]['fieldtype']) {
            case self::FIELDTYPE_ARRAY:
            case self::FIELDTYPE_ENUMERATION_MULTI:
            case self::FIELDTYPE_REFERENCE_MULTIPLE:
                return is_array($this->values[$field]) ? $this->values[$field] : array();
            case self::FIELDTYPE_DATETIME:
            case self::FIELDTYPE_DATE:
                return $this->values[$field] instanceof Time ? $this->values[$field] : new Time();
            case self::FIELDTYPE_REFERENCE_HYPER:
                return array('foreign_class' => $this->values[$field.'_foreign_class'], 'reference' => $this->values[$field.'_reference']);
            case self::FIELDTYPE_ENUMERATION:
                return $this->values[$field] ?: null;
            case self::FIELDTYPE_ADDRESS:
                $result = [];
                foreach (self::ADDRESS_FIELDS as $subfield)
                    $result[$subfield] = $this->values[$field.'_'.$subfield];
                return $result;
            case self::FIELDTYPE_CURRENCY:
                return [
                    'localvalue' => $this->values[$field.'_localvalue'],
                    'currency' => $this->values[$field.'_currency'],
                    'foreignvalue' => $this->values[$field.'_foreignvalue'],
                ];
            default:
                return $this->values[$field];
        }
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
        if (! isset(static::$structure[$field])) return '';
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
    public function getValue(string $field, int $rendermode = -999) {
        if ($rendermode == -999) $rendermode = $this->default_rendermode;
        if (! self::checkRenderMode($rendermode)) trigger_error('Invalid render mode', E_USER_ERROR);
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
    public static function getTableFields() : array {
        static::ensureStructure();
        $result = array();
        foreach (static::$structure as $key => $element) {
            if ($element['invisible'] || $element['columnvisibility'] == self::COLUMN_INVISIBLE) continue;
            $result[] = $key;
        }
        return $result;
    }
    
    /**
     * Check if this object have changed since it was loaded from the database.
     * @return bool
     */
    public function haveChanged() : bool {
        foreach (static::$structure as $key => $definition) {
            switch ($definition['fieldtype']) {
                case self::FIELDTYPE_ARRAY:
                case self::FIELDTYPE_ENUMERATION_MULTI:
                case self::FIELDTYPE_REFERENCE_MULTIPLE:
                    if (array_diff($this->values[$key], $this->values_on_load[$key]) || array_diff($this->values_on_load[$key], $this->values[$key])) return true;
                    break;
                case self::FIELDTYPE_DATETIME:
                    if (! $this->values[$key]->isEqualTo($this->values_on_load[$key])) return true;
                    break;
                default:
                    if ($this->values[$key] !== $this->values_on_load[$key]) return true;
                    break;
            }
        }
        return false;
    }
    
    /**
     * Check if this object have changed since it was loaded (in regards to fields
     * that are actually saved in the database).
     * @return bool
     */
    public function isChanged() : bool {
        foreach (static::$structure as $key => $definition) {
            if ($definition['store_in_database'] !== false && $this->values[$key] != $this->values_on_load[$key]) {
                return true;
            }
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
        return $this->values['is_deleted'] ? true : false;
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
        foreach (static::$structure as $key => $definition) {
            if (! $definition['store_in_metadata']) continue;
            switch ($definition['fieldtype']) {
                case self::FIELDTYPE_DATE:
                case self::FIELDTYPE_DATETIME:
                    if ($this->values[$key] && $this->values[$key] instanceof Time)
                        $metadata[$key] = $this->values[$key]->get();
                    break;
                default:
                    $metadata[$key] = $this->values[$key];
            }
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
        foreach ($databaserow as $key => $value) {
            // When reading a database row, we can encounter an extended field structure, so we 
            // skip fields we don't know about.
            if (! isset(static::$structure[$key])) continue;
            switch (static::$structure[$key]['fieldtype']) {
                case self::FIELDTYPE_KEY:
                case self::FIELDTYPE_PASSWORD:
                    $this->values[$key] = $value;
                    break;
                case self::FIELDTYPE_ARRAY:
                case self::FIELDTYPE_ENUMERATION_MULTI:
                case self::FIELDTYPE_REFERENCE_MULTIPLE:
                    // Handle null values
                    if ($value == '') $value = '[]';
                    $this->setValue($key, json_decode($value, true));
                    break;
                case self::FIELDTYPE_OBJECT:
                    if ($value === null) $this->setValue($key, null);
                    else $this->setValue($key, unserialize($value));
                    break;
                case self::FIELDTYPE_REPETITION:
                    if ($value === null) $this->setValue($key, null);
                    else $this->setValue($key, Repetition::constructFromArray(json_decode($value,true)));
                    break;
                case self::FIELDTYPE_DATE:
                case self::FIELDTYPE_DATETIME:
                    $this->setValue($key, new Time($value));
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
     * This function will populate the foreign reference buffer trying to find
     * all relevant data in a single pass.
     * @param string $class Class of the foreign object
     */
    private function populateForeignReferenceBuffer(string $class) {
        // Get attached collection or create a collection of just this
        $datarecords = $this->collection === false ? array($this) : $this->collection->getAll();
        
        // Locate all interesting ids
        $ids = array();
        foreach (static::$structure as $key => $definition) {
            if ($definition['foreign_class'] != $class && !(in_array($definition['fieldtype'], array(self::FIELDTYPE_IMAGE, self::FIELDTYPE_FILE)) && $class == 'Platform\\File') && $definition['fieldtype'] != self::FIELDTYPE_REFERENCE_HYPER) continue;
            foreach ($datarecords as $datarecord) {
                $values = $datarecord->getRawValue($key);
                if ($definition['fieldtype'] == self::FIELDTYPE_REFERENCE_HYPER) {
                    if ($values['foreign_class'] != $class) continue;
                    $values = $values['reference'];
                }
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
            $qh = $class::query("SELECT * FROM ".$class::getDatabaseTable()." WHERE ".$class::getKeyField()." IN (".implode(',',$missing).")");
            while ($qr = Database::getRow($qh)) {
                $foreign_datarecord = new $class();
                $foreign_datarecord->loadFromDatabaseRow($qr);
                self::$foreign_reference_buffer[$class][$qr[$class::getKeyField()]] = $foreign_datarecord->getTitle();
            }
        }
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
        $warnings = array();
        // Ensure newest version and test that we don't upgrade the database in excess.
        static::ensureInDatabase();
        $changed = static::ensureInDatabase();
        if ($changed) $errors[] = 'Database was changed even though there should be no changes. This is probably a problem with Platform.';
        
        // Check definitions
        $valid_definitions = array('enumeration', 'folder', 'foreign_class', 'layout_group', 'calculations', 'default_value', 'invisible',
            'fieldtype', 'label', 'columnvisibility', 'default', 'subfield',
            'is_title', 'key', 'required', 'readonly', 'searchable', 'store_in_database', 'store_in_metadata', 'table', 'tablegroup', 'substructure');
        
        foreach (static::getStructure() as $field => $definition) {
            foreach ($definition as $key => $value) {
                if (! in_array($key, $valid_definitions)) $errors[] = $field.': Property '.$key.' is not a valid Platform property.';
            }
            // Do some specific integrity
            if (in_array($definition['fieldtype'], [self::FIELDTYPE_ENUMERATION, self::FIELDTYPE_ENUMERATION_MULTI]) && ! is_array($definition['enumeration'])) $errors[] = $field.': Enumeration type without enumeration table.';
            if (! in_array($definition['fieldtype'], [self::FIELDTYPE_ENUMERATION, self::FIELDTYPE_ENUMERATION_MULTI]) && is_array($definition['enumeration'])) $errors[] = $field.': Enumeration table assigned to non-enumeration field.';
            if ($definition['foreign_class'] && ! in_array($definition['fieldtype'], array(self::FIELDTYPE_REFERENCE_SINGLE, self::FIELDTYPE_REFERENCE_MULTIPLE, self::FIELDTYPE_REFERENCE_HYPER))) $errors[] = $field.': A foreign class was provided but field is not relation type.';
        }
        
        // Check references
        foreach (static::getStructure() as $field => $definition) {
            switch ($definition['fieldtype']) {
                case self::FIELDTYPE_REFERENCE_SINGLE:
                case self::FIELDTYPE_REFERENCE_MULTIPLE:
                    if (! $definition['foreign_class']) $errors[] = $field.': Reference without foreign class';
                    elseif (!class_exists($definition['foreign_class'])) $errors[] = $field.': Reference to class which doesn\'t exists.';
                    else {
                        $match = false;
                        // Get array of all classes listed in the foreign class
                        $total_class_array = array_merge($definition['foreign_class']::$referring_classes, $definition['foreign_class']::$depending_classes);
                        // Loop and check if this class (or a parent class) is mentioned at least once.
                        foreach ($total_class_array as $class) {
                            if (is_subclass_of(get_called_class(), $class) || get_called_class() == $class) {
                                $match = true;
                                break;
                            }
                        }
                        if (! $match) $errors[] = 'Remote class '.$definition['foreign_class'].' doesn\'t list this as a referer or dependent class, even though we refer in field: <i>'.$field.'</i>.';
                    }
                    break;
            }
        }
        // Check referring classes
        foreach (static::$referring_classes as $foreign_class) {
            if (! class_exists($foreign_class)) $errors[] = 'Have <i>'.$foreign_class.'</i> as a referring class, but the class doesn\'t exist.';
            else {
                $hit = false;
                foreach ($foreign_class::getStructure() as $field => $definition) {
                    if ($definition['foreign_class'] == get_called_class() || is_subclass_of(get_called_class(), $definition['foreign_class'])) {
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
                foreach ($foreign_class::getStructure() as $field => $definition) {
                    if ($definition['foreign_class'] == get_called_class() || is_subclass_of(get_called_class(), $definition['foreign_class'])) {
                        $hit = true;
                        break;
                    }
                }
                if (! $hit) $errors[] = 'Have <i>'.$foreign_class.'</i> as a depending class, but that class doesn\'t refer this class.';
            }
        }        
        echo '<ul style="margin-top: 3px; margin-bottom: 5px; font-size: 0.8em;">';
        if (! count($errors) && ! count($warnings)) echo '<li><span style="color: green;">All OK</span>';
        foreach ($errors as $error) echo '<li><span style="color: red;">'.$error.'</span>';
        foreach ($warnings as $warning) echo '<li><span style="color: orange;">'.$warning.'</span>';
        echo '</ul>';
    }

    /**
     * Request a calculation
     * @param string $calculation Calculation to request
     * @return bool True if calculation was requested.
     */
    public function requestCalculation(string $calculation) : bool {
        if (! $this->isInDatabase()) return false;
        self::$requested_calculation_buffer[get_called_class()][$calculation][$this->getRawValue($this->getKeyField())] = true;
        return true;
    }

    /**
     * Reset all fields in object except the key, create date and change date
     */
    public function reset() {
        $fields_to_keep = array('create_date', 'change_date');
        foreach (static::$structure as $key => $field_definition) {
            if ($field_definition['fieldtype'] != self::FIELDTYPE_KEY && ! in_array($key, $fields_to_keep)) $this->setValue($key, null);
        }
    }
    
    /**
     * Resolve several foreign references (using caching)
     * @param string $field Name of field refering the foreign class
     * @return array Foreign object titles hashed by ids
     */
    public function resolveForeignReferences(string $field) : array {
        if (! in_array(static::$structure[$field]['fieldtype'], array(self::FIELDTYPE_REFERENCE_SINGLE, self::FIELDTYPE_REFERENCE_MULTIPLE, self::FIELDTYPE_REFERENCE_HYPER, self::FIELDTYPE_FILE, self::FIELDTYPE_IMAGE))) trigger_error('Tried to resolve a foreign reference on an incompatible field.', E_USER_ERROR);
        switch (static::$structure[$field]['fieldtype']) {
            case self::FIELDTYPE_FILE:
            case self::FIELDTYPE_IMAGE:
                $class = 'Platform\\File';
                $ids = array($this->getRawValue($field));
                break;
            case self::FIELDTYPE_REFERENCE_SINGLE:
                $class = static::$structure[$field]['foreign_class'];
                $ids = array($this->getRawValue($field));
            break;
            case self::FIELDTYPE_REFERENCE_MULTIPLE:
                $class = static::$structure[$field]['foreign_class'];
                $ids = $this->getRawValue($field);
            break;
            case self::FIELDTYPE_REFERENCE_HYPER:
                $class = $this->getRawValue($field.'_foreign_class');
                if (! $class) return [];
                $ids = array($this->getRawValue($field.'_reference'));
            break;
        }
        $missing = array();
        foreach ($ids as $id) {
            if (! $id) continue;
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
        // See if we should calculate anything
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
                        self::$requested_calculation_buffer[$definition['foreign_class']][$calculation][$foreign_id] = true;
                    }
                }
            }
        }
        
        $this->packMetadata();
        $this->setValue('change_date', new Time('now'));
        
        if ($this->isInDatabase()) {
            // Prepare update.
            $fielddefinitions = array();
            $fieldsToSave = $this->getChangedFields();
            if ($force_save)
                $fieldsToSave = array_keys(static::$structure);
            foreach ($fieldsToSave as $field) {
                $definition = $this->getFieldDefinition($field);
                if ($definition['store_in_metadata']) continue;
                $fielddefinitions[] = self::getAssignmentForDatabase($field, $this->values[$field]);
            }
            // In rare cases where you force save and to it twice within the same second, then there is nothing to update
            if (count($fielddefinitions)) {
                $sql = 'UPDATE '.static::$database_table.' SET '.implode(',',$fielddefinitions).' WHERE '.static::getKeyField().' = '.$this->values[static::getKeyField()];
                self::query($sql);
            }
            if (! $keep_open_for_write) $this->unlock();
        } else {
            $this->setValue('create_date', new Time('now'));
            $fieldlist = array(); $fieldvalues = array();
            foreach (static::$structure as $key => $definition) {
                if (! $definition['store_in_metadata'] && $definition['store_in_database'] !== false) {
                    $fieldlist[] = "`$key`"; 
                    $fieldvalues[] = ($definition['fieldtype'] == self::FIELDTYPE_KEY && ! static::$manual_key) ? 'NULL' : self::getFieldForDatabase($key, $this->values[$key]);
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
        if (static::$log_me) $this->logChange();
        $this->values_on_load = $this->values;
        
        // This is now in the database
        $this->is_in_database = true;
        
        // Update reference buffer
        self::$foreign_reference_buffer[get_called_class()][$this->values[static::getKeyField()]] = $this->getTitle();
        
        // Unlock a manual key semaphore (if any)
        Semaphore::release($manual_key_semaphore);
        
        if ($is_new_object) $this->onAfterCreate();
        $this->onAfterSave($changed_fields);
        
        return true;
    }
    
    /**
     * Save all requested calculations
     */
    public static function saveRequestedCalculations() {
        if (! count(self::$requested_calculation_buffer)) return;
        if (! Semaphore::wait('platform_calculation_file')) trigger_error('Could not obtain recalculation file semaphore', E_USER_ERROR);
        $fh = fopen(Platform::getConfiguration('dir_temp').'recalculation.current', 'a');
        foreach (self::$requested_calculation_buffer as $class => $calculations) {
            foreach ($calculations as $calculation => $ids) {
                fwrite($fh, $class.' '.$calculation.' '.implode(',',array_keys($ids))."\n");
            }
        }
        fclose($fh);
        Semaphore::release('platform_calculation_file');
        $job = Job::getJob('Platform\\Data\\Datarecord', 'calculationJob', Job::FREQUENCY_ONCE, false, 10, 15);
        $job->save();
    }
    
    /**
     * Set default render mode, when returning values for this object
     * @param int $rendermode
     */
    public function setDefaultRenderMode(int $rendermode) {
        if (! self::checkRenderMode($rendermode)) trigger_error('Invalid render mode', E_USER_ERROR);
        $this->default_rendermode = $rendermode;
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
        if (! isset(static::$structure[$field])) trigger_error('Tried setting invalid field: '.$field.' in class '. get_called_class(), E_USER_ERROR);
        switch (static::$structure[$field]['fieldtype']) {
            case self::FIELDTYPE_PASSWORD:
                if ($value === null) break;
                $this->values[$field] = $value ? md5($value.Platform::getConfiguration('password_salt')) : '';
                break;
            case self::FIELDTYPE_KEY:
                if (! static::$manual_key) return;
                // Runthrough intended.
            case self::FIELDTYPE_TEXT:
            case self::FIELDTYPE_EMAIL:
            case self::FIELDTYPE_OBJECT:
            case self::FIELDTYPE_BIGTEXT:
            case self::FIELDTYPE_HTMLTEXT:
                $this->values[$field] = $value;
                break;
            case self::FIELDTYPE_ENUMERATION:
                // Fail if trying to set invalid value.
                if ($value && ! isset(static::$structure[$field]['enumeration'][$value])) trigger_error('Tried to set invalid ENUMERATION value '.$value.' in field: '.$field, E_USER_ERROR);
                $this->values[$field] = (int)$value;
                break;
            case self::FIELDTYPE_ENUMERATION_MULTI:
                // Fail if trying to set invalid value.
                Errorhandler::checkParams($value, 'array');
                foreach ($value as $element)
                    if (! isset(static::$structure[$field]['enumeration'][$element])) trigger_error('Tried to set invalid ENUMERATION value '.$element.' in field: '.$field, E_USER_ERROR);
                $this->values[$field] = $value;
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
                if (is_object($value) && is_a($value, static::$structure[$field]['foreign_class'])) {
                    // An object of the desired class was passed. Extract ID and set it.
                    $this->values[$field] = $value->getValue($value->getKeyField());
                } else {
                    if (is_object($value) && $value instanceof Datarecord) trigger_error('Expected value of type '.static::$structure[$field]['foreign_class'].' but got '.get_class($value), E_USER_ERROR);
                    // We expect an ID
                    $this->values[$field] = is_numeric($value) ? (int)$value : null;
                }
                break;
            case self::FIELDTYPE_REFERENCE_MULTIPLE:
                if (! is_array($value)) $value = array($value);
                $final = array();
                foreach ($value as $v) {
                    if (is_numeric($v)) $final[] = (int)$v;
                    elseif (is_object($v) && $v !== null && is_a($v, static::$structure[$field]['foreign_class'])) $final[] = (int)$v->getKeyValue();
                    elseif ($v instanceof Datarecord) trigger_error('Expected value of type '.static::$structure[$field]['foreign_class'].' but got '.get_class($v), E_USER_ERROR);
                }
                $this->values[$field] = $final;
                break;
            case self::FIELDTYPE_ARRAY:
                $this->values[$field] = is_array($value) ? $value : array();
                break;
            case self::FIELDTYPE_DATETIME:
            case self::FIELDTYPE_DATE:
                // We assume that all string-based dates are given in the display time zone, so we parse from that.
                if (is_string($value) && ! is_numeric($value)) $this->values[$field] = Time::parseFromDisplayTime($value);
                else $this->values[$field] = new Time($value);
                break;
            case self::FIELDTYPE_ADDRESS:
                if (is_array($value)) {
                    foreach (self::ADDRESS_FIELDS as $subfield)
                        $this->values[$field.'_'.$subfield] = $value[$subfield];
                }
                break;
            case self::FIELDTYPE_CURRENCY:
                if (is_array($value)) {
                    if (! array_key_exists('localvalue', $value)) trigger_error('Invalid value passed to currency field. Missing localvalue from array.', E_USER_ERROR);
                    if (! array_key_exists('currency', $value)) trigger_error('Invalid value passed to currency field. Missing currency from array.', E_USER_ERROR);
                    if (! array_key_exists('foreignvalue', $value)) trigger_error('Invalid value passed to currency field. Missing foreignvalue from array.', E_USER_ERROR);
                    $this->values[$field.'_localvalue'] = $value['localvalue'];
                    $this->values[$field.'_currency'] = $value['currency'];
                    $this->values[$field.'_foreignvalue'] = $value['foreignvalue'];
                } else {
                    $this->values[$field.'_localvalue'] = $value;
                }
                break;
            case self::FIELDTYPE_REPETITION:
                if ($value === null) $this->values[$field] = null;
                else if ($value instanceof Repetition)  $this->values[$field] = $value;
                else if (is_array($value)) $this->values[$field] = Repetition::constructFromArray($value);
                else trigger_error('Invalid value passed to '.$field, E_USER_ERROR);
                break;
            case self::FIELDTYPE_FILE:
            case self::FIELDTYPE_IMAGE:
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
                        $file->save(false, true);
                    } else {
                        // We need to create a new file
                        $file = new File();
                        $file->filename = $value['original_file'];
                        $file->folder = static::$structure[$field]['folder'];
                        $file->mimetype = $value['mimetype'];
                        $folder = File::getFullFolderPath('temp');
                        $file->attachFile($folder.$value['temp_file']);
                        $file->save(false, true);
                        $this->values[$field] = $file->file_id;
                    }
                    if (static::$structure[$field]['fieldtype'] == self::FIELDTYPE_IMAGE && static::$structure[$field]['image_max_width'] && static::$structure[$field]['image_max_height']) {
                        $max_width = static::$structure[$field]['image_max_width'];
                        $max_height = static::$structure[$field]['image_max_height'];
                        $strategy = static::$structure[$field]['image_resize_strategy'] ?: Image::RESIZE_STRATEGY_FILL;
                        $image = new Image();
                        $result = $image->attachFile($file, true);
                        if ($result) {
                            $image->downsize($max_width, $max_height, $strategy);
                            $image->attachToFileAsPNG($file);
                            $file->save();
                        } else {
                            $file->unlock();
                        }
                    } else {
                        $file->unlock();
                    }
                } elseif ($value instanceof File) {
                    $this->values[$field] = $value->file_id;
                } else {
                    $this->values[$field] = $value;
                }
                break;
            case self::FIELDTYPE_REFERENCE_HYPER:
                if (is_array($value)) {
                    $this->setValue($field.'_foreign_class', $value['foreign_class']);
                    $this->setValue($field.'_reference', $value['reference']);
                } elseif ($value instanceof DatarecordReferable) {
                    $this->setValue($field.'_foreign_class', get_class($value));
                    $this->setValue($field.'_reference', $value->getRawValue($value->getKeyField()));
                }
                break;
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
    
    
    const FIELDTYPE_TEXT = 1;
    const FIELDTYPE_INTEGER = 2;
    const FIELDTYPE_FLOAT = 3;
    const FIELDTYPE_BOOLEAN = 4;
    const FIELDTYPE_BIGTEXT = 5;
    const FIELDTYPE_HTMLTEXT = 6;
    
    const FIELDTYPE_DATETIME = 10;
    const FIELDTYPE_DATE = 11;
    const FIELDTYPE_CURRENCY = 12;
    const FIELDTYPE_REPETITION = 13;
    
    const FIELDTYPE_EMAIL = 20;
    const FIELDTYPE_ADDRESS = 30;
    
    const FIELDTYPE_ARRAY = 100;
    const FIELDTYPE_OBJECT = 103;
    const FIELDTYPE_ENUMERATION = 101;
    const FIELDTYPE_ENUMERATION_MULTI = 102;
    
    
    const FIELDTYPE_PASSWORD = 300;
    
    const FIELDTYPE_FILE = 400;
    const FIELDTYPE_IMAGE = 401;
    
    const FIELDTYPE_REFERENCE_SINGLE = 500;
    const FIELDTYPE_REFERENCE_MULTIPLE = 501;
    const FIELDTYPE_REFERENCE_HYPER = 502;
    
    const FIELDTYPE_KEY = 9999;
}
