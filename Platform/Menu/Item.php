<?php
namespace Platform;

class MenuItem {
    
    public $text = '';
    public $url = '';
    public $id = '';
    public $classes = '';
    public $icon = '';
    public $data = array();
    
    /**
     * Construct a menu item
     * @param string $text Text on menu item
     * @param string $url URL to point to
     * @param string $id ID on menu item html
     * @param string $classes Class on menu item html
     * @param string $icon FA icon name
     * @param array $data Data on html tag with values hashed by keys
     */
    public function __construct($text, $url = '', $id = '', $classes = '', $icon = '', $data = array()) {
        Errorhandler::checkParams($text, 'string', $url, 'string', $id, 'string', $classes, 'string', $icon, 'string', $data, 'array');
        $this->text = $text;
        $this->url = $url;
        $this->id = $id;
        $this->classes = $classes;
        $this->icon = $icon;
        $this->data = $data;
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
    
    /**
     * Renders this menu item
     */
    public function render() {
        echo '<a';
        if ($this->url) echo ' href="'.$this->url.'"';
        if ($this->id) echo ' id="'.$this->id.'"';
        echo ' class="platform_menuitem';
        if ($this->classes) echo ' '.$this->classes;
        echo '"';
        if (count($this->data))
            foreach ($this->data as $key => $value) echo ' data-'.$key.'="'.$value.'"';
        echo '>';
        if ($this->icon) echo '<i class="fa '.$this->icon.'"></i> ';
        echo $this->text.'</a>';
    }
}