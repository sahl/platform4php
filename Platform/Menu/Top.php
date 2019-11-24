<?php
namespace Platform;

class MenuTop extends Menu {
    
    public function renderContent() {
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
}