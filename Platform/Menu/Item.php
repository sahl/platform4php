<?
namespace Platform;


class MenuItem {
    
    public $text = '';
    public $url = '';
    public $id = '';
    public $classes = '';
    public $icon = '';
    public $data = array();
    
    public function __construct($text, $url = '', $id = '', $classes = '', $icon = '', $data = array()) {
        $this->text = $text;
        $this->url = $url;
        $this->id = $id;
        $this->classes = $classes;
        $this->icon = $icon;
        $this->data = $data;
    }
    
    public function addClass($class) {
        if ($this->classes) $this->classes .= ' ';
        $this->classes .= $class;
    }
    
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