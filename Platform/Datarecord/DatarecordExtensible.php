<?php
namespace Platform\Datarecord;

use Platform\Filter\ConditionMatch;
use Platform\Filter\Filter;
/**
 * An extension to Datarecord allowing for user configurable fields
 * 
 */

class DatarecordExtensible extends Datarecord {
    
    /**
     * Build additional structure based on information from database.
     */
    protected static function buildStructure() {
        // Only instance tables support extensible fields
        if (static::$location == static::LOCATION_INSTANCE) {
            $filter = new Filter('Platform\Datarecord\ExtensibleField');
            $filter->addCondition(new ConditionMatch('attached_class', get_called_class()));
            $filter->setOrderColumn('order_id');
            $fields = $filter->execute()->getAll();
            $additional_structure = [];
            foreach ($fields as $field) {
                $additional_structure[] = $field->getAsType();
            }
            self::addStructure($additional_structure);
        }
        // Remember to call parent
        parent::buildStructure();
    }
    
    /**
     * Add a extensible field to this class
     * @param Type $type_to_add
     */
    public static function addField(Type $type_to_add) {
        $extensible_field = $type_to_add->getAsExtensibleField();
        $extensible_field->attached_class = get_called_class();
        $extensible_field->save();
        static::ensureInDatabase();
    }
    
    public static function ensureInDatabase(bool $dry_run = false): bool {
        // As the structure can change during runtime, we clear the structure to make sure we read the newest one.
        static::clearStructure();
        return parent::ensureInDatabase($dry_run);
    }
    
    public static function removeFieldByName(string $field_name) {
        $filter = new Filter('Platform\Datarecord\ExtensibleField');
        $filter->addCondition(new ConditionMatch('attached_class', get_called_class()));
        $filter->addCondition(new ConditionMatch('field_name', $field_name));
        $collection = $filter->execute();
        $collection->deleteAll();
        static::ensureInDatabase();
        
    }
}
