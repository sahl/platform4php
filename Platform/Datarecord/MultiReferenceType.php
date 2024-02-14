<?php
namespace Platform\Datarecord;
/**
 * Type class for reference to multiple object
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=type_class
 */


class MultiReferenceType extends Type {

    /**
     * Indicate if this field is a reference to other objects
     * @var bool
     */
    protected static $is_reference = true;

    /**
     * Name of foreign class pointed to by this field
     * @var string
     */
    protected $foreign_class = null;
    
    /**
     * Construct a field of this type
     * @param string $name Field name
     * @param string $title Field title
     * @param type $options Field options
     */
    public function __construct(string $name, string $title = '', array $options = []) {
        $valid_options = ['foreign_class'];
        foreach ($valid_options as $valid_option) {
            if ($options[$valid_option]) {
                $this->$valid_option = $options[$valid_option];
                unset($options[$valid_option]);
            }
        }
        if (! $this->foreign_class) trigger_error('You must specify a foreign class to the SingleReferenceType field', E_USER_ERROR);
        parent::__construct($name, $title, $options);
    }
    
    /**
     * Filter if a value is greater or equal than another value in regards to this type
     * @param mixed $value Value of this
     * @param mixed $other_value Value of other
     * @return bool
     */
    public function filterGreaterEqual($value, $other_value) {
        return false;
    }
    
    /**
     * Get SQL to determine if a field of this type is greater or equal than another value
     * @param mixed $value The other value
     * @return bool
     */
    public function filterGreaterEqualSQL($value) {
        return false;
    }
    
    /**
     * Filter if a value is greater than another value in regards to this type
     * @param mixed $value Value of this
     * @param mixed $other_value Value of other
     * @return bool
     */
    public function filterGreater($value, $other_value) {
        return false;
    }
    
    /**
     * Get SQL to determine if a field of this type is greater than another value
     * @param mixed $value The other value
     * @return bool
     */
    public function filterGreaterSQL($value) {
        return false;
    }
    
    /**
     * Filter if a value is set in regards to this type
     * @param mixed $value Value of this
     * @return bool
     */
    public function filterIsSet($value) {
        return count($value) > 0;
    }
    
    /**
     * Get SQL to determine if a field is set
     * @return bool
     */
    public function filterIsSetSQL() {
        return '`'.$this->name.'` IS NOT NULL';
    }
    
    /**
     * Filter if a value is like another value in regards to this type
     * @param mixed $value Value of this
     * @param mixed $other_value Value of other
     * @return bool
     */
    public function filterLike($value, $other_value) {
        if (SingleReferenceType::$like_search_in_progress) return false;
        SingleReferenceType::$like_search_in_progress = true;
        $result = $this->filterOneOf($value, $this->foreign_class::findByKeywords($other_value)->getAllIds());
        SingleReferenceType::$like_search_in_progress = false;
        return $result;
    }
    
    /**
     * Get SQL to determine if a field of this type is like another value
     * @param mixed $value The other value
     */
    public function filterLikeSQL($value) {
        if (SingleReferenceType::$like_search_in_progress) return 'FALSE';
        SingleReferenceType::$like_search_in_progress = true;
        $result = $this->filterOneOfSQL($this->foreign_class::findByKeywords($value)->getAllIds());
        SingleReferenceType::$like_search_in_progress = false;
        return $result;
    }
    
    /**
     * Filter if a value is lesser or equal than another value in regards to this type
     * @param mixed $value Value of this
     * @param mixed $other_value Value of other
     * @return bool
     */
    public function filterLesserEqual($value, $other_value) {
        return false;
    }
    
    /**
     * Get SQL to determine if a field of this type is lesser or equal than another value
     * @param mixed $value The other value
     * @return bool
     */
    public function filterLesserEqualSQL($value) {
        return false;
    }
    
    /**
     * Filter if a value is lesser than another value in regards to this type
     * @param mixed $value Value of this
     * @param mixed $other_value Value of other
     * @return bool
     */
    public function filterLesser($value, $other_value) {
        return false;
    }
    
    /**
     * Get SQL to determine if a field of this type is lesser than another value
     * @param mixed $value The other value
     * @return bool
     */
    public function filterLesserSQL($value) {
        return false;
    }
    
