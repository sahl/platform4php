<?php
namespace Platform\UI\Menu;

class TopMenu extends Menu {
    
    public function __construct() {
        parent::__construct();
        self::CSSFile('/Platform/UI/Menu/css/TopMenu.css');
    }
    
    public function renderContent() {
        // Resolve location
        $this->calculateSelectedMenuItemByLocation();
        echo '<div>';
        foreach ($this->elements as $menuitem) {
            echo '<div class="platform_dropdown_menu">';
            if ($menuitem->hasSubmenu()) {
                $classes = $this->checkIfSelected($menuitem) ? ' platform_dropdown_menu_top_item_selected platform_dropdown_menu_top_item' : 'platform_dropdown_menu_top_item';
                echo '<div class="'.$classes.'">';
                $menuitem->render();
                echo '</div>';
                echo '<div class="platform_dropdown_menu_content">';
                foreach ($menuitem->submenu_items as $sub_menuitem) {
                    $classes = $this->checkIfSelected($sub_menuitem) ? 'platform_dropdown_menu_item platform_dropdown_menu_item_selected' : ' platform_dropdown_menu_item';
                    $sub_menuitem->addClass($classes);
                    $sub_menuitem->render();
                }
                echo '</div>';
            } else {
                $classes = $this->checkIfSelected($menuitem) ? ' platform_dropdown_menu_top_item_selected platform_dropdown_menu_top_item' : 'platform_dropdown_menu_top_item';
                echo '<div class="'.$classes.'">';
                $menuitem->render();
                echo '</div>';
            }
            echo '</div>';
        }
        echo '</div>';
    }
}