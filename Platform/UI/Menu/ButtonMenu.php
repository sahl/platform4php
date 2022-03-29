<?php
namespace Platform\UI\Menu;

class ButtonMenu extends Menu {
    
    public function __construct() {
        parent::__construct();
        self::CSSFile('/Platform/UI/Menu/css/ButtonMenu.css');
    }
    
    public function renderContent() {
        echo '<div class="platform_button_menu_button platform_button_menu">';
        echo '<button><i class="fa fa-bars"></i></button><br>';
        echo '<div class="platform_button_menu_content">';
        foreach ($this->elements as $element) {
            $element->addClass('platform_button_menu_item');
            $element->render();
        }
        echo '</div></div>';
    }
    
}