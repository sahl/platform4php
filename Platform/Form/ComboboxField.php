<?php
namespace Platform\Form;
/**
 * Field for comboboxes
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=field_class
 */

class ComboboxField extends TextField {
    
    const MODE_LIST = 1;
    const MODE_CALLBACK = 2;
    
    protected static $component_class = 'platform_component_form_comboboxfield';
    
    protected $mode = self::MODE_LIST;
    
    protected $is_strict = false;
    
    public function __construct() {
        parent::__construct();
        static::JSFile(\Platform\Utilities\Utilities::directoryToURL(__DIR__).'/js/ComboboxField.js');
    }
    
    public static function Field(string $label, string $name, array $options = array()) {
        $options['reserved_options'] = ['strict'];
        $field = parent::Field($label, $name, $options);
        $field->addClass('platform_combobox');
        if ($options['strict']) {
            $field->setStrict($options['strict']);
            unset($options['strict']);
        }
        return $field;
    }
    
    /**
     * Do backend autocomplete. Override.
     * @param string $term
     * @return array
     */
    public function autoComplete(string $term) : array {
        return [];
    }
    
    /**
     * Get the mode of this combobox
     * @return int
     */
    public function getMode() : int {
        return $this->mode;
    }
    
    /**
     * Get if this is strict
     * @return bool
     */
    public function getStrict() : bool {
        return $this->is_strict;
    }
    
    public function handleIO(): array {
        if ($_POST['event'] == 'autocomplete') {
            return $this->autoComplete($_POST['term']);
        }
        return parent::handleIO();
    }
    
    public function prepareData() {
        parent::prepareData();
        if ($this->mode == self::MODE_LIST) $this->addData('autocomplete_options', array_values($this->options));
        else $this->addData('use_callback', true);
    }
    
    /**
     * Set the mode of this combobox
     * @param int $mode
     */
    public function setMode(int $mode) {
        if (! in_array($mode, [self::MODE_LIST, self::MODE_CALLBACK])) trigger_error('Invalid mode', E_USER_ERROR);
        $this->mode = $mode;
    }
    
    /**
     * Set if this combobox should be strict
     * @param bool $is_strict
     */
    public function setStrict(bool $is_strict = true) {
        $this->is_strict = $is_strict;
    }
}