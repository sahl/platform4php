<?php
namespace Platform\UI;

class Dialog extends Component {
    
    public $form = null;
    
    private $components = [];
    
    protected $properties = [
        'title' => '',
        'text' => ''
    ];
    
    private $dialog_options = [];
    
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
        // This is commented out to accomodate tables in dialogs. We need to find solution
        //$this->addClass('platform_invisible');
        $this->setID($id);
        $this->title = $title;
        $this->text = $text;
        $this->addData('buttons', $buttons);
        $this->addClass('platform_invisible'); // This breaks tables in dialogs
        $this->form = $form;
    }
    
    public function addComponent(Component $component) {
        $this->components[] = $component;
    }
    
    public function renderContent() {
        echo '<div class="platform_invisible dialog_configuration">';
        echo json_encode($this->dialog_options);
        echo '</div>';
        echo $this->text;
        if ($this->form instanceof \Platform\Form) $this->form->render();
        foreach ($this->components as $component) $component->render();
    }
    
    /**
     * Render a form as invisible to be used later with the javascript formDialog
     * @param \Platform\Form $form Form to render (invisible)
     */
    public static function prepareForm(\Platform\Form $form) {
        echo '<div class="platform_invisible">';
        $form->render();
        echo '</div>';
    }
    
    /**
     * Set an option for the Jquery dialog
     * @param string $option Option to set
     * @param type $value Value to use
     */
    public function setDialogOption(string $option, $value) {
        $this->dialog_options[$option] = $value;
    }
}