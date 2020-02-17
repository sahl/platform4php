<?php
namespace Platform;

class Component {
    
    /**
     * Indicate if this component can be disabled.
     * @var boolean 
     */
    public static $can_disable = true;
    
    /**
     * Configuration of component.
     * @var array 
     */
    public $configuration = array();
    
    /**
     * Javascript to load along with this component.
     * @var boolean|string 
     */
    public static $js_script = false;
    
    /**
     * Indicate if base component script is loaded.
     * @var boolean 
     */
    private static $base_script_loaded = false;

    /**
     * Internal counter used for assigning component IDs if not set
     * @var int 
     */
    private static $component_counter = 1;
    
    /**
     * URL used for component redrawing
     * @var string
     */
    protected static $redraw_url = '/Platform/Component/php/get_content.php';
    
    /**
     * Indicates if this component can only be used when logged in. (Relevant
     * when using the ajax reload.     * 
     * @var boolean
     */
    public static $is_secure = true;
    
    /**
     * Component ID for HTML
     * @var boolean|string 
     */
    private $component_id = false;
    
    private $classes = array();

    /**
     * Construct this component
     * @param type $configuration
     */
    public function __construct($configuration = array()) {
        foreach ($configuration as $key => $value) $this->setConfiguration ($key, $value);
    }
    
    
    /**
     * Short for getConfiguration
     * @param string $key Configuration key to retrieve
     * @return mixed
     */
    public function __get($key) {
        return $this->getConfiguration($key);
    }
    
    /**
     * Short for setConfiguration
     * @param string $key Configuration key to set
     * @param mixed $value Value to set
     */
    public function __set($key, $value) {
        $this->setConfiguration($key, $value);
    }
    
    /**
     * Add a class to this component
     * @param string $class Class name
     */
    public function addClass($class) {
        $this->classes[] = $class;
    }
    
    public function dontLoadScript() {
        self::$base_script_loaded = true;
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
     * Get a configuration parameter from this component
     * @param string $key Configuration key to retrieve
     * @return mixed
     */
    public function getConfiguration($key) {
        return $this->configuration[$key];
    }

    /**
     * Renders the component
     */
    public function render() {
        if (static::$js_script) {
            \Platform\Design::JSFile(static::$js_script);
            static::$js_script = false;
        }
        if (! self::$base_script_loaded) {
            \Platform\Design::JSFile('/Platform/Component/js/component.js');
            self::$base_script_loaded = true;
        }
        
        if ($this->component_id === false) {
            $this->component_id = 'platform_component_'.(self::$component_counter++);
        }
        
        $classes = $this->classes;
        $classes[] = 'platform_component';
        if (static::$can_disable) $classes[] = 'platform_component_candisable';
        
        $configuration = $this->configuration;
        $configuration['__class'] = get_called_class();
        
        echo '<div class="'.implode(' ',$classes).'" id="'.$this->component_id.'" data-redraw_url="'.static::$redraw_url.'" data-configuration="'.htmlentities(json_encode($configuration), ENT_QUOTES).'">';
        $this->renderInnerDiv();
        echo '</div>';
    }
    
    /**
     * Render the component content. Override in subclass.
     */
    public function renderContent() {
        echo 'Override me';
    }
    
    public function renderInnerDiv() {
        $inner_class = 'platform_component_'.$this->getName();
        echo '<div class="'.$inner_class.'">';
        $this->renderContent();
        echo '</div>';
    }
    
     /**
     * Set a configuration parameter
     * @param string $key Configuration key to set
     * @param mixed $value Value to set
     */
   public function setConfiguration($key, $value) {
        if (isset($this->configuration[$key])) $this->configuration[$key] = $value;
    }
    
    /**
     * Set ID of component
     * @param string $id
     */
    public function setID($id) {
        $this->component_id = $id;
    }
}