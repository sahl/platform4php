<?php
namespace Platform;

class Dialog extends Component {
    
    protected static $js_files = ['/Platform/Dialog/js/dialog.js'];
    
    protected $properties = [
        'title' => '',
        'text' => '',
        'form' => null
    ];
    
    public function __construct($id, $title, $text, $buttons = array(), $form = false) {
        parent::__construct();
        $this->setID($id);
        $this->title = $title;
        $this->text = $text;
        $this->addData('buttons', $buttons);
        $this->form = $form;
    }
    
    public function renderContent() {
        echo $this->text;
        if ($this->form instanceof Form) $this->form->render();
    }
    
}