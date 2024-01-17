<?php
namespace Platform\Datarecord;

use Platform\Utilities\Translation;
/**
 * This stored extensible field definitions for the DatarecordExtensible class.
 *  
 */

class ExtensibleField extends Datarecord {
    
    protected static $database_table = 'platform_extensible_fields';
    protected static $delete_strategy = self::DELETE_STRATEGY_BLOCK;
    protected static $location = self::LOCATION_INSTANCE;
    
    protected static $depending_classes = [ ];
    protected static $referring_classes = [ ];

    protected static $structure = false;
    protected static $key_field = false;
    protected static $title_field = false;
    
    protected static function buildStructure() {
        static::addStructure([
            new KeyType('field_id'),
            new TextType('title', Translation::translateForUser('Title'), ['is_required' => true, 'is_title' => true]),
            new TextType('field_name', Translation::translateForUser('Field name'), ['is_required' => true]),
            new TextType('attached_class', '', ['is_invisible' => true]),
            new TextType('type_class', '', ['is_invisible' => true]),
            new ObjectType('properties', '', ['is_invisible' => true]),
            new IntegerType('order_id', '', ['is_invisible' => true]),
        ]);
        parent::buildStructure();
    }
    
    public function onCreate() : bool {
        $result = parent::onCreate();
        if (! $result) return false;
        // Check if field with same name (and class) exists
        $filter = new \Platform\Filter\Filter('Platform\Datarecord\ExtensibleField');
        $filter->conditionMatch('attached_class', $this->attached_class);
        $filter->conditionMatch('field_name', $this->field_name);
        $collection = $filter->execute();
        if ($collection->count()) trigger_error('Field with name '.$this->field_name.' already exists in '.$this->attached_class, E_USER_ERROR);
        // Find sort
        $filter = new \Platform\Filter\Filter('Platform\Datarecord\ExtensibleField');
        $filter->conditionMatch('attached_class', $this->attached_class);
        $filter->setOrderColumn('order_id', false);
        $max_field = $filter->executeAndGetFirst();
        $this->order_id = (int)$max_field->order_id+1;
        // Create name if missing
        if (! $this->field_name) $this->field_name = 'field_'.$this->order_id;
        return true;
    }
}