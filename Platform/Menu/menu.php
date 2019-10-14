<?php
namespace Platform;

class Menu {
    
    private $elements = array();
    
    private $classes = array(
        'menu_selected' => 'platform_menu_item w3-red w3-small',
        'menu_button' => 'w3-small',
        'menu_background' => 'platform_menu_background',
        'menu_item' => 'platform_menu_item w3-white w3-small',
        'menu_bar' => 'platform_menu_bar w3-white w3-small',
        
    );
    
    public function __construct($menu = array()) {
        $this->setElements($menu);
    }
    
    private static function compareLocations($real_location, $menu_link) {
        if (substr($real_location,-9) == 'index.php') $real_location = substr($real_location, 0, -9);
        if (substr($menu_link,-9) == 'index.php') $menu_link = substr($menu_link, 0, -9);
        return strpos($real_location, $menu_link) !== false;
    }
    
    private function getBestLink($current_location) {
        // Extract links
        $linklist = array();
        foreach ($this->elements as $link => $title) {
            if (is_array($title)) {
                foreach ($title as $link => $subtitle) {
                    $linklist[] = $link;
                }
            } else {
                $linklist[] = $link;
            }
        }
        // Score links
        $bestscore = 0; $bestlink = false;
        foreach ($linklist as $link) {
            // Check if we already have a better link
            if (strlen($link) < $bestscore) continue;
            if (self::compareLocations($current_location, $link)) {
                $bestlink = $link; $bestscore = strlen($link);
            }
        }
        return $bestlink;
    }
    
    public function renderAsMenubutton() {
        echo '<div class="w3-dropdown-hover w3-section '.$this->classes['menu_button'].'">';
        echo '<button class="w3-btn"><i class="fa fa-bars"></i></button>';
        echo '<div class="w3-dropdown-content w3-bar-block w3-border">';
        foreach ($this->elements as $id => $title) {
            echo '<a href="#" id="'.$id.'" class="w3-bar-item w3-button">'.$title.'</a>';
        }
        echo '</div></div>';
    }
    
    public function renderAsTop() {
        // Resolve location
        $selected_link = $this->getBestLink($_SERVER['PHP_SELF']);
        echo '<div class="w3-bar '.$this->classes['menu_bar'].'">';
        foreach ($this->elements as $link => $title) {
            if (is_array($title)) {
                // This is a dropdown menu
                echo '<div class="w3-dropdown-hover">';
                echo '<button class="w3-button '.$this->classes['menu_item'].'">'.$link.'</button>';
                echo '<div class="w3-dropdown-content w3-bar-block w3-card-4">';
                foreach ($title as $link => $subtitle) {
                    $classes = $selected_link == $link ? ' '.$this->classes['menu_selected'] : $this->classes['menu_item'];
                    echo '<a href="'.$link.'" class="w3-bar-item w3-button '.$classes.'">'.$subtitle.'</a>';
                }
                echo '</div>';
                echo '</div>';
            } else {
                $classes = $selected_link == $link ? ' '.$this->classes['menu_selected'] : $this->classes['menu_item'];
                echo '<a href="'.$link.'" class="w3-bar-item w3-button '.$classes.'">'.$title.'</a>';
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