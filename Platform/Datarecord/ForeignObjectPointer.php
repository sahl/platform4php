<?php
namespace Platform\Datarecord;


class ForeignObjectPointer {
    
    private $foreign_class;
    
    private $foreign_id;
    
    /**
     * Construct a foreign object pointer
     * @param string $foreign_class Name of foreign class
     * @param int $foreign_id ID of foreign object
     */
    public function __construct(string $foreign_class, int $foreign_id) {
        if (!class_exists($foreign_class)) trigger_error('No such foreign class: '.$foreign_class, E_USER_ERROR);
        $this->foreign_class = $foreign_class;
        $this->foreign_id = $foreign_id;
    }
    
    /**
     * Get the foreign class pointed to by this
     * @return string
     */
    public function getForeignClass() : string {
        return $this->foreign_class;
    }
    
    /**
     * Get the foreign ID of the object pointed to
     * @return int
     */
    public function getForeignID() : int {
        return $this->foreign_id;
    }
    
    /**
     * Get the object pointed to by this pointer
     * @return Datarecord
     */
    public function getForeignObject() : DatarecordReferable {
        $object = new $this->foreign_class();
        $object->loadForRead($this->foreign_id);
        return $object;
    }
    
    /**
     * Returns an array of all unique pointers in the given array
     * @param array $foreign_object_pointers Array of ForeignObjectPointer
     * @return array
     */
    public static function getUniquePointers(array $foreign_object_pointers) {
        $result = []; $map = [];
        foreach ($foreign_object_pointers as $foreign_object_pointer) {
            // Skip of seen
            if (isset($map[$foreign_object_pointer->getForeignClass()][$foreign_object_pointer->getForeignID()])) continue;
            $result[] = $foreign_object_pointer;
            if (! is_array($map[$foreign_object_pointer->getForeignClass()])) $map[$foreign_object_pointer->getForeignClass()] = [];
            $map[$foreign_object_pointer->getForeignClass()][$foreign_object_pointer->getForeignID()] = true;
        }
        return $result;
    }
    
    /**
     * Examines if all pointers in an array points to the same class
     * @param array $foreign_object_pointers Array of ForeignObjectPointer
     * @return bool True if all pointers point to same class
     */
    public static function pointsToSameClass(array $foreign_object_pointers) : bool {
        $seen_class = null;
        foreach ($foreign_object_pointers as $foreign_object_pointer) {
            if ($seen_class == null) {
                $seen_class = $foreign_object_pointer->getForeignClass();
            } else {
                if ($seen_class != $foreign_object_pointer->getForeignClass()) return false;
            }
        }
        return true;
    }
}
