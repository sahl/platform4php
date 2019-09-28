<?php
namespace Platform;

class Menu {
    
    private $elements = array();
    
    private $classes = array(
        'selected' => 'w3-red'
    );
    
    public function __construct($menu = array()) {
        $this->setElements($menu);
    }
    
    private static function compareLocations($location1, $location2) {
        if (substr($location1,-9) == 'index.php') $location1 = substr($location1, 0, -9);
        if (substr($location2,-9) == 'index.php') $location1 = substr($location2, 0, -9);
        return $location1 == $location2;
    }
    
    public function renderAsMenubutton() {
        echo '<div class="w3-dropdown-hover w3-section w3-small">';
        echo '<button class="w3-btn"><i class="fa fa-bars"></i></button>';
        echo '<div class="w3-dropdown-content w3-bar-block w3-border">';
        foreach ($this->elements as $id => $title) {
            echo '<a href="#" id="'.$id.'" class="w3-bar-item w3-button">'.$title.'</a>';
        }
        echo '</div></div>';
    }
    
    public function renderAsTop() {
        // Resolve location
        $location = $_SERVER['PHP_SELF'];
        echo '<div class="w3-bar">';
        foreach ($this->elements as $link => $title) {
            if (is_array($title)) {
                // This is a dropdown menu
                echo '<div class="w3-dropdown-hover">';
                echo '<button class="w3-button">'.$link.'</button>';
                echo '<div class="w3-dropdown-content w3-bar-block w3-card-4">';
                foreach ($title as $link => $subtitle) {
                    $classes = self::compareLocations($location, $link) ? ' '.$this->classes['selected'] : '';
                    echo '<a href="'.$link.'" class="w3-bar-item w3-button'.$classes.'">'.$subtitle.'</a>';
                }
                echo '</div>';
                echo '</div>';
            } else {
                $classes = self::compareLocations($location, $link) ? ' '.$this->classes['selected'] : '';
                echo '<a href="'.$link.'" class="w3-bar-item w3-button'.$classes.'">'.$title.'</a>';
            }
        }
        echo '</div>';
    }
    
    public function setClass($class, $value) {
        if (isset($this->classes[$class])) $this->classes[$class] = $value;
    }
    
    public function setElements($elements) {
        $this->elements = $elements;
    }
    
    
}