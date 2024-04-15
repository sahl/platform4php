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
}
