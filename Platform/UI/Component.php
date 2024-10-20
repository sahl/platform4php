<?php
namespace Platform\UI;
/**
 * Base class for drawing components in Platform.
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=component_class
 */

use Platform\Page\Page;
use Platform\Form\Form;

class Component {
    
    /**
     * Indicate if this component can be disabled.
     * @var bool 
     */
    public static $can_disable = true;
    
    /**
     * Indicate if the component can redraw itself.
     * @var type 
     */
    protected static $can_redraw = true;
    
    /**
     * Indicate if this component should render
     * @var bool
     */
    protected $can_render = true;
    
    /**
     * Classes
     * @var array
     */
    private $classes = array();
    
    /**
     * The component class of this component
     * @var type
     */
    protected static $component_class = 'platform_component';

    /**
     * Component ID for HTML
     * @var bool|string 
     */
    private $component_id = false;
    
    
    /**
     * Data for html tag
     * @var type 
     */
    protected $data = array();

    /**
     * URL used for component redrawing
     * @var string
     */
    protected static $io_url = '/Platform/UI/php/component_io.php';
    
    /**
     * Indicate if this is a container component
     * @var bool
     */
    protected static $is_container_component = false;
    
    /**
     * Indicate if this component have readied its data
     * @var bool
     */
    protected $is_ready = false;
    
    /**
     * Indicates if this component can only be used when logged in. (Relevant
     * when using the ajax reload.     * 
     * @var bool
     */
    public static $is_secure = true;

    /**
     * Properties of the component
     * @var array
     */
    protected $properties = array();

    /**
     * Keeping registered events
     * @var array
     */
    protected $registered_events = [];

    /**
     * Used to store one or more attached form IDs
     * @var string
     */
    private $registered_form_ids = [];
    
    /**
     * Sub components to this component
     * @var array
     */
    protected $subcomponents = [];

    /**
     * Styles of this component
     * @var array
     */
    protected $styles = array();
    
    
    public function __construct() {
    }

    /**
     * Read a property of the component
     * @param string $property Property name
     * @return mixed Property value
     */
    public function __get(string $property) {
        if (! array_key_exists($property, $this->properties)) trigger_error('Tried to read invalid property: '.$property, E_USER_ERROR);
        return $this->properties[$property];
    }
    
    /**
     * Set a property of the component
     * @param string $property Property name
     * @param mixed $value Property value
     */
    public function __set(string $property, $value) {
        if (! array_key_exists($property, $this->properties)) trigger_error('Tried to set invalid property: '.$property, E_USER_ERROR);
        $this->properties[$property] = $value;
        if ($this->is_ready) $this->is_ready = false;
    }

    /**
     * Add a class to this component
     * @param string $class Class name
     */
    public function addClass(string $class) {
        $this->classes[] = $class;
    }
    
    /**
     * Add a html data key/value pair
     * @param string $key
     * @param mixed $value
     */
    public function addData(string $key, $value) {
        $this->data[$key] = $value;
    }
    
    /**
     * Add a map of properties to this object. It will be appended to existing properties.
     * @param array $propertymap
     */
    public function addPropertyMap(array $propertymap) {
        foreach ($propertymap as $key => $value)
            $this->properties[$key] = $value;
    }

    /**
     * Add a style to this component
     * @param string $style
     */
    public function addStyle(string $style) {
        $this->styles[] = $style;
    }
    
    /**
     * Add a subcomponent to this component
     * @param Component $component
     */
    public function addSubcomponent(Component $component) {
        $component->prepareComponent();
        $this->subcomponents[] = $component;
    }
    
    /**
     * Add several subcomponents to this component
     * @param array $components
     */
    public function addSubcomponents(array $components) {
        foreach ($components as $component) {
            $this->addSubcomponent($component);
        }
    }
    
    /**
     * Check if this component is allowed to render
     * @return bool True if this component will render.
     */
    public function canRender() : bool {
        return $this->can_render;
    }
    
    /**
     * Queue a CSS file for load
     * @param string $css_file
     */
    public static function CSSFile(string $css_file) {
        Page::CSSFile($css_file);
    }
    
    /**
     * Decode properties into this object
     * @param string $properties
     */
    public function decodeProperties(string $properties) {
        $result = json_decode($properties, true);
        foreach ($result as $key => $value) {
            if (is_array($value) && array_key_exists('__objecttype', $value)) {
                $value = $value['__objecttype']::constructFromSerialized($value);
            }
            $this->properties[$key] = $value;
        }
    }
    
    /**
     * Get the class of this component
     * @return string
     */
    public static function getComponentClass() : string {
        return static::$component_class ?: '';
    }
    
    /**
     * Return a copy of this component
     * @return Component
     */
    public function getCopy() : Component {
        $component = new static();
        $component->setPropertyMap($this->properties);
        return $component;
    }
    
