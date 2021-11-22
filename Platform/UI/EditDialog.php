<?php
namespace Platform\UI;

use Platform\Utilities\Translation;


class EditDialog extends Dialog {
    
    /**
     * URL to the datarecord io handler
     * @var string 
     */
    protected static $url_io_datarecord = '/Platform/UI/php/io_datarecord.php';
    
    protected $class = null;
    
    
    public function __construct(string $class) {
        if (!class_exists($class)) trigger_error('Invalid class passed to EditDialog', E_USER_ERROR);
        $this->class = $class;
        
        parent::__construct($class::getClassName().'_edit_dialog', Translation::translateForUser('Edit %1', $class::getObjectName()), '', ['save' => Translation::translateForUser('Save'), 'close' => Translation::translateForUser('Cancel')]);
        
        $this->form = $class::getForm();
        
        self::JSFile('/Platform/UI/js/EditDialog.js');
        
        $this->addData('io_datarecord', self::$url_io_datarecord);
        $this->addData('classname', $class);
        $this->addData('element_name', $class::getObjectName());
    }
    
    public function prepareData() {
        if ($this->class == null) trigger_error('Cannot use without attaching a class', E_USER_ERROR);
        parent::prepareData();
    }
}