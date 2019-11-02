<?php
namespace Platform;

class DatarecordExtensiblefield extends \Platform\Datarecord {
    
    /**
     * Name of table in database
     * @var string 
     */
    protected static $database_table = 'extensible_fields';
    
    /**
     * Set a delete strategy for this object
     * @var int
     */
    protected static $delete_strategy = self::DELETE_STRATEGY_BLOCK;
    
    protected static $object_name = 'Field';
    
    /**
     * Names of all classes referring this class
     * @var array 
     */
    protected static $referring_classes = array(
        
    );
    
    /**
     * Holds which remote classes can be selected in a relation field
     * @var array
     */
    protected static $remote_classes = array();

    /**
     * Indicate if this object is relevant for an instance or globally
     * @var int 
     */
    protected static $location = self::LOCATION_INSTANCE;

    protected static $structure = false;
    protected static $key_field = false;
    
    protected static function buildStructure() {
        $structure = array(
            'field_id' => array(
                'invisible' => true,
                'fieldtype' => self::FIELDTYPE_KEY
            ),
            'title' => array(
                'label' => 'Title',
                'required' => true,
                'fieldtype' => self::FIELDTYPE_TEXT
            ),
            'field_type' => array(
                'label' => 'Field type',
                'required' => true,
                'fieldtype' => self::FIELDTYPE_ENUMERATION,
                'enumeration' => self::getFieldTypes(),
            ),
            'linked_class' => array(
                'label' => 'Remote object',
                'required' => true,
                'table' => self::COLUMN_UNSELECTABLE,
                'fieldtype' => self::FIELDTYPE_TEXT
            ),
            'class' => array(
                'label' => 'Object',
                'invisible' => true,
                'fieldtype' => self::FIELDTYPE_TEXT
            ),
            'order_id' => array(
                'invisible' => true,
                'fieldtype' => self::FIELDTYPE_INTEGER
            ),
        );
        self::addStructure($structure);
        // Remember to call parent
        parent::buildStructure();
    }
    
    public function delete($force_remove = false) {
        if (parent::delete($force_remove)) {
            $class = '\\'.$this->class;
            $class::ensureInDatabase();
        }
    }
    
    /**
     * Get available field types for extensible fields
     * @return array
     */
    public static function getFieldTypes() {
        return array(
            Datarecord::FIELDTYPE_TEXT => 'Text',
            Datarecord::FIELDTYPE_FLOAT => 'Number',
            Datarecord::FIELDTYPE_BOOLEAN => 'Checkbox',
            Datarecord::FIELDTYPE_BIGTEXT => 'Large text',
            Datarecord::FIELDTYPE_REFERENCE_SINGLE => 'Relation',
            Datarecord::FIELDTYPE_REFERENCE_MULTIPLE => 'Multiple relations'
        );
    }
    
    public static function getForm() {
        $form = parent::getForm();
        $form->addField(new FieldHidden('', 'class', array('dont-clear' => true)));
        $form->replaceField(new FieldSelect(self::$structure['linked_class']['label'], 'linked_class', array('required' => true, 'options' => self::$remote_classes)), 'linked_class');
        return $form;
    }
    
    public function getTitle() {
        // Override to get a meaningful title of this object
        return $this->title;
    }
    
    /**
     * Render an edit complex for fields to add to the given class, which should
     * be of type DatarecordExtensible
     * TODO: Sorting of fields
     * @param string $class Class
     * @param array $parameters
     */
    public static function renderEditComplexForClass($class, $parameters = array()) {
        if (substr($class,0,1) == '\\') $class = substr($class,1);

        $filter = new \Platform\Filter('\\Platform\\DatarecordExtensiblefield');
        $filter->addCondition(new \Platform\FilterConditionMatch('class', $class));
        $parameters['filter'] = $filter;
        
        $parameters['form_function'] = function($form) use ($class) {
            $form->setValues(array('class' => $class));
        };
        Design::JSFile('/Platform/Datarecord/js/edit_extensible.js');
        static::renderEditComplex($parameters);
    }
    
    
    public function save($force_save = false, $keep_open_for_write = false) {
        // Obtain an order ID if we haven't got one.
        if (! $this->order_id) {
            $semaphore_name = 'DatarecordExtensibleFieldOrderId';
            if (!Semaphore::wait($semaphore_name)) trigger_error('Couldn\'t obtain '.$semaphore_name, E_USER_ERROR);
            $qr = fq("SELECT MAX(order_id) as order_id FROM extensible_fields WHERE class = '".esc($this->class)."'");
            $this->order_id = (int)$qr['order_id']+1;
            Semaphore::release($semaphore_name);
        }
        if (parent::save($force_save, $keep_open_for_write)) {
            // Ensure that the object is properly build
            $class = '\\'.$this->class;
            $class::ensureInDatabase();
        }
    }
    
    /**
     * Set which remote classes one should be able to link from a relation
     * extensible field
     * @param array $classes
     */
    public static function setRemoteClasses($classes) {
        foreach ($classes as $class) {
            self::$remote_classes[$class] = $class::getObjectName();
        }
    }
}