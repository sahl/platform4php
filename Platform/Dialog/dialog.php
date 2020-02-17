<?php
namespace Platform;

class Dialog {
    
    private $id;
    private $title;
    private $text;
    private $form;
    private $buttons;
    
    public function __construct($id, $title, $text, $buttons = array(), $form = false) {
        $this->id = $id;
        $this->title = $title;
        $this->text = $text;
        $this->buttons = $buttons;
        $this->form = $form;
    }
    
    public function render() {
        echo '<div class="platform_dialog" id="'.$this->id.'" title="'.$this->title.'" data-buttons="'.htmlentities(json_encode($this->buttons)).'">';
        echo $this->text;
        if ($this->form instanceof Form) $this->form->render();
        echo '</div>';
    }
    
}