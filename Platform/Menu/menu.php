<?php
namespace Platform;

class Menu extends Component {
    
    protected $elements = array();
    
    protected $classes = array(
        'menu_selected' => 'platform_menu_item w3-red w3-small',
        'menu_button' => 'w3-small',
        'menu_background' => 'platform_menu_background',
        'menu_item' => 'platform_menu_item w3-white w3-small',
        'menu_bar' => 'platform_menu_bar w3-white w3-small',
    );
    
    protected static function compareLocations($real_location, $menu_link) {
        if (substr($real_location,-9) == 'index.php') $real_location = substr($real_location, 0, -9);
        if (substr($menu_link,-9) == 'index.php') $menu_link = substr($menu_link, 0, -9);
        return strpos($real_location, $menu_link) !== false;
    }
    
    protected function getBestLink($current_location) {
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
    
    public function setClass($keyword, $classes) {
        if (isset($this->classes[$keyword])) $this->classes[$keyword] = $classes;
    }
    
    public function setClasses($class_array) {
        foreach ($class_array as $keyword => $classes) $this->setClass($keyword, $classes);
    }
    
    public function setElements($elements) {
        $this->elements = $elements;
    }
    
    
}