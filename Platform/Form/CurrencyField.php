<?php
namespace Platform\Form;

class CurrencyField extends Field {
    
    public function __construct(string $label, string $name, array $options = array()) {
        parent::__construct($label, $name, $options);
    }
    
    public function parse($value) : bool {
        if (! is_array($value)) {
            $this->triggerError('Invalid input');
            return false;
        }
        if ($this->is_required && ! $value['localvalue']) {
            $this->triggerError('This is a required field');
            return false;
        }
        $this->value = $value;
        return true;
    }    
    
    public function renderInput() {
        echo '<input style="width: 120px;" class="'.$this->getClassString().' currency_foreignvalue" type="number" name="'.$this->name.'[foreignvalue]" value="'.htmlentities($this->value['foreignvalue'], ENT_QUOTES).'"> ';
        echo '<select style="width: 75px;" name="'.$this->name.'[currency]" class="'.$this->getClassString().' currency_currency">';
        $enabled_currencies = \Platform\Currency\Currency::getEnabledCurrencies();
        if (! in_array($this->value['currency'], $enabled_currencies)) $this->value['currency'] = \Platform\Currency\Currency::getBaseCurrency ();
        foreach ($enabled_currencies as $currency) {
            echo '<option value="'.$currency.'"';
            if ($currency == $this->value['currency']) echo ' selected';
            echo '>'.$currency;
        }
        echo '</select> <span class="fa fa-arrow-right"></span> ';
        echo '<input style="width: 120px;" class="'.$this->getClassString().' currency_localvalue" type="number" name="'.$this->name.'[localvalue]" id="'.$this->getFieldIdForHTML().'" value="'.htmlentities($this->value['localvalue'], ENT_QUOTES).'"'.$this->additional_attributes.'> ';
        echo '<select disabled=true style="width: 75px;">';
        echo '<option selected>'.\Platform\Currency\Currency::getBaseCurrency();
        echo '</select>';
    }
}