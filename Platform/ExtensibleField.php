<?php
namespace Platform;

class ExtensibleField extends Datarecord {
    
    /**
     * Name of table in database
     * @var string 
     */
    protected static $database_table = 'platform_extensible_fields';
    
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
    protected static $title_field = false;
    
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
                'columnvisibility' => self::COLUMN_INVISIBLE,
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
    
    public function delete(bool $force_remove = false) : bool {
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
    
    public static function getForm() : Form {
        $form = parent::getForm();
        $form->addField(new \Platform\Form\HiddenField('', 'class', array('dont-clear' => true)));
        $form->replaceField(new \Platform\Form\SelectField(self::$structure['linked_class']['label'], 'linked_class', array('required' => true, 'options' => self::$remote_classes)), 'linked_class');
        return $form;
    }
    
    public function getTitle() : string {
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
    public static function renderEditComplexForClass(string $class, array $parameters = array()) {
        if (substr($class,0,1) == '\\') $class = substr($class,1);

        $filter = new Filter('\\Platform\\Data\\Extensiblefield');
        $filter->addCondition(new ConditionMatch('class', $class));
        $parameters['table']['filter'] = $filter;
        
        $parameters['form_function'] = function($form) use ($class) {
            $form->setValues(array('class' => $class));
        };
        Page::JSFile('/Platform/Datarecord/js/edit_extensible.js');
        static::renderEditComplex($parameters);
    }
    
    
    public function save(bool $force_save = false, bool $keep_open_for_write = false) : bool {
        // Obtain an order ID if we haven't got one.
        if (! $this->order_id) {
            $semaphore_name = 'DatarecordExtensibleFieldOrderId';
            if (!Semaphore::wait($semaphore_name)) trigger_error('Couldn\'t obtain '.$semaphore_name, E_USER_ERROR);
            $qr = Database::instanceFastQuery("SELECT MAX(order_id) as order_id FROM extensible_fields WHERE class = '".Database::escape($this->class)."'");
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
    public static function setRemoteClasses(array $classes) {
        foreach ($classes as $class) {
            self::$remote_classes[$class] = $class::getObjectName();
        }
    }
}