<?php
namespace Platform\UI;

class Dialog extends Component {
    
    protected $properties = [
        'title' => '',
        'text' => '',
        'form' => null
    ];
    
    public function __construct(string $id, string $title, string $text, array $buttons = array(), $form = false) {
        parent::__construct();
        $this->addClass('platform_invisible');
        $this->setID($id);
        $this->title = $title;
        $this->text = $text;
        $this->addData('buttons', $buttons);
        $this->form = $form;
    }
    
    public function renderContent() {
        echo $this->text;
        if ($this->form instanceof \Platform\Form) $this->form->render();
    }
    
}