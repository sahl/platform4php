<?php
namespace Platform\UI;

class Dialog extends Component {
    
    public $form = null;
    
    private $components = [];
    
    protected static $component_class = 'platform_dialog';
    
    protected $properties = [
        'title' => '',
        'text' => '',
        'buttons' => []
    ];
    
    private $dialog_options = [];
    
    public function __construct() {
        parent::__construct();
        $this->addClass('platform_invisible'); // This breaks tables in dialogs
    }
    
    /**
     * Create a standard dialog
     * @param string $id Id of dialog
     * @param string $title Title of dialog
     * @param string $text Text in dialog
     * @param array $buttons Buttons as button text hashed by events which they trigger. "close" event closes the dialog
     * @param \Platform\Form\Form $form Form to display
     */
    public static function Dialog(string $id, string $title, string $text, array $buttons = array(), $form = false) : Dialog {
        $dialog = new static();
        $dialog->setID($id);
        $dialog->title = $title;
        $dialog->text = $text;
        $dialog->addButtons($buttons);
        $dialog->form = $form;
        return $dialog;
    }
    
    public function addButton(string $event, string $text) {
        $buttons = $this->buttons;
        $buttons[$event] = $text;
        $this->buttons = $buttons;
    }
    
    public function addButtons(array $buttons) {
        foreach ($buttons as $event => $text)
            $this->addButton($event, $text);
    }
    
    public function addComponent(Component $component) {
        $this->components[] = $component;
    }
    
    public function prepareData() {
        parent::prepareData();
        if ($this->title) $this->setDialogOption('title', $this->title);
        if (count($this->buttons)) $this->addData('buttons', $this->buttons);
    }
    
    public function renderContent() {
        echo '<div class="platform_invisible dialog_configuration">';
        echo json_encode($this->dialog_options);
        echo '</div>';
        echo $this->text;
        if ($this->form instanceof \Platform\Form\Form) $this->form->render();
        foreach ($this->components as $component) $component->render();
    }
    
    /**
     * Render a form as invisible to be used later with the javascript formDialog
     * @param \Platform\Form\Form $form Form to render (invisible)
     */
    public static function renderFormForFormDialog(\Platform\Form\Form $form) {
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