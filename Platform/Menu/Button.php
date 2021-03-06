<?php
namespace Platform;

class MenuButton extends Menu {
    
    public function renderContent() {
        echo '<div class="platform_dropdown_menu_button">';
        echo '<button><i class="fa fa-bars"></i></button><br>';
        echo '<div class="platform_dropdown_menu_content">';
        foreach ($this->elements as $element) {
            $element->addClass('platform_dropdown_menu_item');
            $element->render();
        }
        echo '</div></div>';
    }
    
}