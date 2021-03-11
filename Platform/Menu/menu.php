<?php
namespace Platform;

class Menu extends Component {
    
    protected $elements = array();
    
    protected $select_menuitem = null;
    
    public function __construct() {
        Page::CSSFile('/Platform/Menu/css/menu.css');
        parent::__construct();
    }
    
    protected static function compareLocations($real_location, $menu_link) {
        if (substr($real_location,-9) == 'index.php') $real_location = substr($real_location, 0, -9);
        if (substr($menu_link,-9) == 'index.php') $menu_link = substr($menu_link, 0, -9);
        if (substr($real_location,-4) == '.php') $real_location = substr($real_location, 0, -4);
        if (substr($menu_link,-4) == '.php') $menu_link = substr($menu_link, 0, -4);
        return strpos($real_location, $menu_link) !== false;
    }
    
    protected function calculateSelectedMenuItemByLocation() {
        // Current location
        $current_location = $_SERVER['PHP_SELF'];
        // Extract links
        $menuitems = $this->getAllMenuitems();
        $best_menuitem = null;
        // Score links
        $best_score = 0; $best_menuitem = false;
        foreach ($menuitems as $menuitem) {
            // Check if we already have a better link
            if (strlen($menuitem->url) < $best_score) continue;
            if (self::compareLocations($current_location, $menuitem->url)) {
                $best_menuitem = $menuitem; $best_score = strlen($menuitem->url);
            }
        }
        $this->select_menuitem = $best_menuitem;
    }
    
    /**
     * Check if the passed menuitem is the selected menu item
     * @param MenuItem $menuitem
     * @return boolean
     */
    public function checkIfSelected($menuitem) {
        Errorhandler::checkParams($menuitem, 'Platform\MenuItem');
        return $this->select_menuitem === $menuitem;
    }
    
    /**
     * Get all menu items in this menu as array. This includes menu items in submenus
     * @param array $menu_items Menu items to examine. If false, then use builtin items
     * @return array<MenuItem>
     */
    private function getAllMenuitems($menu_items = false) {
        if ($menu_items === false) $menu_items = $this->elements;
        $result = array();
        foreach ($menu_items as $menu_item) {
            $result[] = $menu_item;
            if ($menu_item->hasSubmenu()) $result = array_merge($result, $this->getAllMenuitems ($menu_item->submenu_items));
        }
        return $result;
    }
    
    public function setClass($keyword, $classes) {
        if (isset($this->classes[$keyword])) $this->classes[$keyword] = $classes;
    }
    
    public function setClasses($class_array) {
        foreach ($class_array as $keyword => $classes) $this->setClass($keyword, $classes);
    }
    
    public function setElements($elements) {
        Errorhandler::checkParams($elements, 'array');
        foreach ($elements as $element) {
            if (! $element instanceof MenuItem) trigger_error('Invalid element passed to menu. Expected type MenuItem', E_USER_ERROR);
            $this->elements[] = $element;
        }
    }
    
    
}