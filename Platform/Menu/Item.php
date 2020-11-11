<?php
namespace Platform;

class MenuItem {
    
    public $text = '';
    public $url = '';
    public $id = '';
    public $classes = '';
    public $icon = '';
    public $data = array();
    public $submenu_items = array();
    
    /**
     * Construct a menu item
     * @param string $text Text on menu item
     * @param string $url URL to point to
     * @param string $id ID on menu item html
     * @param string $classes Class on menu item html
     * @param string $icon FA icon name
     * @param array $data Data on html tag with values hashed by keys
     * @param array $submenu_items An array with MenuItem's intended as submenu to this item
     */
    public function __construct($text, $url = '', $id = '', $classes = '', $icon = '', $data = array(), $submenu_items = array()) {
        Errorhandler::checkParams($text, 'string', $url, 'string', $id, 'string', $classes, 'string', $icon, 'string', $data, 'array');
        $this->text = $text;
        $this->url = $url;
        $this->id = $id;
        $this->classes = $classes;
        $this->icon = $icon;
        $this->data = $data;
        $this->submenu_items = $submenu_items;
    }

    /**
     * Add a class to this menu item
     * @param string $class
     */
    public function addClass($class) {
        Errorhandler::checkParams($class, 'string');
        if ($this->classes) $this->classes .= ' ';
        $this->classes .= $class;
    }
    
    public function addSubmenu($menu_items) {
        Errorhandler::checkParams($menu_items, 'array');
        foreach ($menu_items as $menu_item) {
            $this->addSubmenuItem($menu_item);
        }
    }
    
    public function addSubmenuItem($menu_item) {
        if (! $menu_item instanceof MenuItem) trigger_error('Invalid element passed to submenu. Expected type MenuItem!', E_USER_ERROR);
        $this->submenu_items[] = $menu_item;
    }
    
    /**
     * Construct a MenuItem by ID
     * @param string $text Text on menu item
     * @param string $id ID on menu item html
     * @param string $classes Class on menu item html
     * @param string $icon FA icon name
     * @param array $data Data on html tag with values hashed by keys
     * @return \Platform\MenuItem
     */
    public static function constructByID($text, $id, $classes = '', $icon = '', $data = array(), $submenu_items = array()) {
        return new MenuItem($text, '#', $id, $classes, $icon, $data, $submenu_items);
    }
    
    /**
     * Construct a MenuItem with a submenu
     * @param string $text Text on menu item
     * @param type $submenu_items
     * @param array $submenu_items An array with MenuItem's intended as submenu to this item
     * @return \Platform\MenuItem
     */
    public static function constructSubmenu($text, $submenu_items) {
        return new MenuItem($text, '#', '', '', '', array(), $submenu_items);
    }
    
    /**
     * Get HTML for this menu item.
     * @return string
     */
    public function getHTML() {
        $result = '<a';
        if ($this->url) $result .= ' href="'.$this->url.'"';
        if ($this->id) $result .= ' id="'.$this->id.'"';
        $result .= ' class="platform_menuitem';
        if ($this->classes) $result .= ' '.$this->classes;
        $result .= '"';
        if (count($this->data))
            foreach ($this->data as $key => $value) $result .= ' data-'.$key.'="'.$value.'"';
        $result .= '>';
        if ($this->icon) $result .= '<i class="fa '.$this->icon.'"></i> ';
        $result .= $this->text.'</a>';
        return $result;
    }
    
    /**
     * Does this menuitem have a submenu
     * @return boolean
     */
    public function hasSubmenu() {
        return count($this->submenu_items) > 0;
    }
    
    /**
     * Renders this menu item
     */
    public function render() {
        echo $this->getHTML();
    }
}