    /**
     * Get data which was previously stored using saveSessionProperty()
     * @param string $property The name of the property
     * @return mixed The data or false if no data was stored
     */
    public function getSessionProperty(string $property) {
        if (isset($_SESSION['platform']['component_data'][$this->getID()][$property])) return $_SESSION['platform']['component_data'][$this->getID()][$property];
        return false;
    }
    
    
    /**
     * Get the properties of this component encoded for frontend
     * @return string
     */
    public function getEncodedProperties() : array {
        $result = [];
        foreach ($this->properties as $key => $value) {
            if (is_object($value)) {
                if (! $value instanceof Serializable) trigger_error('An object must implement Platform\\UI\\Serializable to be used as a property', E_USER_ERROR);
                $array = $value->getSerialized();
                $array['__objecttype'] = get_class($value);
                $value = $array;
            }
            $result[$key] = $value;
        }
        return $result;
    }

    /**
     * Get HTML ID of this component. If none have been assigned a random ID is
     * generated
     * @return string
     */
    public function getID() : string {
        if ($this->component_id === false) {
            $this->component_id = 'platform_component_'.rand();
        }
        return $this->component_id;
    }
    
    /**
     * Get the URL for doing IO with the backend.
     * @return string
     */
    public static function getIOUrl() : string {
        return static::$io_url;
    }
    
    /**
     * Get a friendly name for this component, which is the class name without path.
     * @return string
     */
    public function getName() : string {
        $name = strtolower(get_called_class());
        if (strpos($name,'\\')) $name = substr($name,strrpos($name,'\\')+1);
        return $name;
    }
    
    /**
     * Get the styles of this component
     * @return array
     */
    public function getStyles() : array {
        return $this->styles;
    }
    
    /**
     * Override to handle component IO
     * @return array
     */
    public function handleIO() : array {
        return [];
    }
    
    /**
     * Get the style of this component as a string
     * @return string
     */
    public function getStyleString() : string {
        $result = '';
        foreach ($this->getStyles() as $style) {
            if ($result != '' && substr($style,-1) != ';') $result .= ';';
            $result = trim($result.$style);
        }
        return $result;
    }
    
    /**
     * Queue a JS file for load
     * @param string $js_file
     */
    public static function JSFile(string $js_file) {
        Page::JSFile($js_file);
    }    
    
    /**
     * Run this to prepare component data. Can only run once.
     * @return type
     */
    public function prepareComponent() {
        if ($this->is_ready) return;
        $this->prepareData();
        if (static::$is_container_component) $this->addClass('platform_container_component');
        $this->is_ready = true;
    }
    
    /**
     * Override to prepare internal data in this component (if any)
     */
    protected function prepareData() {
    }
    
    /**
     * Register an event to be passed to the backend
     * @param string $event
     */
    public function registerEvent(string $event) {
        $this->registered_events[] = $event;
    }

    /**
     * Attach a form for using IO functions
     * @param Form|string $form or form ID
     */
    public function registerForm($form) {
        $this->registered_form_ids[] = $form instanceof Form ? $form->getFormId() : $form;
    }

    /**
     * Renders the component
     */
    public function render() {
        if (! $this->is_ready) $this->prepareComponent();
        if (! $this->canRender()) return;
        
        $classes = $this->classes;
        if ($this->getComponentClass()) $classes[] = $this->getComponentClass();

        if (static::$can_disable) $classes[] = 'platform_component_candisable';
        
        $this->addData('io_url', static::$io_url);
        $this->addData('componentclass', get_called_class());
        $this->addData('componentproperties', $this->getEncodedProperties());
        
        if (count($this->registered_events)) $this->addData('registered_events', implode(',',$this->registered_events));
        if (count($this->registered_form_ids)) $this->addData('registered_form_ids', implode(',',$this->registered_form_ids));
        
        echo '<div class="'.implode(' ',$classes).'" id="'.$this->getID().'"';
        foreach ($this->data as $key => $data) {
            if (is_array($data)) $data = json_encode($data);
            echo ' data-'.$key.'="'.htmlentities($data, ENT_QUOTES).'"';
        }
        $style_string = $this->getStyleString();
        if ($style_string) echo ' style="'.$style_string.'"';
        echo '>';
        $this->renderContent();
        echo '</div>';
    }
    
    /**
     * Render all subcomponents
     */
    protected function renderSubcomponents() {
        foreach ($this->subcomponents as $subcomponent) {
            $subcomponent->render();
        }
    }
    
    /**
     * Render the component content. Override in subclass.
     */
    public function renderContent() {
        $this->renderSubcomponents();
    }
    
    /**
     * Save some data regarding this componeent to the session, so it can be retrieved later by a component with the same ID
     * @param string $key The key to save data under
     * @param mixed $value The value to save
     */
    public function saveSessionProperty(string $key, $value) {
        $_SESSION['platform']['component_data'][$this->getID()][$key] = $value;
    }
    
    /**
     * Set ID of component
     * @param string $id
     */
    public function setID(string $id) {
        $this->component_id = $id;
    }
    
    /**
     * Set the property map of this component
     * @param array $property_map
     */
    public function setPropertyMap(array $property_map) {
        $this->properties = $property_map;
    }
    
    /**
     * Set if this component should render.
     * @param bool $render false to prevent render.
     */
    public function setRender(bool $render) {
        $this->can_render = $render;
    }
    
    /**
     * Set the style of this component
     * @param string $style
     */
    public function setStyle(string $style) {
        $this->styles = [$style];
    }
}