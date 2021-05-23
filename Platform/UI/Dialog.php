<?php
namespace Platform\UI;

class Dialog extends Component {
    
    public $form = null;
    
    protected $properties = [
        'title' => '',
        'text' => ''
    ];
    
    /**
     * Create a standard dialog
     * @param string $id Id of dialog
     * @param string $title Title of dialog
     * @param string $text Text in dialog
     * @param array $buttons Buttons as button text hashed by events which they trigger. "close" event closes the dialog
     * @param \Platform\Form $form Form to display
     */
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