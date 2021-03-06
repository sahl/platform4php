<?php
namespace Platform;

class MenuTop extends Menu {
    
    public function renderContent() {
        // Resolve location
        $this->calculateSelectedMenuItemByLocation();
        echo '<div>';
        foreach ($this->elements as $menuitem) {
            if ($menuitem->hasSubmenu()) {
                // This is a dropdown menu
                echo '<div class="platform_dropdown_menu">';
                echo '<div class="platform_menu_top_item">';
                $menuitem->render();
                echo '</div>';
                echo '<div class="platform_dropdown_menu_content">';
                foreach ($menuitem->submenu_items as $sub_menuitem) {
                    $classes = $this->checkIfSelected($sub_menuitem) ? ' platform_dropdown_menu_item_selected' : ' platform_dropdown_menu_item';
                    $sub_menuitem->addClass($classes);
                    $sub_menuitem->render();
                }
                echo '</div>';
                echo '</div>';
            } else {
                echo '<div class="'.Design::getClass('platform_dropdown_menu').'">';
                $classes = $this->checkIfSelected($menuitem) ? ' platform_dropdown_menu_top_item_selected' : 'platform_dropdown_menu_top_item';
                echo '<div class="'.$classes.'">';
                $menuitem->render();
                echo '</div>';
                echo '</div>';
            }
        }
        echo '</div>';
    }
}