    /**
     * Filter if a value matches another value in regards to this type
     * @param mixed $value Value of this
     * @param mixed $other_value Value of other
     * @return bool
     */
    public function filterMatch($value, $other_value) {
        $other_value = $this->parseValue($other_value);
        return count(array_intersect($value,$other_value)) > 0;
    }
    
    /**
     * Get SQL to determine if a field of this type matches another value
     * @param mixed $value The other value
     * @return bool
     */
    public function filterMatchSQL($value) {
        $final_values = [];
        foreach ($this->parseValue($value) as $v) {
            $final_values[] = '`'.$this->name.'` LIKE \'%"'.((int)$v).'"%\'';
        }
        return '('.implode(' OR ', $final_values).')';
    }
    
    /**
     * Filter if a value is one of an array of other values
     * @param mixed $value Value of this
     * @param array $other_values Other values
     * @return bool
     */
    public function filterOneOf($value, array $other_values) {
        $final_values = [];
        foreach ($other_values as $other_value) {
            $final_values = array_merge($final_values, $this->parseValue($other_value));
        }
        return in_array($value, array_unique($final_values));
    }
    
    /**
     * Get SQL to determine if a field of this type is part of some values
     * @param mixed $values Other values
     * @return bool
     */
    public function filterOneOfSQL(array $values) {
        if (! count($values)) return 'FALSE';
        $sql_segments = [];
        foreach ($values as $value) {
            $sql_segments[] = $this->filterMatchSQL($value);
        }
        return '('.implode(' OR ', $sql_segments).')';
    }    
    
    /**
     * Format a value for the database in accordance to this type
     * @param mixed $value
     * @return string
     */
    public function getFieldForDatabase($value) : string {
        if (! count($value)) return 'NULL';
        $final_value = [];
        foreach ($value as $v) {
            // We code them as strings to make sure they are encapsulated.
            $final_value[] = (string)$v;
        }
        return "'".json_encode($final_value)."'";
    }
    
    /**
     * Get a form field for editing fields of this type
     * @return \Platform\Form\Field
     */
    public function getFormField() : ?\Platform\Form\Field {
        if ($this->isReadonly() || $this->isInvisible()) return null;
        $options = $this->getFormFieldOptions();
        $options['datarecord_class'] = $this->foreign_class;
        return \Platform\Form\MultidatarecordcomboboxField::Field($this->title, $this->name, $options);
    }
    
    /**
     * Format a value for a form in accordance to this type
     * @param mixed $value
     * @return mixed
     */
    public function getFormValue($value) {
        $result = [];
        foreach ($value as $id) {
            $title = TitleBuffer::getTitleByClassAndId($this->foreign_class, $id);
            if ($title !== null) $result[] = ['id' => $id, 'visual' => strip_tags($title)];
        }
        return $result;
    }
    
    /**
     * Format a value for final display in accordance to this type
     * @param mixed $value
     * @return string
     */
    public function getFullValue($value, Collection &$collection = null) : string {
        $result = []; $sorter = [];
        foreach ($value as $v) {
            $element = TitleBuffer::getBufferedTitle($this->foreign_class, $v);
            if ($element === false) {
                // We need to add more data to the buffer
                if ($collection !== null) {
                    $all_ids = [];
                    foreach ($collection->getAllRawValues($this->name) as $more_ids) {
                        $all_ids = array_unique(array_merge($all_ids, $this->parseValue($more_ids)));
                    }
                    $request = [$this->foreign_class => $all_ids];
                } else {
                    $request = [$this->foreign_class => $this->parseValue($value)];
                }
                TitleBuffer::populateBuffer($request);
                $element = TitleBuffer::getBufferedTitle($this->foreign_class, $v);
            }
            if ($element !== false) {
                $result[] = $element;
                $sorter[] = strip_tags($element);
            }
        }
        array_multisort($sorter, SORT_NATURAL, $result);
        return implode(', ',$result);
    }
    
    /**
     * Get the foreign objects pointed to by this field (if any)
     * @param mixed $value
     * @return array An array of ForeignObject
     */
    public function getForeignObjectPointers($value) : array {
        if ($value === null) return [];
        $result = [];
        foreach ($value as $v) {
            $result[] = new ForeignObjectPointer($this->foreign_class, $v);
        }
        return $result;
    }
    
    /**
     * Get the value for logging fields of this type
     * @param mixed $value
     * @return string
     */
    public function getLogValue($value) : string {
        if (! count($value)) return 'NONE';
        return $this->foreign_class.'(#'.implode(',',$value).')';
    }
    
