<?php
namespace Platform\UI\Menu;

use Platform\Page\MenuItem;
use Platform\UI\Component;

class Menu extends Component {
    
    protected $elements = array();
    
    protected $select_menuitem = null;
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Add a single menu item to this menu
     * @param MenuItem $menuitem
     */
    public function addMenuItem(MenuItem $menuitem) {
        $this->elements[] = $menuitem;
    }

    /**
     * Add several menu items to this menu
     * @param array $menuitems
     */
    public function addMenuItems(array $menuitems) {
        foreach ($menuitems as $element) {
            $this->addMenuItem($element);
        }
    }
    
    /**
     * Compare two locations to see if one is part of the other
     * @param string $real_location The current location
     * @param string $menu_link The menu link
     * @return bool True if the menu link is a part of the current location
     */
    protected static function compareLocations(string $real_location, string $menu_link) : bool {
        if (substr($real_location,-9) == 'index.php') $real_location = substr($real_location, 0, -9);
        if (substr($menu_link,-9) == 'index.php') $menu_link = substr($menu_link, 0, -9);
        if (substr($real_location,-4) == '.php') $real_location = substr($real_location, 0, -4);
        if (substr($menu_link,-4) == '.php') $menu_link = substr($menu_link, 0, -4);
        return strpos($real_location, $menu_link) !== false;
    }
    
    /**
     * Calculate a selected menu item
     */
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
     * @return bool
     */
    public function checkIfSelected(MenuItem $menuitem) {
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
}