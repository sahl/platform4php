<?php
namespace Platform\Datarecord;
/**
 * Type class for describing an object. A substructure can be attached.
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=type_class
 */


class ObjectType extends ArrayType {
    
    /**
     * Format a value for the database in accordance to this type
     * @param mixed $value
     * @return string
     */
    public function getFieldForDatabase($value) : string {
        if (! count($value)) return 'NULL';
        return '\''. \Platform\Utilities\Database::escape(serialize($value)).'\'';
    }
    
    /**
     * Format a value for final display in accordance to this type
     * @param mixed $value
     * @param Collection An optional collection which can contain further records
     * @return string
     */
    public function getFullValue($value, Collection &$collection = null) : string {
        return \Platform\Utilities\Translation::translateForUser('Complex value');
    }
    
    /**
     * Get the textual value for fields of this type
     * @param mixed $value
     * @param Collection An optional collection which can contain further records
     * @return string
     */
    public function getTextValue($value, Collection &$collection = null) : string {
        return \Platform\Utilities\Translation::translateForUser('Complex value');
    }
    
    /**
     * Parse a value of this type from the database
     * @param mixed $value
     * @return mixed
     */
    public function parseDatabaseValue($value) {
        return unserialize($value);
    }
    
    /**
     * Do an integrity check of this field
     * @return array
     */
    public function integrityCheck() : array {
        return [];
    }
    
    /**
     * Get SQL sort or return false if we can't sort by SQL
     * @param bool $descending True if we need descending sort
     * @return string|bool Sort string or false if we can't sort.
     */
    public function getSQLSort(bool $descending = false) {
        return false;
    }
}

