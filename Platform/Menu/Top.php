<?php
namespace Platform;

class MenuTop extends Menu {
    
    public function renderContent() {
        // Resolve location
        $selected_link = $this->getBestLink($_SERVER['PHP_SELF']);
        echo '<div>';
        foreach ($this->elements as $link => $title) {
            if (is_array($title)) {
                // This is a dropdown menu
                echo '<div class="'.Design::getClass('dropdown_menu').'">';
                echo '<div class="'.Design::getClass('dropdown_menu_top_item').'">'.$link.'</div>';
                echo '<div class="'.Design::getClass('dropdown_menu_content').'">';
                foreach ($title as $link => $subtitle) {
                    $classes = $selected_link == $link ? ' '.Design::getClass('dropdown_menu_item_selected') : Design::getClass('dropdown_menu_item');
                    echo '<a href="'.$link.'" class="'.$classes.'">'.$subtitle.'</a>';
                }
                echo '</div>';
                echo '</div>';
            } else {
                echo '<div class="'.Design::getClass('dropdown_menu').'">';
                $classes = $selected_link == $link ? ' '.Design::getClass('dropdown_menu_top_item_selected') : Design::getClass('dropdown_menu_top_item');
                echo '<div class="'.$classes.'">';
                echo '<a href="'.$link.'">'.$title.'</a>';
                echo '</div>';
                echo '</div>';
            }
        }
        echo '</div>';
    }
}