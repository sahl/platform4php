<?php
namespace Platform;

class Component {
    
    /**
     * Indicate if this component can be disabled.
     * @var boolean 
     */
    public static $can_disable = true;
    
    /**
     * Indicate if the component can redraw itself.
     * @var type 
     */
    protected static $can_redraw = true;
    
    /**
     * Indicate if this component should render
     * @var boolean
     */
    protected $can_render = true;
    
    /**
     * Classes
     * @var array
     */
    private $classes = array();
    
    /**
     * Internal counter used for assigning component IDs if not set
     * @var int 
     */
    private static $component_counter = 1;

    /**
     * Component ID for HTML
     * @var boolean|string 
     */
    private $component_id = false;
    
    /**
     * CSS files to use for this component
     * @var array
     */
    protected static $css_files = [];

    /**
     * Data for html tag
     * @var type 
     */
    protected $data = array();
   
    /**
     * Indicates if this component can only be used when logged in. (Relevant
     * when using the ajax reload.     * 
     * @var boolean
     */
    public static $is_secure = true;

    /**
     * List of javascript to load for this component
     * @var array
     */
    protected static $js_files = [];

    /**
     * Properties of the component
     * @var array
     */
    protected $properties = array();
    
    /**
     * URL used for component redrawing
     * @var string
     */
    protected static $redraw_url = '/Platform/Component/php/get_content.php';
    
    public function __construct() {
        foreach (static::$js_files as $js_file) Page::queueJSFile ($js_file);
        foreach (static::$css_files as $css_file) Page::queueCSSFile ($css_file);
        $this->prepareData();
    }

    /**
     * Read a property of the component
     * @param string $property Property name
     * @return mixed Property value
     */
    public function __get($property) {
        Errorhandler::checkParams($property, 'string');
        if (! array_key_exists($property, $this->properties)) trigger_error('Tried to read invalid property: '.$property, E_USER_ERROR);
        return $this->properties[$property];
    }
    
    /**
     * Set a property of the component
     * @param string $property Property name
     * @param mixed $value Property value
     */
    public function __set($property, $value) {
        Errorhandler::checkParams($property, 'string');
        if (! array_key_exists($property, $this->properties)) trigger_error('Tried to set invalid property: '.$property, E_USER_ERROR);
        $this->properties[$property] = $value;
    }

    /**
     * Add a class to this component
     * @param string $class Class name
     */
    public function addClass($class) {
        Errorhandler::checkParams($class, 'string');
        $this->classes[] = $class;
    }
    
    /**
     * Add a html data key/value pair
     * @param string $key
     * @param mixed $value
     */
    public function addData($key, $value) {
        Errorhandler::checkParams($key, 'string');
        $this->data[$key] = $value;
    }
    
    /**
     * Check if this component is allowed to render
     * @return boolean True if this component will render.
     */
    public function canRender() {
        return $this->can_render;
    }

    /**
     * Get HTML ID of this component
     * @return string
     */
    public function getID() {
        if ($this->component_id === false) {
            $this->component_id = 'platform_component_'.(self::$component_counter++);
        }
        return $this->component_id;
    }
    
    /**
     * Get a friendly name for this component, which is the class name without path.
     * @return string
     */
    public function getName() {
        $name = strtolower(get_called_class());
        if (strpos($name,'\\')) $name = substr($name,strrpos($name,'\\')+1);
        return $name;
    }
    
    /**
     * Override to prepare internal data in this component (if any)
     */
    public function prepareData() {
    }

    /**
     * Renders the component
     */
    public function render() {
        if (! $this->can_render) return;
        $classes = $this->classes;
        $classes[] = 'platform_component';
        $classes[] = 'platform_component_'.$this->getName();
        if (static::$can_disable) $classes[] = 'platform_component_candisable';
        
        if (static::$can_redraw) {
            $this->addData('redraw_url', static::$redraw_url);
            $this->addData('componentclass', get_called_class());
            $this->addData('componentproperties', base64_encode(serialize($this->properties)));
        }
        
        echo '<div class="'.implode(' ',$classes).'" id="'.$this->getID().'"';
        foreach ($this->data as $key => $data) {
            if (is_array($data)) $data = json_encode($data);
            echo ' data-'.$key.'="'.htmlentities($data, ENT_QUOTES).'"';
        }
        echo '>';
        $this->renderContent();
        echo '</div>';
    }
    
    /**
     * Render the component content. Override in subclass.
     */
    public function renderContent() {
        echo 'Override me';
    }
    
    /**
     * Set ID of component
     * @param string $id
     */
    public function setID($id) {
        Errorhandler::checkParams($id, 'string');
        $this->component_id = $id;
    }
    
    /**
     * Set the property map of this component
     * @param array $property_map
     */
    public function setPropertyMap($property_map) {
        Errorhandler::checkParams($property_map, 'array');
        $this->properties = $property_map;
    }
    
    /**
     * Set if this component should render.
     * @param boolean $render false to prevent render.
     */
    public function setRender($render) {
        Errorhandler::checkParams($render, 'boolean');
        $this->can_render = $render;
    }
}