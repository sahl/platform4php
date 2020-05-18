<?php
namespace Platform;

class Component {
    
    /**
     * Indicate if this component can be disabled.
     * @var boolean 
     */
    public static $can_disable = true;
    
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
     * Add a class to this component
     * @param string $class Class name
     */
    public function addClass($class) {
        Errorhandler::checkParams($class, 'string');
        $this->classes[] = $class;
    }

    /**
     * Call this for preventing loading of Javascript
     */
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
     * Override to prepare internal data in this component (if any)
     */
    public function prepareData() {
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
        
        $classes[] = 'platform_component_'.$this->getName();
        
        echo '<div class="'.implode(' ',$classes).'" id="'.$this->component_id.'" data-redraw_url="'.static::$redraw_url.'" data-configuration="'.base64_encode(serialize($this)).'">';
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
}