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
     * @var boolean|string|array
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

    /**
     * Classes
     * @var array
     */
    private $classes = array();
    
    /**
     * Data for html tag
     * @var type 
     */
    protected $data = array();

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
            if (is_array(static::$js_script)) {
                foreach (static::$js_script as $script)
                    \Platform\Design::JSFile(static::$js_script);
            } else {
                \Platform\Design::JSFile(static::$js_script);
            }
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
        
        echo '<div class="'.implode(' ',$classes).'" id="'.$this->component_id.'" data-redraw_url="'.static::$redraw_url.'" data-object="'.base64_encode(serialize($this)).'"';
        foreach ($this->data as $key => $data) {
            if (is_array($data)) $data = json_encode($data);
            echo ' data-'.$key.'="'.htmlentities($data, ENT_QUOTES).'"';
        }
        echo '>';
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
     * Set ID of component
     * @param string $id
     */
    public function setID($id) {
        Errorhandler::checkParams($id, 'string');
        $this->component_id = $id;
    }
}