<?php
namespace Platform\Form;

class MultidatarecordcomboboxField extends MultiplierSection {
    
    private $datarecord_combobox = null;
    
    public function __construct(string $label, string $name, array $options = array()) {
        $this->datarecord_combobox = new DatarecordcomboboxField('', 'innercombobox', array('class' => $options['class']));
        $this->datarecord_combobox->addContainerStyle('margin-top: 0px');
        unset($options['class']);
        parent::__construct($label, $name, $options);
        $this->addFields($this->datarecord_combobox);
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
     * @param \Platform\Filter $filter
     */
    public function setFilter(\Platform\Filter $filter) {
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