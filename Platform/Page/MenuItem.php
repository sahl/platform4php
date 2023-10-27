<?php
namespace Platform\Page;

class MenuItem {
    
    public $text = '';
    public $url = '';
    public $id = '';
    public $classes = '';
    public $icon = '';
    public $image = '';
    public $data = [];
    public $submenu_items = [];
    public $target = '';
    
    /**
     * Construct a menu item
     * @param string $text Text on menu item
     * @param string $url URL to point to
     * @param string $id ID on menu item html
     * @param string $classes Class on menu item html
     * @param string $icon FA icon name or image file
     * @param array $data Data on html tag with values hashed by keys
     * @param array $submenu_items An array with MenuItem's intended as submenu to this item
     */
    public function __construct(string $text, string $url = '', string $id = '', string $classes = '', string $icon = '', array $data = array(), array $submenu_items = array()) {
        $this->text = $text;
        $this->url = $url;
        $this->id = $id;
        $this->classes = $classes;
        if (self::isImage($icon)) $this->image = $icon;
        else $this->icon = $icon;
        $this->data = $data;
        $this->submenu_items = $submenu_items;
    }

    /**
     * Add a class to this menu item
     * @param string $class
     */
    public function addClass(string $class) {
        if ($this->classes) $this->classes .= ' ';
        $this->classes .= $class;
    }
    
    public function addSubmenu(array $menu_items) {
        foreach ($menu_items as $menu_item) {
            $this->addSubmenuItem($menu_item);
        }
    }
    
    public function addSubmenuItem(MenuItem $menu_item) {
        $this->submenu_items[] = $menu_item;
    }
    
    /**
     * Construct a MenuItem by ID
     * @param string $text Text on menu item
     * @param string $id ID on menu item html
     * @param string $classes Class on menu item html
     * @param string $icon FA icon name or image file
     * @param array $data Data on html tag with values hashed by keys
     * @return \Platform\Page\MenuItem
     */
    public static function constructByID(string $text, string $id, string $classes = '', string $icon = '', array $data = array(), array $submenu_items = array()) {
        return new MenuItem($text, '#', $id, $classes, $icon, $data, $submenu_items);
    }
    
    /**
     * Construct a MenuItem with a submenu
     * @param string $text Text on menu item
     * @param type $submenu_items
     * @param array $submenu_items An array with MenuItem's intended as submenu to this item
     * @return \Platform\Page\MenuItem
     */
    public static function constructSubmenu(string $text, array $submenu_items) {
        return new MenuItem($text, '#', '', '', '', array(), $submenu_items);
    }
    
    /**
     * Get HTML for this menu item.
     * @return string
     */
    public function getHTML() : string {
        $result = '<a';
        if ($this->target) $result .= ' target="'.$this->target.'"';
        if ($this->url) $result .= ' href="'.$this->url.'"';
        if ($this->id) $result .= ' id="'.$this->id.'"';
        $result .= ' class="platform_menuitem';
        if ($this->classes) $result .= ' '.$this->classes;
        $result .= '"';
        if (count($this->data))
            foreach ($this->data as $key => $value) $result .= ' data-'.$key.'="'.$value.'"';
        $result .= '>';
        if ($this->icon) $result .= '<i class="fa '.$this->icon.'" aria-hidden="true"></i>';
        if ($this->image) $result .= '<img src="'.$this->image.'" style="height: 1em; border: none;">';
        if (($this->icon || $this->image) && $this->text) $result .= '&nbsp;';
        $result .= $this->text.'</a>';
        return $result;
    }
    
    /**
     * Return all submenu items
     * @return array
     */
    public function getSubmenu() : array {
        return $this->submenu_items;
    }
    
    /**
     * Does this menuitem have a submenu
     * @return bool
     */
    public function hasSubmenu() : bool {
        return count($this->submenu_items) > 0;
    }
    
    /**
     * Check if the string contains an image file. We do this by detecting a dot in the filename
     * @param string $string String to check
     * @return bool True if image
     */
    private static function isImage(string $string) : bool {
        return strpos($string,'.') !== false;
    }
    
    /**
     * Renders this menu item
     */
    public function render() {
        echo $this->getHTML();
    }
    
    /**
     * Render a menuitem directly to the page
     * @param string $text Text on menu item
     * @param string $url URL to point to
     * @param string $id ID on menu item html
     * @param string $classes Class on menu item html
     * @param string $icon FA icon name or image file
     * @param array $data Data on html tag with values hashed by keys
     * @param array $submenu_items An array with MenuItem's intended as submenu to this item
     */
    public static function renderDirectly (string $text, string $url = '', string $id = '', string $classes = '', string $icon = '', array $data = array(), array $submenu_items = array()) {
        $menu_item = new MenuItem($text, $url, $id, $classes, $icon, $data, $submenu_items);
        $menu_item->render();
    }
    
}