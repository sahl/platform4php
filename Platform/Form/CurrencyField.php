<?php
namespace Platform\Form;
/**
 * Field for currency
 * 
 * @link https://wiki.platform4php.dk/doku.php?id=field_class
 */

use Platform\Currency\Currency;
use Platform\Currency\Rate;
use Platform\Utilities\Time;
use Platform\Utilities\Utilities;

class CurrencyField extends Field {
    
    public static $component_class = 'platform_component_currency_field';
    
    public function __construct() {
        parent::__construct();
        static::JSFile(Utilities::directoryToURL(__DIR__).'/js/Field.js'); 
        static::JSFile(Utilities::directoryToURL(__DIR__).'/js/CurrencyField.js'); 
    }
    
    public static function Field(string $label, string $name, array $options = []): Field {
        return parent::Field($label, $name, $options);
    }
    
    public function handleIO(): array {
        switch($_POST['event']) {
            case 'currency_lookup':
                if (!is_numeric($_POST['foreignvalue']) || !Currency::isValidCurrency($_POST['currency'])) return ['status' => 0];
                $rate = Rate::getRate($_POST['currency'], Time::today());
                return ['status' => 1, 'localvalue' => $_POST['foreignvalue']/($rate/100)];
        }
        return parent::handleIO();
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
        if ($value['foreignvalue'] === '' || $value['currency'] == '') $value['foreignvalue'] = null;
        $this->value = $value;
        return true;
    }    
    
    public function renderInput() {
        echo '<input style="width: 120px;" id="'.$this->getFieldIdForHTML().'" class="'.$this->getFieldClasses().' currency_foreignvalue" type="number" name="'.$this->name.'[foreignvalue]" value="'.htmlentities($this->value['foreignvalue'], ENT_QUOTES).'"> ';
        echo '<select style="width: 75px;" name="'.$this->name.'[currency]" class="'.$this->getFieldClasses().' currency_currency">';
        $enabled_currencies = Currency::getEnabledCurrencies();
        echo '<option value="">';
        foreach ($enabled_currencies as $currency) {
            echo '<option value="'.$currency.'"';
            if ($currency == $this->value['currency']) echo ' selected';
            echo '>'.$currency;
        }
        echo '</select> <span class="fa fa-arrow-right"></span> ';
        echo '<input data-fieldclass="'.$this->getFieldClass().'" style="width: 120px;" class="'.$this->getFieldClasses().' currency_localvalue" type="number" name="'.$this->name.'[localvalue]" id="'.$this->getFieldIdForHTML().'" value="'.htmlentities($this->value['localvalue'], ENT_QUOTES).'"'.$this->additional_attributes.'> ';
        echo '<select disabled=true style="width: 75px;">';
        echo '<option selected>'.Currency::getBaseCurrency();
        echo '</select>';
    }
}