    /**
     * Get all the options of this type as an array.
     * @return array
     */
    public function getOptionsAsArray() : array {
        $result = parent::getOptionsAsArray();
        $valid_options = ['foreign_class'];
        
        foreach ($valid_options as $option) {
            if ($this->$option != null) $result[$option] = $this->$option;
        }
        return $result;
    }
    
    /**
     * Get the raw value for fields of this type
     * @param type $value
     * @return type
     */
    public function getRawValue($value) {
        return $value;
    }
    
    /**
     * Check if fields of this type contains references to the given foreign class
     * @return bool
     */
    public function matchesForeignClass($foreign_class) : string {
        return $foreign_class == $this->foreign_class;
    }
    
    /**
     * Get the SQL field type for fields of this type
     * @return string
     */
    public function getSQLFieldType() : string {
        return 'MEDIUMTEXT';
    }
    
    /**
     * Get the textual value for fields of this type
     * @param mixed $value
     * @return string
     */
    public function getTextValue($value, Collection &$collection = null) : string {
        // TODO: This is slow as hell and should be fixed
        return strip_tags($this->getFullValue($value, $collection));
    }
    
    /**
     * Do an integrity check of this field
     * @return array
     */
    public function integrityCheck() : array {
        $result = [];
        if (! $this->foreign_class) $result[] = 'Missing foreign class';
        if (! class_exists($this->foreign_class)) $result[] = 'No such foreign class: '.$this->foreign_class;
        return $result;
    }
    
    /**
     * Parse a value of this type from the database
     * @param mixed $value
     * @return mixed
     */
    public function parseDatabaseValue($value) {
        if ($value === null) return [];
        $result = [];
        foreach (json_decode($value, true) as $v) $result[] = (int)$v;
        return $result;
    }
    
    /**
     * Parse a value of this type
     * @param $value The new value to set
     * @param $existing_value The existing value of this field (if any)
     * @return array
     */
    public function parseValue($value, $existing_value = null) {
        if (is_array($value)) {
            $result = []; 
            foreach ($value as $v) $result = array_merge($result, $this->parseValue($v));
            return array_unique($result);
        }
        if ($value instanceof Datarecord) return [(int)$value->getKeyValue()];
        return [(int)$value];
    }
    
    /**
     * Remove a reference to the given object from the value (if present)
     * @param mixed $value
     * @param Datarecord $object
     * @return mixed
     */
    public function removeReferenceToObject($value, Datarecord $object) {
        if ($object instanceof $this->foreign_class) \Platform\Utilities\Utilities::arrayRemove($value, $object->getKeyValue());
        return $value;
    }
    
    /**
     * Replaces a reference to the given object with a reference to a new object.
     * If the old object isn't referenced nothing is changed
     * @param type $value The existing value
     * @param Datarecord $old_object Old reference object
     * @param Datarecord $new_object New reference object
     * @return type The updated value
     */
    public function replaceReferenceToObject($value, Datarecord $old_object, Datarecord $new_object) {
        if ($this->filterMatch($value, $old_object)) {
            $value = $this->removeReferenceToObject($value, $old_object);
            $value[] = $new_object->getKeyValue();
            return $value;
        }
        return $value;
    }    
    
    /**
     * Get SQL sort or return false if we can't sort by SQL
     * @param bool $descending True if we need descending sort
     * @return string|bool Sort string or false if we can't sort.
     */
    public function getSQLSort(bool $descending = false) {
        return false;
    }
    
    /**
     * Validate if this is a valid value for fields of this type
     * @param mixed $value
     * @return bool
     */
    public function validateValue($value) {
        if ($value === null) return true;
        if (! is_array($value)) $value = [$value];
        foreach ($value as $v) {
            if ($v instanceof Datarecord) {
                if (! $v instanceof $this->foreign_class) return \Platform\Utilities\Translation::translateForUser('Incompatible object passed');
                if (! $v->isInDatabase()) return \Platform\Utilities\Translation::translateForUser('Unsaved object passed');
                continue;
            }
            $object = new $this->foreign_class();
            $object->loadForRead($v, false);
            if (! $object->isInDatabase()) return \Platform\Utilities\Translation::translateForUser('Invalid reference id %1', $value);
        }
        return true;
    }
    
}