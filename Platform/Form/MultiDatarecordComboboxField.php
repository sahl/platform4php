<?php
namespace Platform\Form;
/**
 * Field for an array of combobox which is linked to a Datarecord object and can only select items of that type.
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=field_class
 */

class MultiDatarecordComboboxField extends MultiplierSection {
    
    private $datarecord_combobox = null;
    
    protected static $component_class = 'platform_component_form_multi_datarecord_combobox';
    
    public function __construct() {
        parent::__construct();
        static::JSFile(\Platform\Utilities\Utilities::directoryToURL(__DIR__).'/js/Field.js');
        static::JSFile(\Platform\Utilities\Utilities::directoryToURL(__DIR__).'/js/MultiDatarecordComboboxField.js');
        $this->datarecord_combobox = DatarecordComboboxField::Field('', 'innercombobox');
        $this->datarecord_combobox->addFieldStyle('margin-top: 0px');
        $this->addFields($this->datarecord_combobox);
    }
    
    public static function Field(string $label = '', string $name = '', array $options = array()) {
        $field = parent::Field($label, $name, $options);
        $field->addClass('platform_formfield_container');
        if ($options['datarecord_class']) {
            $field->datarecord_combobox->connected_class = $options['datarecord_class'];
            unset($options['datarecord_class']);
        }
        // No label for this field?
        return $field;
    }
    
    
    public function parse($values) : bool {
        $result = parent::parse($values);
        if ($result && $this->is_required && ! count($this->getValue())) {
            $this->triggerError('This is a required field');
            $result = false;
        }
        return $result;
    }
    
    public function getValue() {
        $values = parent::getValue();
        $real_results = array();
        foreach ($values as $value) {
            $real_results[] = $value['innercombobox'];
        }
        return $real_results;
    }
    
    /**
     * Attach a filter to this datarecordcombobox
     * @param \Platform\Filter\Filter $filter
     */
    public function setFilter(\Platform\Filter\Filter $filter) {
        $this->datarecord_combobox->setFilter($filter);
    }    
    
    public function setValue($values) {
        if (! is_array($values)) $values = array();
        $real_values = array();
        foreach ($values as $value) {
            $real_values[] = array('innercombobox' => $value);
        }
        parent::setValue($real_values);
    }
}