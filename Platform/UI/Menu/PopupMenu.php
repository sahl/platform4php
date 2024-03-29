<?php
namespace Platform\UI\Menu;
/**
 * Menu class for drawing a popup menu which appear on top of other items.
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=menu_class
 */

use Platform\Page\Page;
use Platform\UI\Component;
use Platform\Utilities\Utilities;

class PopupMenu extends Menu {
    
    const LOCATION_MOUSE = 1;
    const LOCATION_TOP = 10;
    const LOCATION_BOTTOM = 20;
    const LOCATION_LEFT = 30;
    const LOCATION_RIGHT = 40;
    
    public static $can_redraw = false;
    
    private $location = self::LOCATION_MOUSE;
    
    protected static $component_class = 'platform_menu_popupmenu';
    
    private $attached_component = null;
    
    public function __construct() {
        Page::JSFile(Utilities::directoryToURL(__DIR__).'js/PopupMenu.js');
        Page::CSSFile(Utilities::directoryToURL(__DIR__).'css/PopupMenu.css');
        $this->setPropertyMap([
            'info' => []
        ]);
        parent::__construct();
    }
    
    /**
     * Attach this popupmenu to the given other component
     * @param Component $component
     */
    public function attachTo(Component $component) {
        $this->attached_component = $component;
    }
    
    /**
     * Attach this popupmenu to the HTML element that matches the given jquery selector
     * @param string $selector
     */
    public function attachToElement(string $selector) {
        $this->addData('attach_to', $selector);
    }
    
    protected function prepareData() {
        parent::prepareData();
        if ($this->attached_component) $this->addData ('attach_to', '#'.$this->attached_component->getID());
        $this->addData('location', $this->location);
    }
    
    /**
     * Set the location where the menu should appear
     * @param int $location
     */
    public function setLocation(int $location) {
        if (! in_array($location, [self::LOCATION_MOUSE, self::LOCATION_TOP, self::LOCATION_BOTTOM, self::LOCATION_LEFT, self::LOCATION_RIGHT])) trigger_error('Invalid location specified', E_USER_ERROR);
        $this->location = $location;
    }

    public function renderContent() {
        foreach ($this->elements as $menu_item) {
            echo '<div class="menuitem">';
            $menu_item->render();
            echo '</div>';
        }
    }
}
