<?php
namespace Platform;

class MenuButton extends Menu {
    
    public function renderContent() {
        echo '<div class="w3-dropdown-hover w3-section '.$this->classes['menu_button'].'">';
        echo '<button class="w3-btn"><i class="fa fa-bars"></i></button>';
        echo '<div class="w3-dropdown-content w3-bar-block w3-border">';
        foreach ($this->elements as $id => $title) {
            echo '<a href="#" id="'.$id.'" class="w3-bar-item w3-button">'.$title.'</a>';
        }
        echo '</div></div>';
    }
    
}