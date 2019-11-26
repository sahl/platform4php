<?php
namespace Platform;

class Component {
    
    public $configuration = array();
    
    public static $js_script = false;
    
    public static $base_script_loaded = false;
    
    public static $can_disable = true;
    
    public function __get($key) {
        return $this->getConfiguration($key);
    }
    
    public function __set($key, $value) {
        $this->setConfiguration($key, $value);
    }
    
    public function getName() {
        $name = strtolower(get_called_class());
        if (strpos($name,'\\')) $name = substr($name,strrpos($name,'\\')+1);
        return $name;
    }
    
    public function getConfiguration($key) {
        return $this->configuration[$key];
    }
    
    public function render() {
        if (static::$js_script) {
            \Platform\Design::JSFile(static::$js_script);
            static::$js_script = false;
        }
        if (! self::$base_script_loaded) {
            \Platform\Design::JSFile('/Platform/Component/js/component.js');
            self::$base_script_loaded = true;
        }
        $classes = array();
        $classes[] = 'platform_component';
        if (static::$can_disable) $classes[] = 'platform_component_candisable';
        $classes[] = 'platform_component_'.$this->getName();
        echo '<div class="'.implode(' ',$classes).'" data-configuration="'.htmlentities(json_encode($this->configuration), ENT_QUOTES).'">';
        static::renderContent();
        echo '</div>';
    }
    
    public function renderContent() {
    }
    
    public function setConfiguration($key, $value) {
        if (isset($this->configuration[$key])) $this->configuration[$key] = $value;
    }
    
    
}