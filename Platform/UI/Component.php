<?php
namespace Platform\UI;

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
     * Internal counter used for assigning component IDs if not set
     * @var int 
     */
    private static $component_counter = 1;

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
     * Styles of this component
     * @var array
     */
    protected $styles = array();
    
    /**
     * URL used for component redrawing
     * @var string
     */
    protected static $redraw_url = '/Platform/UI/php/get_content.php';
    
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
    public function CSSFile(string $css_file) {
        \Platform\Page::CSSFile($css_file);
    }

    /**
     * Get HTML ID of this component
     * @return string
     */
    public function getID() : string {
        if ($this->component_id === false) {
            $this->component_id = 'platform_component_'.(self::$component_counter++);
        }
        return $this->component_id;
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
    public function JSFile(string $js_file) {
        \Platform\Page::JSFile($js_file);
    }    
    
    /**
     * Override to prepare internal data in this component (if any)
     */
    public function prepareData() {
        $this->is_ready = true;
    }

    /**
     * Renders the component
     */
    public function render() {
        if (! $this->is_ready) $this->prepareData();
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
        $style_string = $this->getStyleString();
        if ($style_string) echo ' style="'.$style_string.'"';
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