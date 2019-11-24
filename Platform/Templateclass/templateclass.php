<?php
// namespace NAMESPACE;

class Templateclass extends \Platform\Datarecord {
    
    protected static $database_table = 'DATABASE TABLE';
    protected static $delete_strategy = self::DELETE_STRATEGY_BLOCK;
    protected static $referring_classes = array(
        
    );
    protected static $depending_classes = array(
        
    );

    protected static $location = self::LOCATION_INSTANCE;

    protected static $structure = false;
    protected static $key_field = false;
    
    protected static function buildStructure() {
        $structure = array(
            'object_id' => array(
                'invisible' => true,
                'fieldtype' => self::FIELDTYPE_KEY
            ),
            'property1' => array(
                'label' => 'Required property',
                'required' => true,
                'fieldtype' => self::FIELDTYPE_TEXT
            ),
            'property2' => array(
                'label' => 'Optional property',
                'fieldtype' => self::FIELDTYPE_TEXT
            ),
        );
        self::addStructure($structure);
        parent::buildStructure();
    }
    
    public function getTitle() {
        // Override to get a meaningful title of this object
        return $this->property1;
    }
    
    // Remember to add the ::ensureInDatabase to either the instance or
    // globally
}