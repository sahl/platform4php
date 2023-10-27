<?php
/**
 * A component which can refer a specific Datarecord Object
 * 
 */
namespace Platform\UI;

use Platform\Datarecord;

class ContextComponent extends Component {
    
    /**
     * The context object
     * @var Datarecord
     */
    protected $context_object = null;
    
    public function __construct() {
        parent::__construct();
        $this->addPropertyMap([
            'context_object_class' => '',
            'context_object_id' => 0
        ]);
    }
    
    /**
     * Attach a datarecord object for context to this component.
     * @param Datarecord $context_object
     */
    public function attachContextObject(Datarecord $context_object) {
        $this->context_object = $context_object;
        $this->context_object_class = get_class($context_object);
        $this->context_object_id = $context_object->getKeyValue();
    }
    
    /**
     * Get the context from another ContextComponent
     * @param ContextComponent $context_component
     */
    public function getContextFromComponent(ContextComponent $context_component) {
        $this->context_object_class = $context_component->context_object_class;
        $this->context_object_id = $context_component->context_object_id;
    }
    
    /**
     * Get the datarecord object which is the context of this component.
     * @return Datarecord
     */
    public function getContextObject() : Datarecord {
        if ($this->context_object == null) $this->loadContextObject();
        return $this->context_object;
    }
    
    /**
     * Load the actual datarecord object which is referred by this component
     */
    protected function loadContextObject() {
        if (!class_exists($this->context_object_class)) trigger_error('No valid context class', E_USER_ERROR);
        $this->context_object = new $this->context_object_class();
        if (! $this->context_object instanceof Datarecord) trigger_error('Context object must be a subclass of Datarecord', E_USER_ERROR);
        $this->context_object->loadForRead($this->context_object_id);
    }
    
    /**
     * Prepare data in this object
     */
    protected function prepareData() {
        parent::prepareData();
        // Ensure the context object is loaded.
        if ($this->context_object == null) $this->loadContextObject();
    }

}