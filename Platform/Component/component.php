<?php
namespace Platform;

class Component {
    
    public $configuration = array();
    
    public function __get($key) {
        $this->getConfiguration($key);
    }
    
    public function __set($key, $value) {
        $this->setConfiguration($key, $value);
    }
    
    public function getConfiguration($key) {
        return $this->configuration[$key];
    }
    
    public function render() {
        static::renderContent();
    }
    
    public function renderContent() {
        
    }
    
    public function setConfiguration($key, $value) {
        self::$configuration[$key] = $value;
    }
    
    
}