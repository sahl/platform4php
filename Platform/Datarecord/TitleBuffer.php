<?php
namespace Platform\Datarecord;

class TitleBuffer {
    
    private static $buffer = [];
    
    /**
     * Get a title from the buffer, but only if it is already buffered.
     * @param string $class_name Class name
     * @param int $id ID of record to fetch
     * @return mixed The title or false if the title isn't in the buffer
     */
    public static function getBufferedTitle(string $class_name, int $id) {
        if (array_key_exists($class_name, static::$buffer) && array_key_exists($id, static::$buffer[$class_name])) return static::$buffer[$class_name][$id];
        return false;
    }
    
    /**
     * Get a title from the buffer
     * @param string $class_name Class name
     * @param int $id ID of record to fetch
     * @return The title from the buffer
     */
    public static function getTitleByClassAndId(string $class_name, int $id) : string {
        static::populateBuffer([$class_name => [$id]]);
        return (string)static::getBufferedTitle($class_name, $id);
    }
    
    /**
     * Get several classes by name and ids
     * @param string $class_name
     * @param array $ids
     * @return array Titles hashed by IDs
     */
    public static function getTitlesByClassAndIds(string $class_name, array $ids) : array {
        static::populateBuffer([$class_name => $ids]);
        $result = []; $sort_array = [];
        foreach ($ids as $id) {
            $title = static::getBufferedTitle($class_name, $id);
            $result[] = $title;
            $sort_array[] = strip_tags($title);
        }
        array_multisort($sort_array, SORT_ASC, $result, $ids);
        $result = array_combine($ids, $result);
        return $result;
    }
    
    /**
     * Populate the buffer from the given request. The reques should be a nested array, where the outer array is hashed by
     * class names and the inner array is the needed ID's
     * @param array $request
     */
    public static function populateBuffer(array $request) {
        foreach ($request as $class_name => $ids) {
            // The ids can contain null values
            $cleaned_ids = [];
            foreach ($ids as $id) if (is_numeric($id)) $cleaned_ids[] = $id;
            if (!class_exists($class_name)) trigger_error('Unknown class '.$class_name.' passed to the TitleBuffer', E_USER_ERROR);
            if (array_key_exists($class_name, static::$buffer)) {
                // We have some IDs already, so only fetch the missing ones
                $fetch_ids = array_diff(array_unique($cleaned_ids), array_keys(static::$buffer[$class_name]));
            } else {
                // We don't have any IDs, so fetch them all
                $fetch_ids = array_unique($cleaned_ids);
            }
            if (! count($fetch_ids)) return;
            // Check if we are refering Datarecord classes
            $object = new $class_name();
            if ($object instanceof Datarecord) {
                $filter = new \Platform\Filter\Filter($class_name);
                $filter->conditionOneOf($class_name::getKeyField(), $fetch_ids);
                $objects = $filter->execute();
                foreach ($objects as $object) {
                    // Fill the buffer
                    static::$buffer[$class_name][$object->getKeyValue()] = $object->getTitle();
                }
            } elseif ($object instanceof DatarecordReferable) {
                foreach ($fetch_ids as $fetch_id) {
                    $object = new $class_name();
                    $object->loadForRead($fetch_id);
                    static::$buffer[$class_name][$object->getKeyValue()] = $object->getTitle();
                }
            } else {
                trigger_error('Class '.$class_name.' must be Datarecord or DatarecordReferable', E_USER_ERROR);
            }
        }
    }
    
    /**
     * Remove item from buffer
     * @param string $class_name Class name
     * @param int $id ID
     */
    public static function removeFromBuffer(string $class_name, int $id) {
        // We only update objects which are already buffered
        if (!array_key_exists($class_name, static::$buffer)) return;
        unset(static::$buffer[$class_name][$id]);
        if (count(static::$buffer[$class_name]) == 0) unset(static::$buffer[$class_name]);
    }
    
    
    /**
     * Update the buffer with a new title
     * @param string $class_name Class name
     * @param int $id ID
     * @param string $title New title to write
     */
    public static function updateBuffer(string $class_name, int $id, string $title) {
        // We only update objects which are already buffered
        if (!array_key_exists($class_name, static::$buffer)) return;
        static::$buffer[$class_name][$id] = $title;
    }
}